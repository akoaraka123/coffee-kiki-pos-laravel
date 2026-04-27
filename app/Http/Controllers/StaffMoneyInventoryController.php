<?php

namespace App\Http\Controllers;

use App\Models\MoneyInventory;
use App\Models\Order;
use App\Models\PaymentEntry;
use App\Models\PaymentEntryItem;
use App\Models\SavedMoneyInventory;
use App\Models\Shift;
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

    private function parseDateString(mixed $raw): ?string
    {
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveActiveShift(?int $userId): ?Shift
    {
        if (!$userId) {
            return null;
        }

        return Shift::query()
            ->where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->latest('started_at')
            ->first();
    }

    private function resolveViewBusinessDate(Request $request, ?int $userId): string
    {
        $requested = $this->parseDateString($request->query('date'));
        if ($requested) {
            return $requested;
        }

        $activeShift = $this->resolveActiveShift($userId);
        if ($activeShift?->business_date) {
            return Carbon::parse($activeShift->business_date)->toDateString();
        }

        return Carbon::today()->toDateString();
    }

    private function resolveWriteBusinessDate(Request $request, ?int $userId): string
    {
        $activeShift = $this->resolveActiveShift($userId);
        if ($activeShift?->business_date) {
            return Carbon::parse($activeShift->business_date)->toDateString();
        }

        $requested = $this->parseDateString($request->input('date'));
        if ($requested) {
            return $requested;
        }

        return Carbon::today()->toDateString();
    }

    private function applyOrderBusinessDateFilter($query, string $businessDate): void
    {
        $query->where(function ($q) use ($businessDate) {
            $q->whereDate('business_date', $businessDate)
                ->orWhere(function ($legacy) use ($businessDate) {
                    $legacy->whereNull('business_date')
                        ->whereDate('created_at', $businessDate);
                });
        });
    }

    private function applyPaymentEntryBusinessDateFilter($query, string $businessDate): void
    {
        $query->where(function ($q) use ($businessDate) {
            $q->whereDate('business_date', $businessDate)
                ->orWhere(function ($legacy) use ($businessDate) {
                    $legacy->whereNull('business_date')
                        ->where(function ($legacyDate) use ($businessDate) {
                            $legacyDate->whereDate('date', $businessDate)
                                ->orWhereDate('created_at', $businessDate);
                        });
                });
        });
    }

    private function applyMoneyInventoryBusinessDateFilter($query, string $businessDate): void
    {
        $query->where(function ($q) use ($businessDate) {
            $q->whereDate('business_date', $businessDate)
                ->orWhere(function ($legacy) use ($businessDate) {
                    $legacy->whereNull('business_date')
                        ->whereDate('date', $businessDate);
                });
        });
    }

    private function applySavedInventoryBusinessDateFilter($query, string $businessDate): void
    {
        $query->where(function ($q) use ($businessDate) {
            $q->whereDate('business_date', $businessDate)
                ->orWhere(function ($legacy) use ($businessDate) {
                    $legacy->whereNull('business_date')
                        ->whereDate('date', $businessDate);
                });
        });
    }

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

        $date = $this->resolveViewBusinessDate($request, $user?->id);

        $dateDisplay = $date;
        try {
            $dateDisplay = Carbon::parse($date)->format('F j Y (l)');
        } catch (\Throwable $e) {
            $dateDisplay = $date;
        }

        $existingQuery = MoneyInventory::query()
            ->where('user_id', $user->id);
        $this->applyMoneyInventoryBusinessDateFilter($existingQuery, $date);
        $existing = $existingQuery->get(['denomination', 'quantity']);

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
            ->where('status', 'paid');
        $this->applyOrderBusinessDateFilter($basePaidOrders, $date);

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

        $allDayOrdersQuery = Order::query()
            ->where('created_by', $user->id)
            ->where('status', 'paid');
        $this->applyOrderBusinessDateFilter($allDayOrdersQuery, $date);
        $allDayTotalSales = (float) $allDayOrdersQuery->sum('total_amount');

        $lowerTodaysTotalSales = $allDayTotalSales;

        $paymentEntriesQuery = PaymentEntry::query()
            ->where('user_id', $user->id)
            ->with(['items' => function ($q) {
                $q->orderByDesc('denomination');
            }])
            ->latest();
        $this->applyPaymentEntryBusinessDateFilter($paymentEntriesQuery, $date);
        $paymentEntries = $paymentEntriesQuery->get(['id', 'user_id', 'date', 'business_date', 'payment_type', 'received_amount', 'created_at', 'order_id']);

        // Fetch GCash orders for the current date
        $gcashOrders = Order::query()
            ->where('created_by', $user->id)
            ->where('status', 'paid')
            ->where('payment_type', 'gcash')
            ->with(['items' => function ($q) {
                $q->select(['id', 'order_id', 'name', 'price', 'quantity']);
            }])
            ->orderBy('created_at');
        $this->applyOrderBusinessDateFilter($gcashOrders, $date);
        $gcashOrders = $gcashOrders->get(['id', 'order_number', 'total_amount', 'created_at', 'gcash_reference', 'gcash_sender_name', 'gcash_sender_mobile']);

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

        // Fetch saved money inventory records
        $savedInventoriesQuery = SavedMoneyInventory::query()
            ->where('user_id', $user->id)
            ->with(['user:id,name'])
            ->latest('saved_at');
        $this->applySavedInventoryBusinessDateFilter($savedInventoriesQuery, $date);
        $savedInventories = $savedInventoriesQuery->get();

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
            'savedInventories' => $savedInventories,
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

        $date = $this->resolveWriteBusinessDate($request, $user?->id);

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
            $activeSession = $this->resolveActiveShift($user->id);

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

        // Group payment entries into saved money inventory record
        $paymentEntriesQuery = PaymentEntry::query()
            ->where('user_id', $user->id)
            ->with(['items', 'order'])
            ->latest();
        $this->applyPaymentEntryBusinessDateFilter($paymentEntriesQuery, $date);
        $paymentEntries = $paymentEntriesQuery->get();

        $savedInventoryPayload = null;
        $clearedEntriesCount = 0;

        if ($paymentEntries->isNotEmpty()) {
            DB::transaction(function () use ($user, $date, $paymentEntries, &$savedInventoryPayload, &$clearedEntriesCount) {
                // Calculate totals
                $cashTotal = $paymentEntries->where('payment_type', 'cash')->sum('received_amount');
                $gcashTotal = $paymentEntries->where('payment_type', 'gcash')->sum('received_amount');
                $totalVerified = $cashTotal + $gcashTotal;

                // Get today's total sales
                $basePaidOrders = Order::query()
                    ->where('created_by', $user->id)
                    ->where('status', 'paid');
                $this->applyOrderBusinessDateFilter($basePaidOrders, $date);
                $todaysTotalSales = (float) (clone $basePaidOrders)->sum('total_amount');

                $difference = $todaysTotalSales - $totalVerified;

                // Build cash breakdown
                $cashBreakdown = [];
                $cashEntries = $paymentEntries->where('payment_type', 'cash');
                foreach ($cashEntries as $entry) {
                    foreach ($entry->items as $item) {
                        $denom = (int) $item->denomination;
                        $qty = (int) $item->quantity;
                        if (!isset($cashBreakdown[$denom])) {
                            $cashBreakdown[$denom] = 0;
                        }
                        $cashBreakdown[$denom] += $qty;
                    }
                }

                // Build GCash details
                $gcashDetails = [];
                $gcashEntries = $paymentEntries->where('payment_type', 'gcash');
                foreach ($gcashEntries as $entry) {
                    $gcashDetails[] = [
                        'sender_name' => $entry->gcash_sender_name,
                        'gcash_reference' => $entry->gcash_reference_number,
                        'mobile' => $entry->gcash_sender_mobile,
                        'order_number' => $entry->order ? $entry->order->order_number : null,
                        'amount' => (int) $entry->received_amount,
                    ];
                }

                // Build payment entries data
                $paymentEntriesData = $paymentEntries->map(function ($entry) {
                    return [
                        'id' => (int) $entry->id,
                        'payment_type' => (string) $entry->payment_type,
                        'received_amount' => (int) $entry->received_amount,
                        'created_at' => $entry->created_at?->toIso8601String(),
                        'items' => $entry->items->map(fn ($item) => [
                            'denomination' => (int) $item->denomination,
                            'quantity' => (int) $item->quantity,
                        ])->values()->all(),
                    ];
                })->values()->all();

                // Get active session
                $activeSession = $this->resolveActiveShift($user->id);

                // Create saved money inventory record
                $savedInventory = SavedMoneyInventory::create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'saved_at' => now(),
                    'total_sales' => $todaysTotalSales,
                    'cash_total' => $cashTotal,
                    'gcash_total' => $gcashTotal,
                    'total_verified' => $totalVerified,
                    'difference' => $difference,
                    'status' => 'saved',
                    'cash_breakdown' => $cashBreakdown,
                    'gcash_details' => $gcashDetails,
                    'payment_entries' => $paymentEntriesData,
                    'shift_id' => $activeSession ? $activeSession->shift_id : null,
                    'business_date' => $activeSession ? $activeSession->business_date : null,
                ]);

                // Delete the payment entries after saving
                $clearedEntriesCount = PaymentEntry::query()
                    ->where('user_id', $user->id)
                    ->where(function ($q) use ($date) {
                        $q->whereDate('business_date', $date)
                            ->orWhere(function ($legacy) use ($date) {
                                $legacy->whereNull('business_date')
                                    ->where(function ($legacyDate) use ($date) {
                                        $legacyDate->whereDate('date', $date)
                                            ->orWhereDate('created_at', $date);
                                    });
                            });
                    })
                    ->delete();

                $savedInventory->loadMissing(['user:id,name']);
                $savedInventoryPayload = [
                    'id' => (int) $savedInventory->id,
                    'date' => $savedInventory->date?->toDateString(),
                    'saved_at' => $savedInventory->saved_at?->toIso8601String(),
                    'total_sales' => (float) $savedInventory->total_sales,
                    'cash_total' => (float) $savedInventory->cash_total,
                    'gcash_total' => (float) $savedInventory->gcash_total,
                    'total_verified' => (float) $savedInventory->total_verified,
                    'difference' => (float) $savedInventory->difference,
                    'status' => (string) $savedInventory->status,
                    'cash_breakdown' => $savedInventory->cash_breakdown,
                    'gcash_details' => $savedInventory->gcash_details,
                    'payment_entries' => $savedInventory->payment_entries,
                    'user' => $savedInventory->user ? [
                        'name' => (string) $savedInventory->user->name,
                    ] : null,
                ];
            });
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
                'saved_inventory' => $savedInventoryPayload,
                'cleared_entries_count' => (int) $clearedEntriesCount,
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
        $date = $this->resolveWriteBusinessDate($request, $user?->id);

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
                ->where(function ($q) use ($date) {
                    $q->whereDate('business_date', $date)
                        ->orWhere(function ($legacy) use ($date) {
                            $legacy->whereNull('business_date')
                                ->whereDate('created_at', $date);
                        });
                })
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
            $activeShift = $this->resolveActiveShift($user->id);

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
                    ->where(function ($q) use ($date) {
                        $q->whereDate('business_date', $date)
                            ->orWhere(function ($legacy) use ($date) {
                                $legacy->whereNull('business_date')
                                    ->whereDate('created_at', $date);
                            });
                    })
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

        $businessDate = $this->resolveWriteBusinessDate($request, $user?->id);
        $entryBusinessDate = $entry->business_date?->toDateString()
            ?? $entry->date?->toDateString()
            ?? $entry->created_at?->toDateString();
        if ($entryBusinessDate !== $businessDate) {
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
        $businessDate = $this->resolveWriteBusinessDate($request, $user?->id);
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
                ->where('date', $businessDate)
                ->first();

            if ($existing) {
                // Merge with existing data
                $existingData = json_decode($existing->reconciliation_data ?? '{}', true);
                $existingData[$paymentType] = $reconciliationData;

                DB::table('daily_sales_reconciliations')
                    ->where('user_id', $user->id)
                    ->where('date', $businessDate)
                    ->update([
                        'reconciled_at' => $reconciledAt,
                        'reconciliation_data' => json_encode($existingData),
                        'total_sales' => Order::query()
                            ->where('created_by', $user->id)
                            ->where('status', 'paid')
                            ->where(function ($q) use ($businessDate) {
                                $q->whereDate('business_date', $businessDate)
                                    ->orWhere(function ($legacy) use ($businessDate) {
                                        $legacy->whereNull('business_date')
                                            ->whereDate('created_at', $businessDate);
                                    });
                            })
                            ->where('created_at', '<=', $reconciledAt)
                            ->sum('total_amount'),
                    ]);
            } else {
                // Create new record
                DB::table('daily_sales_reconciliations')->insert([
                    'user_id' => $user->id,
                    'date' => $businessDate,
                    'reconciled_at' => $reconciledAt,
                    'reconciliation_data' => json_encode([$paymentType => $reconciliationData]),
                    'total_sales' => Order::query()
                        ->where('created_by', $user->id)
                        ->where('status', 'paid')
                        ->where(function ($q) use ($businessDate) {
                            $q->whereDate('business_date', $businessDate)
                                ->orWhere(function ($legacy) use ($businessDate) {
                                    $legacy->whereNull('business_date')
                                        ->whereDate('created_at', $businessDate);
                                });
                        })
                        ->where('created_at', '<=', $reconciledAt)
                        ->sum('total_amount'),
                ]);
            }
        } else {
            // Fallback: use old behavior (single reconciliation for all)
            DB::table('daily_sales_reconciliations')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'date' => $businessDate,
                ],
                [
                    'reconciled_at' => $reconciledAt,
                    'total_sales' => Order::query()
                        ->where('created_by', $user->id)
                        ->where('status', 'paid')
                        ->where(function ($q) use ($businessDate) {
                            $q->whereDate('business_date', $businessDate)
                                ->orWhere(function ($legacy) use ($businessDate) {
                                    $legacy->whereNull('business_date')
                                        ->whereDate('created_at', $businessDate);
                                });
                        })
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
        $businessDate = $this->resolveWriteBusinessDate($request, $user?->id);

        DB::table('daily_sales_reconciliations')
            ->where('user_id', $user->id)
            ->where('date', $businessDate)
            ->delete();

        $basePaidOrders = Order::query()
            ->where('created_by', $user->id)
            ->where('status', 'paid');
        $this->applyOrderBusinessDateFilter($basePaidOrders, $businessDate);

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

        $businessDate = $this->resolveWriteBusinessDate($request, $user?->id);
        $entryBusinessDate = $entry->business_date?->toDateString()
            ?? $entry->date?->toDateString()
            ?? $entry->created_at?->toDateString();
        if ($entryBusinessDate !== $businessDate) {
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

    public function showSavedInventory(Request $request, SavedMoneyInventory $savedInventory): JsonResponse
    {
        $blocked = $this->ensureClockedOut($request);
        if ($blocked) {
            return $blocked;
        }

        $user = $request->user();
        if (! $user || (int) $savedInventory->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Not authorized.',
            ], 403);
        }

        $savedInventory->loadMissing(['user:id,name']);

        return response()->json([
            'saved_inventory' => [
                'id' => (int) $savedInventory->id,
                'date' => $savedInventory->date?->toDateString(),
                'saved_at' => $savedInventory->saved_at?->toIso8601String(),
                'total_sales' => (float) $savedInventory->total_sales,
                'cash_total' => (float) $savedInventory->cash_total,
                'gcash_total' => (float) $savedInventory->gcash_total,
                'total_verified' => (float) $savedInventory->total_verified,
                'difference' => (float) $savedInventory->difference,
                'status' => (string) $savedInventory->status,
                'cash_breakdown' => $savedInventory->cash_breakdown,
                'gcash_details' => $savedInventory->gcash_details,
                'payment_entries' => $savedInventory->payment_entries,
                'user' => $savedInventory->user ? [
                    'name' => (string) $savedInventory->user->name,
                ] : null,
                'staff_name' => $savedInventory->user?->name,
            ],
        ]);
    }
}
