<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('orders.index', [
            'orders' => Order::query()
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return $this->pos();
    }

    public function pos(): View
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'category']);

        return view('orders.create', [
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:pending,paid,cancelled'],
            'items' => ['required', 'string'],
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

        $order = null;

        DB::transaction(function () use ($request, $validated, $total, $itemsToInsert, &$order): void {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $validated['customer_name'] ?? null,
                'total' => $total,
                'status' => $validated['status'],
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
