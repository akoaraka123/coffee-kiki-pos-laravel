<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\MoneyInventory;
use App\Models\Order;
use App\Models\PaymentEntry;
use App\Models\PaymentEntryItem;
use App\Models\SavedMoneyInventory;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    private const DISPLAY_TIMEZONE = 'Asia/Manila';

    private function formatHumanDate(?Carbon $dt): ?string
    {
        return $dt ? $dt->copy()->setTimezone(self::DISPLAY_TIMEZONE)->format('F j, Y (l)') : null;
    }

    private function formatHumanDateTime(?Carbon $dt): ?string
    {
        return $dt ? $dt->copy()->setTimezone(self::DISPLAY_TIMEZONE)->format('F j, Y (l) – g:i A') : null;
    }

    public function index(Request $request): View
    {
        $staffId = $request->string('staff')->toString();
        $staffId = $staffId !== '' ? $staffId : null;

        $summary = $request->string('summary')->toString();
        $summary = $summary !== '' ? $summary : null;

        $staffUsers = User::query()
            ->where('role', 'staff')
            ->orderBy('name')
            ->get(['id', 'name']);

        $baseQuery = Order::query()->latest();
        if ($staffId) {
            $baseQuery->where('created_by', $staffId);
        }

        $todaySales = null;
        if ($summary === 'today') {
            $start = Carbon::today();
            $end = Carbon::tomorrow();

            $salesQuery = Order::query()
                ->where('status', 'paid')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end);

            if ($staffId) {
                $salesQuery->where('created_by', $staffId);
            }

            $todaySales = (float) $salesQuery->sum('total');
        }

        $groupsQuery = Order::query();
        if ($staffId) {
            $groupsQuery->where('created_by', $staffId);
        }

        $groups = $groupsQuery
            ->selectRaw("DATE(created_at) as order_date, created_by, COUNT(*) as total_orders, SUM(COALESCE(total_amount, total)) as total_sales")
            ->groupBy('order_date', 'created_by')
            ->orderByDesc('order_date')
            ->get();

        $staffMap = $staffUsers->keyBy(fn (User $u) => (string) $u->id);

        $keys = $groups
            ->map(fn ($g) => (string) $g->order_date . '||' . (string) $g->created_by)
            ->values();

        $itemsByKey = collect();
        if ($keys->count() > 0) {
            $itemsAggQuery = Order::query()
                ->join('order_items', 'orders.id', '=', 'order_items.order_id');

            if ($staffId) {
                $itemsAggQuery->where('orders.created_by', $staffId);
            }

            $itemsAgg = $itemsAggQuery
                ->selectRaw("DATE(orders.created_at) as order_date, orders.created_by, SUM(order_items.quantity) as total_items")
                ->groupBy('order_date', 'orders.created_by')
                ->get();

            $itemsByKey = $itemsAgg->mapWithKeys(function ($row) {
                $key = (string) $row->order_date . '||' . (string) $row->created_by;
                return [$key => (int) ($row->total_items ?? 0)];
            });
        }

        $mapped = $groups->map(function ($g) use ($staffMap, $itemsByKey) {
            $key = (string) $g->order_date . '||' . (string) $g->created_by;
            $staff = $staffMap->get((string) $g->created_by);

            $dateRaw = (string) $g->order_date;
            $dateDisplay = $dateRaw;
            try {
                $dateDisplay = Carbon::parse($dateRaw)->format('F j, Y (l)');
            } catch (\Throwable $e) {
                $dateDisplay = $dateRaw;
            }

            return [
                'date' => $dateRaw,
                'date_display' => $dateDisplay,
                'staff_id' => (string) $g->created_by,
                'staff_name' => $staff?->name ?? '—',
                'total_orders' => (int) ($g->total_orders ?? 0),
                'total_items' => (int) ($itemsByKey->get($key, 0)),
                'total_sales' => (float) ($g->total_sales ?? 0),
            ];
        });

        $perPage = 10;
        $page = max((int) $request->query('page', 1), 1);
        $paged = $mapped->slice(($page - 1) * $perPage, $perPage)->values();
        $summaries = new LengthAwarePaginator(
            $paged,
            $mapped->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.orders.index', [
            'summaries' => $summaries,
            'staffUsers' => $staffUsers,
            'staffId' => $staffId,
            'summary' => $summary,
            'todaySales' => $todaySales,
        ]);
    }

    public function details(Request $request): View
    {
        $staffId = $request->string('staff')->toString();
        $date = $request->string('date')->toString();

        $request->validate([
            'staff' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $staff = User::query()
            ->where('role', 'staff')
            ->findOrFail($staffId, ['id', 'name']);

        $orders = Order::query()
            ->where('created_by', $staff->id)
            ->where(function ($q) use ($date) {
                // Filter by business_date if available, otherwise fallback to created_at
                $q->whereDate('business_date', $date)
                  ->orWhereDate('created_at', $date);
            })
            ->with([
                'items' => function ($q) {
                    $q->withTrashed()->select(['id', 'order_id', 'product_id', 'name', 'price', 'quantity', 'line_total', 'deleted_at']);
                },
                'items.product:id,size',
            ])
            ->withCount([
                'activities as activity_count',
                'activities as item_deleted_count' => function ($q) {
                    $q->where('action', 'item_deleted');
                },
                'activities as item_edited_count' => function ($q) {
                    $q->where('action', 'item_edited');
                },
            ])
            ->orderBy('created_at')
            ->get(['id', 'order_number', 'total', 'total_amount', 'payment_type', 'cash_received', 'change_amount', 'status', 'created_at', 'created_by', 'gcash_reference', 'gcash_sender_name', 'gcash_sender_mobile', 'gcash_proof_image', 'shift_id', 'business_date']);

        $totalOrders = $orders->count();
        $totalSales = (float) $orders->sum(fn (Order $o) => (float) ($o->total_amount ?? $o->total ?? 0));
        $totalItems = (int) $orders
            ->flatMap(fn (Order $o) => $o->items)
            ->sum('quantity');

        $dateDisplay = $date;
        try {
            $dateDisplay = Carbon::parse($date)->format('F j, Y (l)');
        } catch (\Throwable $e) {
            $dateDisplay = $date;
        }

        // Calculate cash and GCash sales totals
        $paidOrders = $orders->where('status', 'paid');
        $cashSalesTotal = (float) $paidOrders
            ->where('payment_type', 'cash')
            ->sum(fn (Order $o) => (float) ($o->total_amount ?? $o->total ?? 0));
        $gcashSalesTotal = (float) $paidOrders
            ->where('payment_type', 'gcash')
            ->sum(fn (Order $o) => (float) ($o->total_amount ?? $o->total ?? 0));

        // Fetch Money Inventory data for the staff and date
        $moneyInventory = MoneyInventory::query()
            ->where('user_id', $staff->id)
            ->where('date', $date)
            ->first();

        $countedCashTotal = 0;
        $denominationBreakdown = [];
        if ($moneyInventory) {
            $countedCashTotal = $moneyInventory->quantities ? collect($moneyInventory->quantities)->reduce(function ($sum, $qty, $denom) {
                return $sum + ($denom * $qty);
            }, 0) : 0;
            $denominationBreakdown = $moneyInventory->quantities ?? [];
        }

        // Fetch payment entries for verification
        $paymentEntries = PaymentEntry::query()
            ->where('user_id', $staff->id)
            ->where('date', $date)
            ->with('items')
            ->get();

        $verifiedGcashTotal = (float) $paymentEntries
            ->where('payment_type', 'gcash')
            ->sum('received_amount');

        $cashDifference = $countedCashTotal - $cashSalesTotal;
        $gcashDifference = $verifiedGcashTotal - $gcashSalesTotal;
        $totalDifference = $cashDifference + $gcashDifference;

        if ($totalDifference == 0) {
            $reconciliationStatus = 'balanced';
        } elseif ($totalDifference < 0) {
            $reconciliationStatus = 'short';
        } else {
            $reconciliationStatus = 'over';
        }

        return view('admin.orders.details', [
            'date' => $date,
            'dateDisplay' => $dateDisplay,
            'staff' => $staff,
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'totalItems' => $totalItems,
            'totalSales' => $totalSales,
            'cashSalesTotal' => $cashSalesTotal,
            'gcashSalesTotal' => $gcashSalesTotal,
            'countedCashTotal' => $countedCashTotal,
            'verifiedGcashTotal' => $verifiedGcashTotal,
            'denominationBreakdown' => $denominationBreakdown,
            'paymentEntries' => $paymentEntries,
            'cashDifference' => $cashDifference,
            'gcashDifference' => $gcashDifference,
            'totalDifference' => $totalDifference,
            'reconciliationStatus' => $reconciliationStatus,
        ]);
    }

    public function detailsJson(Request $request): JsonResponse
    {
        $staffId = $request->string('staff')->toString();
        $date = $request->string('date')->toString();

        $request->validate([
            'staff' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $staff = User::query()
            ->where('role', 'staff')
            ->findOrFail($staffId, ['id', 'name']);

        $orders = Order::query()
            ->where('created_by', $staff->id)
            ->where(function ($q) use ($date) {
                // Filter by business_date if available, otherwise fallback to created_at
                $q->whereDate('business_date', $date)
                  ->orWhereDate('created_at', $date);
            })
            ->with([
                'items' => function ($q) {
                    $q->withTrashed()->select(['id', 'order_id', 'product_id', 'name', 'price', 'quantity', 'line_total', 'deleted_at']);
                },
                'items.product:id,size',
                'activities' => function ($q) {
                    $q->with('actor:id,name')->orderBy('created_at');
                },
            ])
            ->withCount([
                'activities as item_deleted_count' => function ($q) {
                    $q->where('action', 'item_deleted');
                },
                'activities as item_edited_count' => function ($q) {
                    $q->where('action', 'item_edited');
                },
            ])
            ->orderBy('created_at')
            ->get(['id', 'order_number', 'total', 'total_amount', 'payment_type', 'cash_received', 'change_amount', 'status', 'created_at', 'created_by', 'gcash_reference', 'gcash_sender_name', 'gcash_sender_mobile', 'gcash_proof_image', 'shift_id', 'business_date']);

        $totalOrders = $orders->count();
        $totalSales = (float) $orders->sum(fn (Order $o) => (float) ($o->total_amount ?? $o->total ?? 0));
        $totalItems = (int) $orders
            ->flatMap(fn (Order $o) => $o->items->whereNull('deleted_at'))
            ->sum('quantity');

        $paidOrders = $orders->where('status', 'paid');
        $cashSalesTotal = (float) $paidOrders
            ->where('payment_type', 'cash')
            ->sum(fn (Order $o) => (float) ($o->total_amount ?? $o->total ?? 0));
        $gcashSalesTotal = (float) $paidOrders
            ->where('payment_type', 'gcash')
            ->sum(fn (Order $o) => (float) ($o->total_amount ?? $o->total ?? 0));
        $paidSalesTotal = (float) $paidOrders
            ->sum(fn (Order $o) => (float) ($o->total_amount ?? $o->total ?? 0));

        $dateDisplay = $date;
        try {
            $dateDisplay = Carbon::parse($date)->format('F j, Y (l)');
        } catch (\Throwable $e) {
            $dateDisplay = $date;
        }

        $moneyInventoryRows = MoneyInventory::query()
            ->where('user_id', $staff->id)
            ->where(function ($q) use ($date) {
                // Filter by business_date if available, otherwise fallback to date
                $q->whereDate('business_date', $date)
                  ->orWhereDate('date', $date);
            })
            ->orderByDesc('denomination')
            ->get(['denomination', 'quantity']);

        // Load Cash entries from PaymentEntry table to get the denomination breakdown
        $cashPaymentEntries = PaymentEntry::query()
            ->where('user_id', $staff->id)
            ->where(function ($q) use ($date) {
                // Filter by business_date if available, otherwise fallback to date
                $q->whereDate('business_date', $date)
                  ->orWhereDate('date', $date);
            })
            ->where('payment_type', 'cash')
            ->with(['items' => function ($q) {
                $q->orderByDesc('denomination');
            }])
            ->get(['id', 'received_amount']);

        // Aggregate denomination breakdown from all cash payment entries
        $cashDenominationBreakdown = [];
        $cashTotal = 0;

        foreach ($cashPaymentEntries as $entry) {
            $cashTotal += (int) $entry->received_amount;
            foreach ($entry->items as $item) {
                $denom = (int) $item->denomination;
                $qty = (int) $item->quantity;
                if (!isset($cashDenominationBreakdown[$denom])) {
                    $cashDenominationBreakdown[$denom] = 0;
                }
                $cashDenominationBreakdown[$denom] += $qty;
            }
        }

        // Convert to array format for the response
        $cashBreakdownArray = [];
        foreach ($cashDenominationBreakdown as $denom => $qty) {
            if ($qty > 0) {
                $cashBreakdownArray[] = [
                    'denomination' => $denom,
                    'quantity' => $qty,
                    'subtotal' => $denom * $qty,
                ];
            }
        }

        // Sort by denomination descending
        usort($cashBreakdownArray, function ($a, $b) {
            return $b['denomination'] <=> $a['denomination'];
        });

        // Use cash payment entry data if available, otherwise fall back to MoneyInventory
        if ($cashTotal > 0) {
            $moneyInventoryTotal = $cashTotal;
            $moneyInventoryBreakdown = $cashBreakdownArray;
        } else {
            $moneyInventoryTotal = (int) $moneyInventoryRows
                ->sum(fn (MoneyInventory $r) => ((int) $r->denomination) * ((int) $r->quantity));
            $moneyInventoryBreakdown = $moneyInventoryRows->map(fn (MoneyInventory $r) => [
                'denomination' => (int) $r->denomination,
                'quantity' => (int) $r->quantity,
                'subtotal' => (int) $r->denomination * (int) $r->quantity,
            ])->values()->all();
        }

        $paymentEntries = PaymentEntry::query()
            ->where('user_id', $staff->id)
            ->where(function ($q) use ($date) {
                // Filter by business_date if available, otherwise fallback to date
                $q->whereDate('business_date', $date)
                  ->orWhereDate('date', $date);
            })
            ->with(['items' => function ($q) {
                $q->orderByDesc('denomination');
            }, 'order', 'verifiedByUser:id,name'])
            ->latest()
            ->get(['id', 'payment_type', 'received_amount', 'order_id', 'gcash_sender_name', 'gcash_reference_number', 'gcash_sender_mobile', 'gcash_proof_image', 'verified_at', 'verified_by', 'created_at', 'shift_id', 'business_date']);

        $cashPaymentsTotal = (int) $paymentEntries
            ->where('payment_type', 'cash')
            ->sum(fn (PaymentEntry $e) => (int) $e->received_amount);

        $gcashPaymentsTotal = (int) $paymentEntries
            ->where('payment_type', 'gcash')
            ->sum(fn (PaymentEntry $e) => (int) $e->received_amount);

        // Load sales session information for the staff and date
        $salesSession = Shift::where('user_id', $staff->id)
            ->where('business_date', $date)
            ->first(['shift_id', 'started_at', 'ended_at', 'status', 'opening_cash', 'closing_cash']);

        $savedInventoryRows = SavedMoneyInventory::query()
            ->where('user_id', $staff->id)
            ->where(function ($q) use ($date) {
                $q->whereDate('business_date', $date)
                    ->orWhereDate('date', $date);
            })
            ->with(['user:id,name'])
            ->latest('saved_at')
            ->get();

        return response()->json([
            'staff' => [
                'id' => (int) $staff->id,
                'name' => (string) $staff->name,
            ],
            'date' => $date,
            'date_display' => $dateDisplay,
            'sales_session' => $salesSession ? [
                'session_id' => (string) $salesSession->shift_id,
                'started_at' => $salesSession->started_at ? $this->formatHumanDateTime(Carbon::instance($salesSession->started_at)) : null,
                'ended_at' => $salesSession->ended_at ? $this->formatHumanDateTime(Carbon::instance($salesSession->ended_at)) : null,
                'status' => (string) $salesSession->status,
                'opening_cash' => (float) ($salesSession->opening_cash ?? 0),
                'closing_cash' => (float) ($salesSession->closing_cash ?? 0),
            ] : null,
            'orders_summary' => [
                'total_orders' => (int) $totalOrders,
                'total_items' => (int) $totalItems,
                'total_sales' => (float) $totalSales,
                'paid_sales' => [
                    'cash' => (float) $cashSalesTotal,
                    'gcash' => (float) $gcashSalesTotal,
                    'overall' => (float) $paidSalesTotal,
                ],
            ],
            'money_inventory' => [
                'total_cash' => $moneyInventoryTotal,
                'breakdown' => $moneyInventoryBreakdown,
            ],
            'payment_inventory' => [
                'totals' => [
                    'cash' => (int) $cashPaymentsTotal,
                    'gcash' => (int) $gcashPaymentsTotal,
                    'overall' => (int) ($cashPaymentsTotal + $gcashPaymentsTotal),
                ],
                'entries' => $paymentEntries->map(function (PaymentEntry $e) {
                    $gcashProofImageUrl = null;
                    if ($e->gcash_proof_image) {
                        $gcashProofImageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($e->gcash_proof_image);
                    }

                    return [
                        'id' => (int) $e->id,
                        'payment_type' => (string) $e->payment_type,
                        'received_amount' => (int) $e->received_amount,
                        'order_id' => (int) $e->order_id,
                        'order_number' => $e->order ? (string) $e->order->order_number : null,
                        'gcash_sender_name' => $e->gcash_sender_name,
                        'gcash_reference_number' => $e->gcash_reference_number,
                        'gcash_sender_mobile' => $e->gcash_sender_mobile,
                        'gcash_proof_image' => $gcashProofImageUrl,
                        'verified_at' => $e->verified_at ? $this->formatHumanDateTime(Carbon::instance($e->verified_at)) : null,
                        'verified_by' => $e->verifiedByUser ? (string) $e->verifiedByUser->name : null,
                        'created_at' => $this->formatHumanDateTime($e->created_at ? Carbon::instance($e->created_at) : null),
                        'created_at_raw' => $e->created_at?->toIso8601String(),
                        'items' => $e->items->map(fn (PaymentEntryItem $i) => [
                            'denomination' => (int) $i->denomination,
                            'quantity' => (int) $i->quantity,
                            'subtotal' => (int) $i->denomination * (int) $i->quantity,
                        ])->values()->all(),
                    ];
                })->values()->all(),
            ],
            'saved_money_inventories' => $savedInventoryRows->map(function (SavedMoneyInventory $s) {
                return [
                    'id' => (int) $s->id,
                    'date' => $s->date?->toDateString(),
                    'saved_at' => $s->saved_at?->toIso8601String(),
                    'saved_at_display' => $this->formatHumanDateTime($s->saved_at ? Carbon::instance($s->saved_at) : null),
                    'staff_name' => $s->user?->name,
                    'total_sales' => (float) $s->total_sales,
                    'cash_total' => (float) $s->cash_total,
                    'gcash_total' => (float) $s->gcash_total,
                    'total_verified' => (float) $s->total_verified,
                    'difference' => (float) $s->difference,
                    'status' => (string) $s->status,
                    'cash_breakdown' => is_array($s->cash_breakdown) ? $s->cash_breakdown : [],
                    'gcash_details' => is_array($s->gcash_details) ? $s->gcash_details : [],
                    'payment_entries' => is_array($s->payment_entries) ? $s->payment_entries : [],
                ];
            })->values()->all(),
            'orders' => $orders->map(function (Order $o) {
                $total = (float) ($o->total_amount ?? $o->total ?? 0);
                $gcashProofImageUrl = null;
                if ($o->gcash_proof_image) {
                    $gcashProofImageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($o->gcash_proof_image);
                }
                return [
                    'id' => (int) $o->id,
                    'order_number' => (string) $o->order_number,
                    'created_at' => $this->formatHumanDateTime($o->created_at ? Carbon::instance($o->created_at) : null),
                    'created_at_raw' => $o->created_at?->toIso8601String(),
                    'status' => (string) $o->status,
                    'payment_type' => (string) ($o->payment_type ?? ''),
                    'cash_received' => $o->cash_received !== null ? (float) $o->cash_received : null,
                    'change_amount' => $o->change_amount !== null ? (float) $o->change_amount : null,
                    'total' => $total,
                    'gcash_reference' => $o->gcash_reference,
                    'gcash_sender_name' => $o->gcash_sender_name,
                    'gcash_sender_mobile' => $o->gcash_sender_mobile,
                    'gcash_proof_image' => $gcashProofImageUrl,
                    'item_edited_count' => (int) ($o->item_edited_count ?? 0),
                    'item_deleted_count' => (int) ($o->item_deleted_count ?? 0),
                    'items' => $o->items->map(function ($i) {
                        return [
                            'id' => (int) $i->id,
                            'name' => (string) $i->name,
                            'size' => $i->product?->size,
                            'quantity' => (int) $i->quantity,
                            'price' => (float) $i->price,
                            'line_total' => (float) $i->line_total,
                            'deleted_at' => $this->formatHumanDateTime($i->deleted_at ? Carbon::instance($i->deleted_at) : null),
                            'deleted_at_raw' => $i->deleted_at?->toIso8601String(),
                        ];
                    })->values()->all(),
                    'activities' => $o->activities->map(function ($a) {
                        return [
                            'id' => (int) $a->id,
                            'created_at' => $this->formatHumanDateTime($a->created_at ? Carbon::instance($a->created_at) : null),
                            'created_at_raw' => $a->created_at?->toIso8601String(),
                            'actor_name' => $a->actor?->name,
                            'action' => (string) $a->action,
                            'note' => $a->note,
                            'meta' => $a->meta,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'creator:id,name',
            'items' => function ($q) {
                $q->withTrashed()->select(['id', 'order_id', 'product_id', 'name', 'price', 'quantity', 'line_total', 'deleted_at']);
            },
            'items.product:id,size',
            'activities' => function ($q) {
                $q->with('actor:id,name')->orderBy('created_at');
            },
        ]);

        $hasEdits = $order->activities->contains(fn ($a) => $a->action === 'item_edited');
        $hasDeletes = $order->activities->contains(fn ($a) => $a->action === 'item_deleted');

        return view('admin.orders.show', [
            'order' => $order,
            'hasEdits' => $hasEdits,
            'hasDeletes' => $hasDeletes,
        ]);
    }

    public function destroy(Order $order): JsonResponse
    {
        DB::transaction(function () use ($order) {
            // Restore inventory stock for deleted order items
            $items = $order->items()->withTrashed()->get();
            foreach ($items as $item) {
                $inventory = Inventory::where('product_id', $item->product_id)->first();
                if ($inventory) {
                    $inventory->stock_quantity += $item->quantity;
                    $inventory->save();
                }
            }

            // Delete order activities
            $order->activities()->delete();

            // Delete order items
            $order->items()->delete();

            // Delete the order
            $order->delete();
        });

        return response()->json([
            'message' => 'Order deleted successfully.',
        ]);
    }

    public function deleteDailySales(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff' => ['nullable', 'integer', 'exists:users,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $staffId = $validated['staff'] ?? null;
        $date = $validated['date'];

        $deletedOrders = 0;
        $deletedPaymentEntries = 0;
        $deletedReconciliations = 0;

        DB::transaction(function () use ($staffId, $date, &$deletedOrders, &$deletedPaymentEntries, &$deletedReconciliations) {
            // Delete orders for the business date with fallback to created_at
            $orderQuery = Order::query()
                ->where(function ($q) use ($date) {
                    // Filter by business_date if available, otherwise fallback to created_at date
                    $q->where('business_date', $date)
                      ->orWhereDate('created_at', $date);
                });

            if ($staffId) {
                $orderQuery->where('created_by', $staffId);
            }

            $orders = $orderQuery->get(['id']);
            foreach ($orders as $order) {
                // Restore inventory stock for deleted order items
                $items = $order->items()->withTrashed()->get();
                foreach ($items as $item) {
                    $inventory = Inventory::where('product_id', $item->product_id)->first();
                    if ($inventory) {
                        $inventory->stock_quantity += $item->quantity;
                        $inventory->save();
                    }
                }
                
                // Delete order activities
                $order->activities()->delete();
                
                // Delete order items
                $order->items()->delete();
                
                // Delete the order
                $order->delete();
                $deletedOrders++;
            }

            // Delete payment entries for the business date with fallback to date
            $paymentEntryQuery = \App\Models\PaymentEntry::query()
                ->where(function ($q) use ($date) {
                    // Filter by business_date if available, otherwise fallback to date
                    $q->where('business_date', $date)
                      ->orWhereDate('date', $date);
                });

            if ($staffId) {
                $paymentEntryQuery->where('user_id', $staffId);
            }

            $paymentEntries = $paymentEntryQuery->get(['id']);
            foreach ($paymentEntries as $entry) {
                $entry->delete();
                $deletedPaymentEntries++;
            }

            // Delete daily sales reconciliation records for the date
            $reconciliationQuery = DB::table('daily_sales_reconciliations')
                ->where('date', $date);

            if ($staffId) {
                $reconciliationQuery->where('user_id', $staffId);
            }

            $deletedReconciliations = $reconciliationQuery->delete();
        });

        return response()->json([
            'message' => "Deleted {$deletedOrders} orders, {$deletedPaymentEntries} payment entries, and {$deletedReconciliations} reconciliation records.",
            'deleted_orders' => $deletedOrders,
            'deleted_payment_entries' => $deletedPaymentEntries,
            'deleted_reconciliations' => $deletedReconciliations,
        ]);
    }
}
