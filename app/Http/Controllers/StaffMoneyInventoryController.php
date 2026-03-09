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
use Illuminate\View\View;

class StaffMoneyInventoryController extends Controller
{
    private const DENOMINATIONS = [1000, 500, 200, 100, 50, 20, 10, 1];

    private const PAYMENT_DENOMINATIONS = [1000, 500, 200, 100, 50, 20, 10, 5, 1];

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

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user && (bool) $user->clocked_in === true) {
            return redirect()->route('profile.edit')->with('status', 'Please Clock Out to access Money Inventory.');
        }

        $date = Carbon::today()->toDateString();

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

        $todaysTotalSales = (float) Order::query()
            ->where('created_by', $user->id)
            ->where('status', 'paid')
            ->whereDate('created_at', $date)
            ->sum('total_amount');

        return view('staff.money-inventory', [
            'date' => $date,
            'denominations' => self::DENOMINATIONS,
            'quantities' => $quantities,
            'todaysTotalSales' => $todaysTotalSales,
            'clockedIn' => (bool) ($user->clocked_in ?? false),
            'paymentDenominations' => self::PAYMENT_DENOMINATIONS,
            'paymentEntries' => PaymentEntry::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $date)
                ->with(['items' => function ($q) {
                    $q->orderByDesc('denomination');
                }])
                ->latest()
                ->get(['id', 'user_id', 'date', 'payment_type', 'received_amount', 'created_at']),
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

        $date = Carbon::today()->toDateString();

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

            MoneyInventory::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $date,
                    'denomination' => $denomination,
                ],
                [
                    'quantity' => $qty,
                ]
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Money inventory saved.',
            ]);
        }

        return redirect()->route('staff.money-inventory.index')->with('status', 'Money inventory saved.');
    }

    public function storePaymentEntry(Request $request): JsonResponse
    {
        $blocked = $this->ensureClockedOut($request);
        if ($blocked) {
            return $blocked;
        }

        $user = $request->user();
        $date = Carbon::today()->toDateString();

        $validated = $request->validate([
            'payment_type' => ['required', 'string', 'in:cash,gcash'],
            'received_amount' => ['nullable', 'numeric', 'min:0'],
            'breakdown' => ['nullable', 'array'],
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

        $entry = DB::transaction(function () use ($user, $date, $paymentType, $receivedAmount, $cleanBreakdown) {
            $entry = PaymentEntry::create([
                'user_id' => $user->id,
                'date' => $date,
                'payment_type' => $paymentType,
                'received_amount' => $receivedAmount,
            ]);

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
}
