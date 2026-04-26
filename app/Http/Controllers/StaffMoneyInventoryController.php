<?php

namespace App\Http\Controllers;

use App\Models\MoneyInventory;
use App\Models\Order;
use App\Models\PaymentEntry;
use App\Models\PaymentEntryItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StaffMoneyInventoryController extends Controller
{
    private const DENOMINATIONS = [1000, 500, 200, 100, 50, 20, 10, 1];

    private const PAYMENT_DENOMINATIONS = [1000, 500, 200, 100, 50, 20, 10, 5, 1];

    private function resolveSelectedDate(Request $request): string
    {
        $raw = $request->query('date');
        if (!is_string($raw) || trim($raw) === '') {
            return Carbon::today()->toDateString();
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable $e) {
            return Carbon::today()->toDateString();
        }
    }

    private function ensureClockedOut(Request $request): ?JsonResponse
    {
        $user = $request->user();

        if ($user && (bool) $user->clocked_in === true) {
            return response()->json([
                'message' => 'Please Clock Out to access Money Inventory.',
            ], 403);
        }

        return null;
    }

    public function index(Request $request): View|RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user && (bool) $user->clocked_in === true) {
            return redirect()->route('profile.edit')->with('status', 'Please Clock Out to access Money Inventory.');
        }

        $date = $this->resolveSelectedDate($request);

        $dateDisplay = $date;
        try {
            $dateDisplay = Carbon::parse($date)->format('F j Y (l)');
        } catch (\Throwable $e) {
            $dateDisplay = $date;
        }

        $existing = MoneyInventory::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->get(['denomination', 'quantity']);

        $qtyByDenom = $existing->mapWithKeys(function (MoneyInventory $row) {
            return [(int) $row->denomination => (int) $row->quantity];
        });

        $quantities = collect(self::DENOMINATIONS)
            ->mapWithKeys(fn (int $d) => [$d => (int) $qtyByDenom->get($d, 0)])
            ->all();

        $reconciliationRow = DB::table('daily_sales_reconciliations')
            ->where('user_id', $user->id)
            ->where('date', $date)
            ->first(['reconciled_at', 'total_sales']);

        $hasReconciliation = $reconciliationRow !== null;
        $reconciledAt = null;
        if ($reconciliationRow && $reconciliationRow->reconciled_at) {
            try {
                $reconciledAt = Carbon::parse($reconciliationRow->reconciled_at);
            } catch (\Throwable $e) {
                $reconciledAt = null;
            }
        }

        $basePaidOrders = Order::query()
            ->where('created_by', $user->id)
            ->where('status', 'paid')
            ->whereDate('created_at', $date);

        if ($reconciledAt) {
            $basePaidOrders->where('created_at', '>', $reconciledAt);
        }

        $todaysTotalSales = (float) (clone $basePaidOrders)->sum('total_amount');

        $todaysCashSales = (float) (clone $basePaidOrders)
            ->where('payment_type', 'cash')
            ->sum('total_amount');

        $todaysGcashSales = (float) (clone $basePaidOrders)
            ->where('payment_type', 'gcash')
            ->sum('total_amount');

        $reconciledToday = $hasReconciliation && ($todaysTotalSales <= 0);

        $reconciledAtIso = $reconciledAt ? $reconciledAt->toIso8601String() : null;

        $allDayTotalSales = (float) Order::query()
            ->where('created_by', $user->id)
            ->where('status', 'paid')
            ->whereDate('created_at', $date)
            ->sum('total_amount');

        $lowerTodaysTotalSales = $allDayTotalSales;

        $paymentEntries = PaymentEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->with(['items' => function ($q) {
                $q->orderByDesc('denomination');
            }])
            ->latest()
            ->get(['id', 'user_id', 'date', 'payment_type', 'received_amount', 'created_at', 'order_id']);

        // Fetch GCash orders for the current date
        $gcashOrders = Order::query()
            ->where('created_by', $user->id)
            ->where('status', 'paid')
            ->where('payment_type', 'gcash')
            ->whereDate('created_at', $date)
            ->with(['items' => function ($q) {
                $q->select(['id', 'order_id', 'name', 'price', 'quantity']);
            }])
            ->orderBy('created_at')
            ->get(['id', 'order_number', 'total_amount', 'created_at', 'gcash_reference', 'gcash_sender_name', 'gcash_sender_mobile']);

        // Get confirmed order IDs from payment entries
        $confirmedOrderIds = $paymentEntries
            ->where('payment_type', 'gcash')
            ->pluck('order_id')
            ->filter()
            ->toArray();

        // Filter out confirmed GCash orders
        $gcashOrders = $gcashOrders->reject(function ($order) use ($confirmedOrderIds) {
            return in_array($order->id, $confirmedOrderIds);
        })->values();

        if ($request->expectsJson()) {
            return response()->json([
                'date' => $date,
                'date_display' => $dateDisplay,
                'reconciled_at' => $reconciledAtIso,
                'summary' => [
                    'total_sales' => $todaysTotalSales,
                    'cash' => $todaysCashSales,
                    'gcash' => $todaysGcashSales,
                    'lower_total_sales' => $lowerTodaysTotalSales,
                ],
                'reconciled' => $reconciledToday,
                'payment_entries_count' => $paymentEntries->count(),
            ]);
        }

        return view('staff.money-inventory', [
            'date' => $date,
            'dateDisplay' => $dateDisplay,
            'denominations' => self::DENOMINATIONS,
            'quantities' => $quantities,
            'todaysTotalSales' => $todaysTotalSales,
            'todaysCashSales' => $todaysCashSales,
            'todaysGcashSales' => $todaysGcashSales,
            'lowerTodaysTotalSales' => $lowerTodaysTotalSales,
            'reconciledToday' => $reconciledToday,
            'reconciledAt' => $reconciledAtIso,
            'clockedIn' => (bool) ($user->clocked_in ?? false),
            'paymentDenominations' => self::PAYMENT_DENOMINATIONS,
            'paymentEntries' => $paymentEntries,
            'gcashOrders' => $gcashOrders,
            'confirmedOrderIds' => $confirmedOrderIds,
        ]);
    }

    public function save(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            $blocked = $this->ensureClockedOut($request);
            if ($blocked) {
                return $blocked;
            }
        }

        $user = $request->user();

        if ($user && (bool) $user->clocked_in === true) {
            return redirect()->route('profile.edit')->with('status', 'Please Clock Out to access Money Inventory.');
        }

        $date = null;
        $rawDate = $request->input('date');
        if (is_string($rawDate) && trim($rawDate) !== '') {
            try {
                $date = Carbon::parse($rawDate)->toDateString();
            } catch (\Throwable $e) {
                $date = null;
            }
        }
        if (!$date) {
            $date = $this->resolveSelectedDate($request);
        }

        $validated = $request->validate([
            'quantities' => ['required', 'array'],
        ]);

        $raw = $validated['quantities'];

        foreach (self::DENOMINATIONS as $denomination) {
            $qty = $raw[$denomination] ?? 0;
            $qty = is_numeric($qty) ? (int) $qty : 0;
            if ($qty < 0) {
                $qty = 0;
            }

            // Get active sales session for the user
            $activeSession = Shift::where('user_id', $user->id)
                ->where('status', 'ACTIVE')
                ->first();

            MoneyInventory::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $date,
                    'denomination' => $denomination,
                ],
                [
                    'quantity' => $qty,
                    'shift_id' => $activeSession ? $activeSession->shift_id : null,
                    'business_date' => $activeSession ? $activeSession->business_date : null,
                ]
            );
        }

        // Close current session and create new session
        $activeSession = Shift::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->first();

        if ($activeSession) {
            $activeSession->update([
                'ended_at' => Carbon::now(),
                'status' => 'CLOSED',
            ]);

            // Create new active session
            $businessDate = Carbon::now()->toDateString();
            $newSalesSessionId = 'SESSION-' . Carbon::now()->format('YmdHis') . '-' . strtoupper(substr($user->name, 0, 5));

            Shift::create([
                'user_id' => $user->id,
                'shift_id' => $newSalesSessionId,
                'business_date' => $businessDate,
                'started_at' => Carbon::now(),
                'status' => 'ACTIVE',
                'opening_cash' => null,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Money inventory saved. New sales session started.',
            ]);
        }

        return redirect()->route('staff.money-inventory.index', ['date' => $date])->with('status', 'Money inventory saved. New sales session started.');
    }

    public function storePaymentEntry(Request $request): JsonResponse
    {
        $blocked = $this->ensureClockedOut($request);
        if ($blocked) {
            return $blocked;
        }

        $user = $request->user();
        $date = null;
        $rawDate = $request->input('date');
        if (is_string($rawDate) && trim($rawDate) !== '') {
            try {
                $date = Carbon::parse($rawDate)->toDateString();
            } catch (\Throwable $e) {
                $date = null;
            }
        }
        if (!$date) {
            $date = $this->resolveSelectedDate($request);
        }

        $validated = $request->validate([
            'payment_type' => ['required', 'string', 'in:cash,gcash'],
            'received_amount' => ['nullable', 'numeric', 'min:0'],
            'breakdown' => ['nullable', 'array'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
        ]);

        $paymentType = (string) $validated['payment_type'];
        $breakdown = is_array($validated['breakdown'] ?? null) ? $validated['breakdown'] : [];

        $cleanBreakdown = [];
        foreach (self::PAYMENT_DENOMINATIONS as $denom) {
            $qty = $breakdown[$denom] ?? $breakdown[(string) $denom] ?? 0;
            $qty = is_numeric($qty) ? (int) $qty : 0;
            if ($qty < 0) {
                $qty = 0;
            }
            $cleanBreakdown[$denom] = $qty;
        }

        $amountFromBreakdown = 0;
        foreach ($cleanBreakdown as $denom => $qty) {
            $amountFromBreakdown += ((int) $denom) * ((int) $qty);
        }

        $receivedAmount = null;
        if (array_key_exists('received_amount', $validated) && $validated['received_amount'] !== null && $validated['received_amount'] !== '') {
            $receivedAmount = (int) round((float) $validated['received_amount']);
        }

        if ($receivedAmount === null) {
            $receivedAmount = $amountFromBreakdown;
        }

        if ($receivedAmount < 0) {
            $receivedAmount = 0;
        }

        if ($receivedAmount === 0) {
            return response()->json([
                'message' => 'Received amount is required.',
                'errors' => [
                    'received_amount' => ['Received amount is required.'],
                ],
            ], 422);
        }

        $orderId = $validated['order_id'] ?? null;

        // If order_id is provided, validate against that specific order's amount
        if ($orderId) {
            $order = Order::where('id', $orderId)
                ->where('created_by', $user->id)
                ->where('payment_type', $paymentType)
                ->where('status', 'paid')
                ->whereDate('created_at', $date)
                ->first();

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found or does not match criteria.',
                    'errors' => [
                        'order_id' => ['Order not found or does not match criteria.'],
                    ],
                ], 422);
            }

            if ((int) $receivedAmount !== (int) $order->total_amount) {
                return response()->json([
                    'message' => 'Received amount does not match the order total.',
                    'errors' => [
                        'received_amount' => ["Received amount must match order total ({$order->total_amount})."],
                    ],
                ], 422);
            }
        } else {
            // Remove strict validation for total daily sales - allow saving working entries
            // This allows staff to save temporary entries during reconciliation process
            // Final validation happens when saving Daily Reconciliation
        }

        $entry = DB::transaction(function () use ($user, $date, $paymentType, $receivedAmount, $cleanBreakdown, $orderId) {
            // Get active shift for the user
            $activeShift = Shift::where('user_id', $user->id)
                ->where('status', 'ACTIVE')
                ->first();

            $entryData = [
                'user_id' => $user->id,
                'date' => $date,
                'payment_type' => $paymentType,
                'received_amount' => $receivedAmount,
                'order_id' => $orderId,
                'shift_id' => $activeShift ? $activeShift->shift_id : null,
                'business_date' => $activeShift ? $activeShift->business_date : null,
            ];

            // If this is a GCash entry with an order, copy GCash verification data from the order
            if ($paymentType === 'gcash' && $orderId) {
                $order = Order::where('id', $orderId)
                    ->where('created_by', $user->id)
                    ->where('payment_type', 'gcash')
                    ->where('status', 'paid')
                    ->whereDate('created_at', $date)
                    ->first();

                if ($order) {
                    $entryData['gcash_sender_name'] = $order->gcash_sender_name;
                    $entryData['gcash_reference_number'] = $order->gcash_reference;
                    $entryData['gcash_sender_mobile'] = $order->gcash_sender_mobile;
                    $entryData['gcash_proof_image'] = $order->gcash_proof_image;
                    $entryData['verified_at'] = now();
                    $entryData['verified_by'] = $user->id;
                }
            }

            $entry = PaymentEntry::create($entryData);

            foreach ($cleanBreakdown as $denom => $qty) {
                if ($qty <= 0) {
                    continue;
                }
                PaymentEntryItem::create([
                    'payment_entry_id' => $entry->id,
                    'denomination' => (int) $denom,
                    'quantity' => (int) $qty,
                ]);
            }

            return $entry;
        });

        $entry->load(['items' => function ($q) {
            $q->orderByDesc('denomination');
        }]);

        return response()->json([
            'message' => 'Payment entry saved.',
            'payment_entry' => [
                'id' => (int) $entry->id,
                'payment_type' => (string) $entry->payment_type,
                'received_amount' => (int) $entry->received_amount,
                'order_id' => (int) $entry->order_id,
                'created_at' => $entry->created_at?->toIso8601String(),
                'items' => $entry->items->map(fn (PaymentEntryItem $i) => [
                    'denomination' => (int) $i->denomination,
                    'quantity' => (int) $i->quantity,
                ])->values()->all(),
            ],
        ]);
    }

    public function updatePaymentEntry(Request $request, PaymentEntry $entry): JsonResponse
    {
        $blocked = $this->ensureClockedOut($request);
        if ($blocked) {
            return $blocked;
        }

        $user = $request->user();
        if (! $user || (int) $entry->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Not authorized.',
            ], 403);
        }

        $today = Carbon::today()->toDateString();
        if ($entry->date?->toDateString() !== $today) {
            return response()->json([
                'message' => 'Only today\'s entries can be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'breakdown' => ['required', 'array'],
        ]);

        $rawBreakdown = $validated['breakdown'] ?? [];
        $cleanBreakdown = [];
        foreach (self::PAYMENT_DENOMINATIONS as $denom) {
            $qty = $rawBreakdown[$denom] ?? $rawBreakdown[(string) $denom] ?? 0;
            $qty = is_numeric($qty) ? (int) $qty : 0;
            if ($qty < 0) {
                $qty = 0;
            }
            $cleanBreakdown[$denom] = $qty;
        }

        $amount = 0;
        foreach ($cleanBreakdown as $denom => $qty) {
            $amount += ((int) $denom) * ((int) $qty);
        }

        if ($amount <= 0) {
            return response()->json([
                'message' => 'Received amount is required.',
                'errors' => [
                    'breakdown' => ['Received amount is required.'],
                ],
            ], 422);
        }

        DB::transaction(function () use ($entry, $cleanBreakdown, $amount): void {
            $entry->received_amount = (int) $amount;
            $entry->save();

            foreach ($cleanBreakdown as $denom => $qty) {
                if ($qty > 0) {
                    PaymentEntryItem::query()->updateOrCreate(
                        [
                            'payment_entry_id' => (int) $entry->id,
                            'denomination' => (int) $denom,
                        ],
                        [
                            'quantity' => (int) $qty,
                        ]
                    );
                } else {
                    PaymentEntryItem::query()
                        ->where('payment_entry_id', (int) $entry->id)
                        ->where('denomination', (int) $denom)
                        ->delete();
                }
            }
        });

        $entry->load(['items' => function ($q) {
            $q->orderByDesc('denomination');
        }]);

        return response()->json([
            'message' => 'Payment entry updated.',
            'entry' => [
                'id' => (int) $entry->id,
                'payment_type' => (string) $entry->payment_type,
                'received_amount' => (int) $entry->received_amount,
                'created_at' => $entry->created_at?->toIso8601String(),
                'items' => $entry->items->map(fn (PaymentEntryItem $i) => [
                    'denomination' => (int) $i->denomination,
                    'quantity' => (int) $i->quantity,
                ])->values()->all(),
            ],
        ]);
    }

    public function resetTodaysSales(Request $request): JsonResponse
    {
        $blocked = $this->ensureClockedOut($request);
        if ($blocked) {
            return $blocked;
        }

        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $paymentType = $request->input('payment_type', 'all'); // 'cash', 'gcash', or 'all'

        $reconciledAt = now();

        // Check if reconciliation_data column exists
        $hasReconciliationDataColumn = Schema::hasColumn('daily_sales_reconciliations', 'reconciliation_data');

        if ($hasReconciliationDataColumn) {
            // Store reconciliation data with payment type in JSON
            $reconciliationData = [
                'reconciled_at' => $reconciledAt->toIso8601String(),
                'payment_type' => $paymentType,
            ];

            // Get existing reconciliation data
            $existing = DB::table('daily_sales_reconciliations')
                ->where('user_id', $user->id)
                ->where('date', $today)
                ->first();

            if ($existing) {
                // Merge with existing data
                $existingData = json_decode($existing->reconciliation_data ?? '{}', true);
                $existingData[$paymentType] = $reconciliationData;

                DB::table('daily_sales_reconciliations')
                    ->where('user_id', $user->id)
                    ->where('date', $today)
                    ->update([
                        'reconciled_at' => $reconciledAt,
                        'reconciliation_data' => json_encode($existingData),
                        'total_sales' => Order::query()
                            ->where('created_by', $user->id)
                            ->where('status', 'paid')
                            ->whereDate('created_at', $today)
                            ->where('created_at', '<=', $reconciledAt)
                            ->sum('total_amount'),
                    ]);
            } else {
                // Create new record
                DB::table('daily_sales_reconciliations')->insert([
                    'user_id' => $user->id,
                    'date' => $today,
                    'reconciled_at' => $reconciledAt,
                    'reconciliation_data' => json_encode([$paymentType => $reconciliationData]),
                    'total_sales' => Order::query()
                        ->where('created_by', $user->id)
                        ->where('status', 'paid')
                        ->whereDate('created_at', $today)
                        ->where('created_at', '<=', $reconciledAt)
                        ->sum('total_amount'),
                ]);
            }
        } else {
            // Fallback: use old behavior (single reconciliation for all)
            DB::table('daily_sales_reconciliations')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'date' => $today,
                ],
                [
                    'reconciled_at' => $reconciledAt,
                    'total_sales' => Order::query()
                        ->where('created_by', $user->id)
                        ->where('status', 'paid')
                        ->whereDate('created_at', $today)
                        ->where('created_at', '<=', $reconciledAt)
                        ->sum('total_amount'),
                ]
            );
        }

        return response()->json([
            'message' => 'Today\'s sales reconciled.',
            'reconciled_at' => $reconciledAt?->toIso8601String(),
            'payment_type' => $paymentType,
        ]);
    }

    public function undoTodaysSalesReconciliation(Request $request): JsonResponse
    {
        $blocked = $this->ensureClockedOut($request);
        if ($blocked) {
            return $blocked;
        }

        $user = $request->user();
        $today = Carbon::today()->toDateString();

        DB::table('daily_sales_reconciliations')
            ->where('user_id', $user->id)
            ->where('date', $today)
            ->delete();

        $basePaidOrders = Order::query()
            ->where('created_by', $user->id)
            ->where('status', 'paid')
            ->whereDate('created_at', $today);

        $todaysTotalSales = (float) (clone $basePaidOrders)->sum('total_amount');
        $todaysCashSales = (float) (clone $basePaidOrders)->where('payment_type', 'cash')->sum('total_amount');
        $todaysGcashSales = (float) (clone $basePaidOrders)->where('payment_type', 'gcash')->sum('total_amount');

        return response()->json([
            'message' => 'Today\'s reconciliation undone.',
            'totals' => [
                'overall' => $todaysTotalSales,
                'cash' => $todaysCashSales,
                'gcash' => $todaysGcashSales,
            ],
        ]);
    }

    public function deletePaymentEntry(Request $request, PaymentEntry $entry): JsonResponse
    {
        $blocked = $this->ensureClockedOut($request);
        if ($blocked) {
            return $blocked;
        }

        $user = $request->user();
        if (! $user || (int) $entry->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Not authorized.',
            ], 403);
        }

        $today = Carbon::today()->toDateString();
        if ($entry->date?->toDateString() !== $today) {
            return response()->json([
                'message' => 'Only today\'s entries can be deleted.',
            ], 422);
        }

        $entry->delete();

        return response()->json([
            'message' => 'Payment entry deleted.',
            'id' => (int) $entry->id,
        ]);
    }
}
