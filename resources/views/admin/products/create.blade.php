@extends('layouts.dashboard')

@section('title', 'Add Product')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Add Product</h1>
            <p class="mt-1 text-sm text-white/60">Create a new product and its size/price options.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="text-sm text-white/70 hover:text-white underline decoration-white/20">Back</a>
    </div>

    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5 rounded-[24px] border border-white/10 bg-white/5 p-6" x-data="{ category: @js(old('category', $categories->first() ?? '')), newCategory: @js(old('new_category', '')) }">
        @csrf

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="text-xs text-white/60">Product Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
            </div>

            <div>
                <label for="category" class="text-xs text-white/60">Category</label>
                <select id="category" name="category" class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20" x-model="category" x-bind:disabled="(newCategory || '').trim().length > 0" x-on:change="if ((category || '').trim().length > 0) { newCategory = '' }">
                    <option value="">Select category</option>
                    @foreach ($categories as $value)
                        <option value="{{ $value }}">{{ $value }}</option>
                    @endforeach
                </select>
                <div class="mt-2 text-xs text-white/40" x-show="(newCategory || '').trim().length === 0">Or add a new category on the right.</div>
                <div class="mt-2 text-xs text-white/40" x-show="(newCategory || '').trim().length > 0">Using new category. Dropdown disabled.</div>
            </div>

            <div>
                <label for="new_category" class="text-xs text-white/60">Add Category (optional)</label>
                <input id="new_category" name="new_category" placeholder="Type new category name" class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" x-model="newCategory" x-on:input="if ((newCategory || '').trim().length > 0) { category = '' }" />
                <div class="mt-2 text-xs text-white/40">If you fill this, it will be used instead of the selected category.</div>
            </div>

            <div>
                <label for="image" class="text-xs text-white/60">Product Image</label>
                <input id="image" name="image" type="file" accept="image/*" class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white file:mr-4 file:rounded-xl file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-white/15" />
                <div class="mt-2 text-xs text-white/40">Optional. If not set, POS will use a placeholder.</div>
            </div>

            <div class="sm:col-span-2">
                <label class="text-xs text-white/60">Sizes & Prices</label>
                <div class="mt-2 space-y-3">
                    @php
                        $oldSizes = old('sizes', ['12oz', '16oz', '22oz']);
                        $oldPrices = old('prices', ['', '', '']);
                    @endphp

                    @for ($i = 0; $i < max(count($oldSizes), 3); $i++)
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <input name="sizes[]" value="{{ $oldSizes[$i] ?? '' }}" placeholder="Size (e.g. 12oz)" class="w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
                            <input name="prices[]" value="{{ $oldPrices[$i] ?? '' }}" placeholder="Price (e.g. 39)" inputmode="decimal" class="w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
                        </div>
                    @endfor
                </div>
                <div class="mt-2 text-xs text-white/40">Add more sizes by duplicating rows after saving, or fill only the rows you need.</div>
            </div>

            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-white/70">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="rounded border-white/10 bg-[#1b1b1b] text-white focus:ring-white/20" />
                    Active (visible in POS)
                </label>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('admin.products.index') }}" class="text-sm text-white/70 hover:text-white underline decoration-white/20">Cancel</a>
            <button type="submit" class="rounded-full bg-[#efe9df] px-5 py-3 text-sm font-semibold text-[#1c1c1c] shadow-lg hover:opacity-95 active:opacity-90">
                Create Product
            </button>
        </div>
    </form>
@endsection
