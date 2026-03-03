<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function index(): View
    {
        $categories = Product::query()
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        $counts = Product::query()
            ->select('category', DB::raw('COUNT(*) as total'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->pluck('total', 'category');

        return view('admin.categories.index', [
            'categories' => $categories,
            'counts' => $counts,
        ]);
    }

    public function indexJson(): JsonResponse
    {
        $categories = Product::query()
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        $counts = Product::query()
            ->select('category', DB::raw('COUNT(*) as total'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->pluck('total', 'category');

        return response()->json([
            'categories' => $categories,
            'counts' => $counts,
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'old_category' => ['required', 'string', 'max:255'],
            'new_category' => ['required', 'string', 'max:255'],
        ]);

        $old = trim($validated['old_category']);
        $new = trim($validated['new_category']);

        if ($old === '' || $new === '') {
            return back()->withErrors([
                'new_category' => 'Category name is required.',
            ]);
        }

        DB::transaction(function () use ($old, $new): void {
            Product::query()
                ->where('category', $old)
                ->update(['category' => $new]);
        });

        if ($request->expectsJson()) {
            return $this->indexJson()->setStatusCode(200);
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:255'],
        ]);

        $category = trim($validated['category']);
        if ($category === '') {
            return back()->withErrors([
                'category' => 'Category name is required.',
            ]);
        }

        DB::transaction(function () use ($category): void {
            Product::query()
                ->where('category', $category)
                ->update(['category' => 'uncategorized']);
        });

        if ($request->expectsJson()) {
            return $this->indexJson()->setStatusCode(200);
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }
}
