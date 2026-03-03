<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
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

            return [
                'date' => (string) $g->order_date,
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
            ->whereDate('created_at', $date)
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
            ->get(['id', 'order_number', 'total', 'total_amount', 'payment_type', 'cash_received', 'change_amount', 'status', 'created_at', 'created_by']);

        $totalOrders = $orders->count();
        $totalSales = (float) $orders->sum(fn (Order $o) => (float) ($o->total_amount ?? $o->total ?? 0));
        $totalItems = (int) $orders
            ->flatMap(fn (Order $o) => $o->items)
            ->sum('quantity');

        return view('admin.orders.details', [
            'date' => $date,
            'staff' => $staff,
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'totalItems' => $totalItems,
            'totalSales' => $totalSales,
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
            ->whereDate('created_at', $date)
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
            ->get(['id', 'order_number', 'total', 'total_amount', 'payment_type', 'cash_received', 'change_amount', 'status', 'created_at', 'created_by']);

        $totalOrders = $orders->count();
        $totalSales = (float) $orders->sum(fn (Order $o) => (float) ($o->total_amount ?? $o->total ?? 0));
        $totalItems = (int) $orders
            ->flatMap(fn (Order $o) => $o->items->whereNull('deleted_at'))
            ->sum('quantity');

        return response()->json([
            'date' => $date,
            'staff' => [
                'id' => (int) $staff->id,
                'name' => (string) $staff->name,
            ],
            'summary' => [
                'total_orders' => (int) $totalOrders,
                'total_items' => (int) $totalItems,
                'total_sales' => (float) $totalSales,
            ],
            'orders' => $orders->map(function (Order $o) {
                $total = (float) ($o->total_amount ?? $o->total ?? 0);
                return [
                    'id' => (int) $o->id,
                    'order_number' => (string) $o->order_number,
                    'created_at' => $o->created_at?->format('Y-m-d H:i') ?? null,
                    'status' => (string) $o->status,
                    'payment_type' => (string) ($o->payment_type ?? ''),
                    'cash_received' => $o->cash_received !== null ? (float) $o->cash_received : null,
                    'change_amount' => $o->change_amount !== null ? (float) $o->change_amount : null,
                    'total' => $total,
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
                            'deleted_at' => $i->deleted_at?->format('Y-m-d H:i'),
                        ];
                    })->values()->all(),
                    'activities' => $o->activities->map(function ($a) {
                        return [
                            'id' => (int) $a->id,
                            'created_at' => $a->created_at?->format('Y-m-d H:i') ?? null,
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
}
