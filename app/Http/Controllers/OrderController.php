<?php

namespace App\Http\Controllers;

use App\Models\OrderActivity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $userId = $user?->id;

        $selectedDate = $request->string('date')->toString();
        $selectedDate = $selectedDate !== '' ? $selectedDate : null;
        if ($selectedDate) {
            $request->validate([
                'date' => ['date_format:Y-m-d'],
            ]);
        }

        $ordersQuery = Order::query()->latest();
        if ($userId) {
            $ordersQuery->where('created_by', $userId);
        }
        if ($selectedDate) {
            $ordersQuery->whereDate('created_at', $selectedDate);
        }

        $start = Carbon::today();
        $end = Carbon::tomorrow();

        $todaySales = 0.0;
        $todayOrders = 0;
        if ($userId) {
            $todaySales = (float) Order::query()
                ->where('created_by', $userId)
                ->where('status', 'paid')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->sum('total');

            $todayOrders = (int) Order::query()
                ->where('created_by', $userId)
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->count();
        }

        $dailySummaries = collect();
        if ($userId) {
            $groups = Order::query()
                ->where('created_by', $userId)
                ->selectRaw("DATE(created_at) as order_date, COUNT(*) as total_orders, SUM(COALESCE(total_amount, total)) as total_sales")
                ->groupBy('order_date')
                ->orderByDesc('order_date')
                ->limit(14)
                ->get();

            $dates = $groups->pluck('order_date')->map(fn ($d) => (string) $d)->values();

            $itemsByDate = collect();
            if ($dates->count() > 0) {
                $itemsAgg = Order::query()
                    ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.created_by', $userId)
                    ->whereNull('order_items.deleted_at')
                    ->whereIn(DB::raw('DATE(orders.created_at)'), $dates)
                    ->selectRaw("DATE(orders.created_at) as order_date, SUM(order_items.quantity) as total_items")
                    ->groupBy('order_date')
                    ->get();

                $itemsByDate = $itemsAgg->mapWithKeys(fn ($r) => [(string) $r->order_date => (int) ($r->total_items ?? 0)]);
            }

            $dailySummaries = $groups->map(function ($g) use ($itemsByDate) {
                $date = (string) $g->order_date;
                return [
                    'date' => $date,
                    'total_orders' => (int) ($g->total_orders ?? 0),
                    'total_items' => (int) ($itemsByDate->get($date, 0)),
                    'total_sales' => (float) ($g->total_sales ?? 0),
                ];
            });
        }

        return view('orders.index', [
            'orders' => $ordersQuery->paginate(10),
            'todaySales' => $todaySales,
            'todayOrders' => $todayOrders,
            'dailySummaries' => $dailySummaries,
            'selectedDate' => $selectedDate,
        ]);
    }

    public function create(): Response
    {
        return $this->pos();
    }

    public function pos(): Response
    {
        $user = request()->user();
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->orderBy('size')
            ->get(['id', 'name', 'price', 'category', 'size', 'image']);

        return response()->view('orders.create', [
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:pending,paid,cancelled'],
            'items' => ['required', 'string'],
            'payment_type' => ['required', 'string', 'in:cash,gcash'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $items = json_decode($validated['items'], true);

        if (! is_array($items) || count($items) === 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'items' => ['Please add at least 1 item to the order.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'items' => 'Please add at least 1 item to the order.',
            ])->withInput();
        }

        $productIds = collect($items)
            ->pluck('product_id')
            ->filter(fn ($id) => is_int($id) || ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get(['id', 'name', 'price']);

        $itemsToInsert = [];
        $total = 0;

        foreach ($items as $raw) {
            $productId = isset($raw['product_id']) ? (int) $raw['product_id'] : 0;
            $qty = isset($raw['quantity']) ? (int) $raw['quantity'] : 0;

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $product = $products->firstWhere('id', $productId);

            if (! $product) {
                continue;
            }

            $price = (float) $product->price;
            $lineTotal = $price * $qty;
            $total += $lineTotal;

            $itemsToInsert[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $qty,
                'line_total' => $lineTotal,
            ];
        }

        if (count($itemsToInsert) === 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'items' => ['No valid items found. Please try again.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'items' => 'No valid items found. Please try again.',
            ])->withInput();
        }

        $paymentType = $validated['payment_type'];
        $cashReceived = isset($validated['cash_received']) ? (float) $validated['cash_received'] : null;
        $changeAmount = 0.0;

        if ($paymentType === 'cash') {
            $cash = (float) ($cashReceived ?? 0);
            if ($cash <= 0) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'cash_received' => ['Please enter cash received.'],
                        ],
                    ], 422);
                }

                return back()->withErrors([
                    'cash_received' => 'Please enter cash received.',
                ])->withInput();
            }

            if ($cash < $total) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'cash_received' => ['Insufficient payment amount.'],
                        ],
                    ], 422);
                }

                return back()->withErrors([
                    'cash_received' => 'Insufficient payment amount.',
                ])->withInput();
            }

            $changeAmount = $cash - $total;
        }

        $order = null;

        DB::transaction(function () use ($request, $validated, $total, $itemsToInsert, $paymentType, $cashReceived, $changeAmount, &$order): void {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $validated['customer_name'] ?? null,
                'total_amount' => $total,
                'total' => $total,
                'status' => $validated['status'],
                'payment_type' => $paymentType,
                'cash_received' => $paymentType === 'cash' ? $cashReceived : null,
                'change_amount' => $paymentType === 'cash' ? $changeAmount : 0,
                'created_by' => $request->user()->id,
            ]);

            foreach ($itemsToInsert as $row) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $row['product_id'],
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'quantity' => $row['quantity'],
                    'line_total' => $row['line_total'],
                ]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Order completed successfully.',
                'order_number' => $order?->order_number,
            ]);
        }

        return redirect()->route('orders.index')->with('status', "Order {$order->order_number} created.");
    }

    public function details(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if (! $user || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            abort(403);
        }

        if ((int) $order->created_by !== (int) $user->id) {
            abort(403);
        }

        $order->load([
            'creator:id,name',
            'activeItems:id,order_id,product_id,name,price,quantity,line_total',
            'activeItems.product:id,size',
        ]);

        return response()->json([
            'order' => $this->orderToPayload($order),
        ]);
    }

    public function updateItem(Request $request, Order $order, OrderItem $item): JsonResponse
    {
        $user = $request->user();
        if (! $user || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            abort(403);
        }

        if ((int) $order->created_by !== (int) $user->id) {
            abort(403);
        }

        if ((int) $item->order_id !== (int) $order->id) {
            abort(404);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'message' => 'This order is locked and cannot be modified.',
            ], 422);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $before = [
            'quantity' => (int) $item->quantity,
            'price' => (float) $item->price,
            'line_total' => (float) $item->line_total,
        ];

        $newQty = (int) $validated['quantity'];
        $newPrice = (float) $item->price;
        $newLineTotal = $newQty * $newPrice;

        $result = null;

        DB::transaction(function () use ($order, $item, $newQty, $newPrice, $newLineTotal, $before, $validated, $user, &$result): void {
            $order->refresh();
            $orderTotalBefore = (float) ($order->total_amount ?? $order->total ?? 0);

            $item->quantity = $newQty;
            $item->price = $newPrice;
            $item->line_total = $newLineTotal;
            $item->save();

            $this->recalculateOrderTotals($order);

            $orderTotalAfter = (float) ($order->total_amount ?? $order->total ?? 0);

            OrderActivity::create([
                'order_id' => $order->id,
                'actor_id' => $user->id,
                'action' => 'item_edited',
                'meta' => [
                    'order_item_id' => $item->id,
                    'item_name' => (string) $item->name,
                    'before' => $before,
                    'after' => [
                        'quantity' => (int) $item->quantity,
                        'price' => (float) $item->price,
                        'line_total' => (float) $item->line_total,
                    ],
                    'order_total_before' => $orderTotalBefore,
                    'order_total_after' => $orderTotalAfter,
                ],
                'note' => $validated['note'] ?? null,
            ]);

            $order->load([
                'creator:id,name',
                'activeItems:id,order_id,product_id,name,price,quantity,line_total',
                'activeItems.product:id,size',
            ]);

            $result = [
                'message' => 'Item updated.',
                'order' => $this->orderToPayload($order),
            ];
        });

        return response()->json($result);
    }

    public function deleteItem(Request $request, Order $order, OrderItem $item): JsonResponse
    {
        $user = $request->user();
        if (! $user || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            abort(403);
        }

        if ((int) $order->created_by !== (int) $user->id) {
            abort(403);
        }

        if ((int) $item->order_id !== (int) $order->id) {
            abort(404);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'message' => 'This order is locked and cannot be modified.',
            ], 422);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $before = [
            'quantity' => (int) $item->quantity,
            'price' => (float) $item->price,
            'line_total' => (float) $item->line_total,
        ];

        $result = null;

        DB::transaction(function () use ($order, $item, $before, $validated, $user, &$result): void {
            $order->refresh();
            $orderTotalBefore = (float) ($order->total_amount ?? $order->total ?? 0);

            $item->delete();

            $this->recalculateOrderTotals($order);

            $orderTotalAfter = (float) ($order->total_amount ?? $order->total ?? 0);

            OrderActivity::create([
                'order_id' => $order->id,
                'actor_id' => $user->id,
                'action' => 'item_deleted',
                'meta' => [
                    'order_item_id' => $item->id,
                    'item_name' => (string) $item->name,
                    'before' => $before,
                    'after' => null,
                    'order_total_before' => $orderTotalBefore,
                    'order_total_after' => $orderTotalAfter,
                ],
                'note' => $validated['note'] ?? null,
            ]);

            $activeItemsCount = (int) $order->activeItems()->count();
            if ($activeItemsCount === 0) {
                $order->status = 'cancelled';
                $order->save();

                OrderActivity::create([
                    'order_id' => $order->id,
                    'actor_id' => $user->id,
                    'action' => 'order_voided',
                    'meta' => [
                        'reason' => 'All items were deleted from the order.',
                    ],
                    'note' => $validated['note'] ?? null,
                ]);
            }

            $order->load([
                'creator:id,name',
                'activeItems:id,order_id,product_id,name,price,quantity,line_total',
                'activeItems.product:id,size',
            ]);

            $result = [
                'message' => 'Item deleted.',
                'order' => $this->orderToPayload($order),
            ];
        });

        return response()->json($result);
    }

    private function generateOrderNumber(): string
    {
        return 'KK-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    }

    private function orderToPayload(Order $order): array
    {
        $total = (float) ($order->total_amount ?? $order->total ?? 0);

        return [
            'id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
            'created_at' => $order->created_at?->format('Y-m-d H:i') ?? null,
            'staff_name' => $order->creator?->name,
            'customer_name' => $order->customer_name,
            'status' => (string) $order->status,
            'payment_type' => (string) ($order->payment_type ?? ''),
            'cash_received' => $order->cash_received !== null ? (float) $order->cash_received : null,
            'change_amount' => $order->change_amount !== null ? (float) $order->change_amount : null,
            'total' => $total,
            'items' => $order->activeItems->map(function (OrderItem $i) {
                return [
                    'id' => (int) $i->id,
                    'name' => (string) $i->name,
                    'size' => $i->product?->size,
                    'quantity' => (int) $i->quantity,
                    'price' => (float) $i->price,
                    'line_total' => (float) $i->line_total,
                ];
            })->values()->all(),
        ];
    }

    private function recalculateOrderTotals(Order $order): void
    {
        $total = (float) $order->activeItems()->sum('line_total');

        $order->total_amount = $total;
        $order->total = $total;

        if ($order->payment_type === 'cash') {
            $cash = (float) ($order->cash_received ?? 0);
            if ($cash < $total) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Cash received is insufficient for the updated total.',
                    'errors' => [
                        'cash_received' => ['Cash received is insufficient for the updated total.'],
                    ],
                ], 422));
            }

            $order->change_amount = $cash - $total;
        } else {
            $order->change_amount = 0;
        }

        $order->save();
    }
}
