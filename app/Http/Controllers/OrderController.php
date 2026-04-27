<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryHistory;
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

        // Check if today's sales have been reconciled for cash and gcash separately
        $cashReconciledAt = null;
        $gcashReconciledAt = null;
        $reconciliationRow = DB::table('daily_sales_reconciliations')
            ->where('user_id', $userId)
            ->where('date', $start->toDateString())
            ->first(['reconciliation_data']);

        if ($reconciliationRow && $reconciliationRow->reconciliation_data) {
            try {
                $reconciliationData = json_decode($reconciliationRow->reconciliation_data, true);
                if (isset($reconciliationData['cash']['reconciled_at'])) {
                    $cashReconciledAt = Carbon::parse($reconciliationData['cash']['reconciled_at']);
                }
                if (isset($reconciliationData['gcash']['reconciled_at'])) {
                    $gcashReconciledAt = Carbon::parse($reconciliationData['gcash']['reconciled_at']);
                }
            } catch (\Throwable $e) {
                // If JSON parsing fails, fall back to old behavior
                if ($reconciliationRow->reconciled_at) {
                    try {
                        $cashReconciledAt = Carbon::parse($reconciliationRow->reconciled_at);
                        $gcashReconciledAt = Carbon::parse($reconciliationRow->reconciled_at);
                    } catch (\Throwable $e) {
                        $cashReconciledAt = null;
                        $gcashReconciledAt = null;
                    }
                }
            }
        }

        $todaySales = 0.0;
        $todayOrders = 0;
        if ($userId) {
            // Calculate cash sales (exclude reconciled cash orders)
            $cashSalesQuery = Order::query()
                ->where('created_by', $userId)
                ->where('status', 'paid')
                ->where('payment_type', 'cash')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end);

            if ($cashReconciledAt) {
                $cashSalesQuery->where('created_at', '>', $cashReconciledAt);
            }

            $cashSales = (float) $cashSalesQuery->sum('total');

            // Calculate GCash sales (exclude reconciled GCash orders)
            $gcashSalesQuery = Order::query()
                ->where('created_by', $userId)
                ->where('status', 'paid')
                ->where('payment_type', 'gcash')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end);

            if ($gcashReconciledAt) {
                $gcashSalesQuery->where('created_at', '>', $gcashReconciledAt);
            }

            $gcashSales = (float) $gcashSalesQuery->sum('total');

            $todaySales = $cashSales + $gcashSales;

            // Calculate total orders (use the later of the two cutoffs)
            $todayOrdersQuery = Order::query()
                ->where('created_by', $userId)
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end);

            // Use the latest reconciliation cutoff
            $latestCutoff = null;
            if ($cashReconciledAt && $gcashReconciledAt) {
                $latestCutoff = $cashReconciledAt->gt($gcashReconciledAt) ? $cashReconciledAt : $gcashReconciledAt;
            } elseif ($cashReconciledAt) {
                $latestCutoff = $cashReconciledAt;
            } elseif ($gcashReconciledAt) {
                $latestCutoff = $gcashReconciledAt;
            }

            if ($latestCutoff) {
                $todayOrdersQuery->where('created_at', '>', $latestCutoff);
            }

            $todayOrders = (int) $todayOrdersQuery->count();
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
                $dateDisplay = null;
                try {
                    $dateDisplay = Carbon::parse($date)->format('F j, Y (l)');
                } catch (\Throwable $e) {
                    $dateDisplay = $date;
                }
                return [
                    'date' => $date,
                    'date_display' => $dateDisplay,
                    'total_orders' => (int) ($g->total_orders ?? 0),
                    'total_items' => (int) ($itemsByDate->get($date, 0)),
                    'total_sales' => (float) ($g->total_sales ?? 0),
                ];
            });
        }

        $selectedDateDisplay = null;
        if ($selectedDate) {
            try {
                $selectedDateDisplay = Carbon::parse($selectedDate)->format('F j, Y (l)');
            } catch (\Throwable $e) {
                $selectedDateDisplay = $selectedDate;
            }
        }

        return view('orders.index', [
            'orders' => $ordersQuery->paginate(10),
            'todaySales' => $todaySales,
            'todayOrders' => $todayOrders,
            'dailySummaries' => $dailySummaries,
            'selectedDate' => $selectedDate,
            'selectedDateDisplay' => $selectedDateDisplay,
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

        $inventories = Inventory::query()
            ->get(['product_id', 'stock_quantity', 'low_stock_threshold']);

        $inventoryMap = $inventories->keyBy('product_id');

        $totalStock = Inventory::sum('stock_quantity');

        return response()->view('orders.create', [
            'products' => $products,
            'inventoryMap' => $inventoryMap,
            'totalStock' => $totalStock,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            abort(403);
        }

        if ($user && method_exists($user, 'isStaff') && $user->isStaff() && ! $user->clocked_in) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Staff is currently clocked out. You cannot add or checkout orders.',
                ], 403);
            }

            abort(403, 'Staff is currently clocked out. You cannot add or checkout orders.');
        }

        // Find or create active sales session
        $activeSalesSession = \App\Models\Shift::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->latest('started_at')
            ->first();

        if (! $activeSalesSession) {
            try {
                $activeSalesSession = \App\Models\Shift::create([
                    'user_id' => $user->id,
                    'shift_id' => (string) Str::uuid(),
                    'business_date' => now()->toDateString(),
                    'started_at' => now(),
                    'status' => 'ACTIVE',
                ]);
            } catch (\Throwable $e) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Unable to create active sales session.',
                    ], 422);
                }

                abort(422, 'Unable to create active sales session.');
            }
        }

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:pending,paid,cancelled'],
            'items' => ['required', 'string'],
            'payment_type' => ['required', 'string', 'in:cash,gcash'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'gcash_reference' => ['nullable', 'string', 'max:255'],
            'gcash_sender_name' => ['nullable', 'string', 'max:255'],
            'gcash_sender_mobile' => ['nullable', 'string', 'max:11'],
            'gcash_proof_image' => ['nullable', 'string'],
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
            ->get(['id', 'name', 'price', 'size']);

        $itemsToInsert = [];
        $total = 0;

        // Validate stock availability
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

            $inventory = Inventory::where('product_id', $productId)->first();

            if ($inventory && $inventory->stock_quantity < $qty) {
                $size = $product->size ?? 'Regular';
                $errorMessage = "Not enough stock for {$product->name} ({$size}). Available stock: {$inventory->stock_quantity}.";
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'items' => [$errorMessage],
                        ],
                    ], 422);
                }

                return back()->withErrors([
                    'items' => $errorMessage,
                ])->withInput();
            }
        }

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

        DB::transaction(function () use ($request, $validated, $total, $itemsToInsert, $paymentType, $cashReceived, $changeAmount, &$order, $activeSalesSession): void {
            $gcashProofImagePath = null;
            if ($paymentType === 'gcash' && !empty($validated['gcash_proof_image'])) {
                $gcashProofImagePath = $this->saveGcashProofImage($validated['gcash_proof_image']);
            }

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $validated['customer_name'] ?? null,
                'total_amount' => $total,
                'total' => $total,
                'status' => $validated['status'],
                'payment_type' => $paymentType,
                'cash_received' => $paymentType === 'cash' ? $cashReceived : null,
                'change_amount' => $paymentType === 'cash' ? $changeAmount : 0,
                'gcash_reference' => $paymentType === 'gcash' ? ($validated['gcash_reference'] ?? null) : null,
                'gcash_sender_name' => $paymentType === 'gcash' ? ($validated['gcash_sender_name'] ?? null) : null,
                'gcash_sender_mobile' => $paymentType === 'gcash' ? ($validated['gcash_sender_mobile'] ?? null) : null,
                'gcash_proof_image' => $gcashProofImagePath,
                'created_by' => $request->user()->id,
                'shift_id' => $activeSalesSession ? $activeSalesSession->shift_id : null,
                'business_date' => $activeSalesSession ? $activeSalesSession->business_date : null,
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

                // Deduct stock
                $inventory = Inventory::where('product_id', $row['product_id'])->first();
                if ($inventory) {
                    $inventory->stock_quantity -= $row['quantity'];
                    $inventory->save();

                    // Record inventory history
                    $product = Product::find($row['product_id']);
                    InventoryHistory::create([
                        'inventory_id' => $inventory->id,
                        'product_id' => $row['product_id'],
                        'product_name' => $row['name'],
                        'size' => $product ? $product->size : null,
                        'action_type' => 'DEDUCT_STOCK',
                        'quantity' => $row['quantity'],
                        'user_id' => $request->user()->id,
                        'user_name' => $request->user()->name,
                        'order_id' => $order->id,
                    ]);
                }
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
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        if ((int) $order->created_by !== (int) $user->id) {
            return response()->json([
                'message' => 'You are not allowed to view this order.',
            ], 403);
        }

        $order->load([
            'creator:id,name',
            'activeItems:id,order_id,product_id,name,price,quantity,line_total',
            'activeItems.product:id,size',
        ]);

        $payload = $this->orderToPayload($order);

        return response()->json([
            'order' => $payload,
            'items' => $payload['items'] ?? [],
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

    public function destroy(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if (! $user || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            abort(403);
        }

        if ((int) $order->created_by !== (int) $user->id) {
            abort(403);
        }

        if ((string) $order->status === 'paid') {
            $message = 'Paid orders cannot be deleted.';
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }

            return redirect()->route('orders.index')->with('status', $message);
        }

        $orderNumber = (string) $order->order_number;

        DB::transaction(function () use ($order): void {
            $order->delete();
        });

        $message = "Order {$orderNumber} deleted.";
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'id' => (int) $order->id,
            ]);
        }

        return redirect()->route('orders.index')->with('status', $message);
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
        $change = $order->change_amount !== null ? (float) $order->change_amount : null;

        $paymentType = (string) ($order->payment_type ?? '');
        $status = (string) $order->status;
        $statusLabel = $status;
        if ($status === 'paid') {
            $statusLabel = $paymentType === 'gcash' ? 'Pay in G-Cash' : 'Pay in Cash';
        }

        $gcashProofImageUrl = null;
        if ($order->gcash_proof_image) {
            $gcashProofImageUrl = Storage::disk('public')->url($order->gcash_proof_image);
        }

        return [
            'id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
            'created_at' => $this->formatHumanDateTime($order->created_at ? Carbon::instance($order->created_at) : null),
            'created_at_raw' => $order->created_at?->toIso8601String(),
            'staff_name' => $order->creator?->name,
            'customer_name' => $order->customer_name,
            'status' => (string) $order->status,
            'status_label' => $statusLabel,
            'payment_type' => $paymentType,
            'cash_received' => $order->cash_received !== null ? (float) $order->cash_received : null,
            'change_amount' => $change,
            'change' => $change,
            'total' => $total,
            'gcash_reference' => $order->gcash_reference,
            'gcash_sender_name' => $order->gcash_sender_name,
            'gcash_sender_mobile' => $order->gcash_sender_mobile,
            'gcash_proof_image' => $gcashProofImageUrl,
            'items' => $order->activeItems->map(function (OrderItem $i) {
                return [
                    'id' => (int) $i->id,
                    'name' => (string) $i->name,
                    'size' => $i->product?->size,
                    'quantity' => (int) $i->quantity,
                    'qty' => (int) $i->quantity,
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

    private function saveGcashProofImage($base64Image): ?string
    {
        if (empty($base64Image)) {
            return null;
        }

        try {
            // Remove data URI scheme if present
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
                $extension = $matches[1];
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            } else {
                $extension = 'jpg';
            }

            $imageData = base64_decode($base64Image);
            if ($imageData === false) {
                return null;
            }

            $filename = 'gcash_proof_' . time() . '_' . uniqid() . '.' . $extension;
            $path = 'gcash_proofs/' . $filename;

            if (Storage::disk('public')->put($path, $imageData)) {
                return $path;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
