@extends('layouts.dashboard')

@section('title', 'Categories')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Categories</h2>
                <p class="mt-1 text-sm text-white/50">Rename or delete categories used by products.</p>
            </div>

            <a href="{{ route('admin.products.index') }}" class="text-sm text-white/70 hover:text-white underline decoration-white/20">Back to Products</a>
        </div>

        <div class="overflow-hidden rounded-xl border border-white/10 bg-white/5 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-white/5 text-white/70">
                        <tr>
                            <th class="px-5 py-4 font-medium">Category</th>
                            <th class="px-5 py-4 font-medium">Products</th>
                            <th class="px-5 py-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($categories as $category)
                            <tr>
                                <td class="px-5 py-4">
                                    <form method="POST" action="{{ route('admin.categories.update') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="old_category" value="{{ $category }}" />
                                        <input
                                            name="new_category"
                                            value="{{ $category }}"
                                            class="w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                                        />
                                        <button type="submit" class="rounded-xl bg-[#efe9df] px-4 py-2 text-xs font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                                            Save
                                        </button>
                                    </form>
                                </td>
                                <td class="px-5 py-4 text-white/70">
                                    {{ (int) ($counts[$category] ?? 0) }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <form method="POST" action="{{ route('admin.categories.destroy') }}" onsubmit="return confirm('Delete this category? Products under it will be moved to Uncategorized.');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="category" value="{{ $category }}" />
                                        <button type="submit" class="text-xs text-rose-300 hover:text-rose-200 underline decoration-rose-300/30">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-5 py-6 text-white/60" colspan="3">No categories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-xs text-white/40">
            Deleting a category will move its products to <span class="text-white/60">uncategorized</span> so they stay visible in POS.
        </div>
    </div>
@endsection
