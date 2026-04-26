<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryHistory;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->string('category')->toString();
        $category = $category !== '' ? $category : null;

        $search = $request->string('search')->toString();
        $search = $search !== '' ? $search : null;

        $query = Inventory::query()
            ->with('product:id,name,image')
            ->orderBy('category')
            ->orderBy('product_name')
            ->orderBy('size');

        if ($category) {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('size', 'like', "%{$search}%");
            });
        }

        $inventories = $query->get();

        $categories = Product::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $totalItems = Inventory::count();
        $lowStockCount = Inventory::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0)
            ->count();
        $outOfStockCount = Inventory::where('stock_quantity', 0)->count();
        $totalStock = Inventory::sum('stock_quantity');

        return view('admin.inventory.index', [
            'inventories' => $inventories,
            'categories' => $categories,
            'selectedCategory' => $category,
            'search' => $search,
            'totalItems' => $totalItems,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'totalStock' => $totalStock,
        ]);
    }

    public function history(Request $request): View
    {
        $inventoryId = $request->string('inventory')->toString();
        $inventoryId = $inventoryId !== '' ? $inventoryId : null;

        $query = InventoryHistory::query()
            ->with(['inventory:id,product_name,size', 'user:id,name', 'order:id,order_number'])
            ->orderByDesc('created_at');

        if ($inventoryId) {
            $query->where('inventory_id', $inventoryId);
        }

        $histories = $query->paginate(20);

        $inventories = Inventory::query()
            ->select('id', 'product_name', 'size')
            ->orderBy('product_name')
            ->orderBy('size')
            ->get();

        return view('admin.inventory.history', [
            'histories' => $histories,
            'inventories' => $inventories,
            'selectedInventory' => $inventoryId,
        ]);
    }

    public function addStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_id' => ['required', 'exists:inventories,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $inventory = Inventory::findOrFail($validated['inventory_id']);
        $quantity = (int) $validated['quantity'];

        DB::transaction(function () use ($inventory, $quantity, $request) {
            $inventory->stock_quantity += $quantity;
            $inventory->save();

            InventoryHistory::create([
                'inventory_id' => $inventory->id,
                'product_id' => $inventory->product_id,
                'product_name' => $inventory->product_name,
                'size' => $inventory->size,
                'action_type' => 'ADD_STOCK',
                'quantity' => $quantity,
                'user_id' => $request->user()->id,
                'user_name' => $request->user()->name,
                'order_id' => null,
            ]);
        });

        return response()->json([
            'message' => 'Stock added successfully.',
            'new_quantity' => $inventory->fresh()->stock_quantity,
        ]);
    }

    public function deleteStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_id' => ['required', 'exists:inventories,id'],
        ]);

        $inventory = Inventory::findOrFail($validated['inventory_id']);

        if ($inventory->stock_quantity <= 0) {
            return response()->json([
                'message' => 'Stock is already 0.',
            ], 400);
        }

        $quantity = $inventory->stock_quantity;

        DB::transaction(function () use ($inventory, $quantity, $request) {
            $inventory->stock_quantity = 0;
            $inventory->save();

            InventoryHistory::create([
                'inventory_id' => $inventory->id,
                'product_id' => $inventory->product_id,
                'product_name' => $inventory->product_name,
                'size' => $inventory->size,
                'action_type' => 'DELETE_STOCK',
                'quantity' => $quantity,
                'user_id' => $request->user()->id,
                'user_name' => $request->user()->name,
                'order_id' => null,
            ]);
        });

        return response()->json([
            'message' => 'Stock deleted successfully.',
            'new_quantity' => $inventory->fresh()->stock_quantity,
        ]);
    }

    public function initializeInventory(): JsonResponse
    {
        $products = Product::where('is_active', true)->get();

        $createdCount = 0;

        foreach ($products as $product) {
            $key = $product->category . '||' . $product->name . '||' . ($product->size ?? 'Regular');

            $existing = Inventory::where('product_id', $product->id)->first();

            if (!$existing) {
                Inventory::create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'category' => $product->category,
                    'size' => $product->size,
                    'stock_quantity' => 0,
                    'low_stock_threshold' => 10,
                ]);
                $createdCount++;
            }
        }

        return response()->json([
            'message' => "Inventory initialized. Created {$createdCount} records.",
            'created_count' => $createdCount,
        ]);
    }
}
