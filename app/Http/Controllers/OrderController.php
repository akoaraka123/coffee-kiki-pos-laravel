<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
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

        $ordersQuery = Order::query()->latest();
        if ($userId) {
            $ordersQuery->where('created_by', $userId);
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

        return view('orders.index', [
            'orders' => $ordersQuery->paginate(10),
            'todaySales' => $todaySales,
            'todayOrders' => $todayOrders,
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

    public function store(Request $request): RedirectResponse
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
                return back()->withErrors([
                    'cash_received' => 'Please enter cash received.',
                ])->withInput();
            }

            if ($cash < $total) {
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

        return redirect()->route('orders.index')->with('status', "Order {$order->order_number} created.");
    }

    private function generateOrderNumber(): string
    {
        return 'KK-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    }
}
