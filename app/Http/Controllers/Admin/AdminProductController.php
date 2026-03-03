<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->orderBy('category')
            ->orderBy('name')
            ->orderBy('size')
            ->get(['id', 'name', 'category', 'size', 'price', 'image', 'is_active']);

        $groups = $products
            ->groupBy(fn (Product $p) => $p->category . '||' . $p->name)
            ->map(function ($items) {
                /** @var \Illuminate\Support\Collection<int, Product> $items */
                $first = $items->first();
                $key = ($first?->category ?? '') . '||' . ($first?->name ?? '');

                return [
                    'key' => $key,
                    'product' => [
                        'id' => $first?->id,
                        'name' => $first?->name,
                        'category' => $first?->category,
                        'image' => $first?->image,
                        'is_active' => (bool) ($first?->is_active),
                    ],
                    'sizes' => $items->map(fn (Product $p) => [
                        'id' => $p->id,
                        'size' => $p->size,
                        'price' => $p->price,
                    ])->values(),
                    'editUrl' => $first ? route('admin.products.edit', $first) : null,
                    'deleteUrl' => $first ? route('admin.products.destroy', $first) : null,
                ];
            })
            ->values();

        return view('admin.products.index', [
            'groups' => $groups,
        ]);
    }

    public function indexJson(): JsonResponse
    {
        $products = Product::query()
            ->orderBy('category')
            ->orderBy('name')
            ->orderBy('size')
            ->get(['id', 'name', 'category', 'size', 'price', 'image', 'is_active']);

        $groups = $products
            ->groupBy(fn (Product $p) => $p->category . '||' . $p->name)
            ->map(function ($items) {
                /** @var \Illuminate\Support\Collection<int, Product> $items */
                $first = $items->first();
                $key = ($first?->category ?? '') . '||' . ($first?->name ?? '');

                return [
                    'key' => $key,
                    'product' => [
                        'id' => $first?->id,
                        'name' => $first?->name,
                        'category' => $first?->category,
                        'image' => $first?->image,
                        'is_active' => (bool) ($first?->is_active),
                    ],
                    'sizes' => $items->map(fn (Product $p) => [
                        'id' => $p->id,
                        'size' => $p->size,
                        'price' => $p->price,
                    ])->values(),
                    'editUrl' => $first ? route('admin.products.edit', $first) : null,
                    'deleteUrl' => $first ? route('admin.products.destroy', $first) : null,
                ];
            })
            ->values();

        return response()->json([
            'groups' => $groups,
        ]);
    }

    public function create(): View
    {
        $categories = Product::query()
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return view('admin.products.create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'new_category' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*' => ['nullable', 'string', 'max:50'],
            'prices' => ['required', 'array', 'min:1'],
            'prices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $isActive = $request->has('is_active') ? $request->boolean('is_active') : true;

        $category = trim((string) ($validated['new_category'] ?? ''));
        if ($category === '') {
            $category = trim((string) ($validated['category'] ?? ''));
        }
        if ($category === '') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'category' => ['Category is required (select one or add a new category).'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'category' => 'Category is required (select one or add a new category).',
            ])->withInput();
        }

        $sizes = $validated['sizes'];
        $prices = $validated['prices'];

        $rows = [];
        for ($i = 0; $i < count($sizes); $i++) {
            $size = $sizes[$i] ?? null;
            $price = $prices[$i] ?? null;

            if ($price === null || $price === '') {
                continue;
            }

            $rows[] = [
                'size' => (is_string($size) && trim($size) !== '') ? trim($size) : null,
                'price' => $price,
            ];
        }

        if (count($rows) === 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'sizes' => ['Please add at least 1 size/price row.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'sizes' => 'Please add at least 1 size/price row.',
            ])->withInput();
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('products'), $filename);
            $imagePath = 'products/' . $filename;
        }

        DB::transaction(function () use ($validated, $rows, $imagePath, $isActive, $category): void {
            foreach ($rows as $row) {
                Product::create([
                    'name' => $validated['name'],
                    'category' => $category,
                    'size' => $row['size'],
                    'price' => $row['price'],
                    'image' => $imagePath,
                    'is_active' => $isActive,
                ]);
            }
        });

        if ($request->expectsJson()) {
            $createdItems = Product::query()
                ->where('name', $validated['name'])
                ->where('category', $category)
                ->orderBy('size')
                ->get(['id', 'name', 'category', 'size', 'price', 'image', 'is_active']);

            $first = $createdItems->first();

            return response()->json([
                'message' => 'Product created.',
                'group' => [
                    'key' => ($first?->category ?? '') . '||' . ($first?->name ?? ''),
                    'product' => [
                        'id' => $first?->id,
                        'name' => $first?->name,
                        'category' => $first?->category,
                        'image' => $first?->image,
                        'is_active' => (bool) ($first?->is_active),
                    ],
                    'sizes' => $createdItems->map(fn (Product $p) => [
                        'id' => $p->id,
                        'size' => $p->size,
                        'price' => $p->price,
                    ])->values(),
                    'editUrl' => $first ? route('admin.products.edit', $first) : null,
                    'deleteUrl' => $first ? route('admin.products.destroy', $first) : null,
                ],
            ]);
        }

        return redirect()->route('admin.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $groupItems = Product::query()
            ->where('name', $product->name)
            ->where('category', $product->category)
            ->orderBy('size')
            ->get(['id', 'name', 'category', 'size', 'price', 'image', 'is_active']);

        $categories = Product::query()
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return view('admin.products.edit', [
            'product' => $product,
            'groupItems' => $groupItems,
            'categories' => $categories,
        ]);
    }

    public function editData(Product $product): JsonResponse
    {
        $groupItems = Product::query()
            ->where('name', $product->name)
            ->where('category', $product->category)
            ->orderBy('size')
            ->get(['id', 'name', 'category', 'size', 'price', 'image', 'is_active']);

        $categories = Product::query()
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        $first = $groupItems->first();

        return response()->json([
            'group' => [
                'key' => ($first?->category ?? '') . '||' . ($first?->name ?? ''),
                'product' => [
                    'id' => $first?->id,
                    'name' => $first?->name,
                    'category' => $first?->category,
                    'image' => $first?->image,
                    'is_active' => (bool) ($first?->is_active),
                ],
                'sizes' => $groupItems->map(fn (Product $p) => [
                    'id' => $p->id,
                    'size' => $p->size,
                    'price' => $p->price,
                ])->values(),
            ],
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'new_category' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*' => ['nullable', 'string', 'max:50'],
            'prices' => ['required', 'array', 'min:1'],
            'prices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $isActive = $request->has('is_active') ? $request->boolean('is_active') : false;

        $category = trim((string) ($validated['new_category'] ?? ''));
        if ($category === '') {
            $category = trim((string) ($validated['category'] ?? ''));
        }
        if ($category === '') {
            return back()->withErrors([
                'category' => 'Category is required (select one or add a new category).',
            ])->withInput();
        }

        $sizes = $validated['sizes'];
        $prices = $validated['prices'];

        $rows = [];
        for ($i = 0; $i < count($sizes); $i++) {
            $size = $sizes[$i] ?? null;
            $price = $prices[$i] ?? null;

            if ($price === null || $price === '') {
                continue;
            }

            $rows[] = [
                'size' => (is_string($size) && trim($size) !== '') ? trim($size) : null,
                'price' => $price,
            ];
        }

        if (count($rows) === 0) {
            return back()->withErrors([
                'sizes' => 'Please add at least 1 size/price row.',
            ])->withInput();
        }

        $oldName = $product->name;
        $oldCategory = $product->category;

        $existing = Product::query()
            ->where('name', $oldName)
            ->where('category', $oldCategory)
            ->get(['id', 'image']);

        $imagePath = $existing->first()?->image;
        $oldImagePath = $imagePath;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('products'), $filename);
            $imagePath = 'products/' . $filename;
        }

        DB::transaction(function () use ($existing, $validated, $rows, $imagePath, $isActive, $category): void {
            $existing->each->delete();

            foreach ($rows as $row) {
                Product::create([
                    'name' => $validated['name'],
                    'category' => $category,
                    'size' => $row['size'],
                    'price' => $row['price'],
                    'image' => $imagePath,
                    'is_active' => $isActive,
                ]);
            }
        });

        if (is_string($oldImagePath) && $oldImagePath !== $imagePath && str_starts_with($oldImagePath, 'products/')) {
            $oldFullPath = public_path($oldImagePath);
            if (is_file($oldFullPath)) {
                @unlink($oldFullPath);
            }
        }

        if ($request->expectsJson()) {
            $updatedItems = Product::query()
                ->where('name', $validated['name'])
                ->where('category', $category)
                ->orderBy('size')
                ->get(['id', 'name', 'category', 'size', 'price', 'image', 'is_active']);

            $first = $updatedItems->first();

            return response()->json([
                'oldKey' => $oldCategory . '||' . $oldName,
                'group' => [
                    'key' => ($first?->category ?? '') . '||' . ($first?->name ?? ''),
                    'product' => [
                        'id' => $first?->id,
                        'name' => $first?->name,
                        'category' => $first?->category,
                        'image' => $first?->image,
                        'is_active' => (bool) ($first?->is_active),
                    ],
                    'sizes' => $updatedItems->map(fn (Product $p) => [
                        'id' => $p->id,
                        'size' => $p->size,
                        'price' => $p->price,
                    ])->values(),
                    'editUrl' => $first ? route('admin.products.edit', $first) : null,
                    'deleteUrl' => $first ? route('admin.products.destroy', $first) : null,
                ],
            ]);
        }

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $items = Product::query()
            ->where('name', $product->name)
            ->where('category', $product->category)
            ->get(['id', 'image']);

        $imagePath = $items->first()?->image;

        DB::transaction(function () use ($items): void {
            $items->each->delete();
        });

        if (is_string($imagePath) && str_starts_with($imagePath, 'storage/')) {
            $relative = substr($imagePath, strlen('storage/'));
            Storage::disk('public')->delete($relative);
        }

        if (is_string($imagePath) && str_starts_with($imagePath, 'products/')) {
            $fullPath = public_path($imagePath);
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }
}
