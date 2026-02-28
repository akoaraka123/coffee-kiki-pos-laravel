@extends('layouts.dashboard')

@section('title', 'Edit Product')

@section('content')
    <div class="mx-auto w-full max-w-[860px]">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-xl font-semibold">Edit Product</h1>
                <p class="mt-1 text-sm text-white/60">Update product details and size/price options.</p>
            </div>
            <a href="{{ route('admin.products.index') }}" class="shrink-0 text-sm text-white/70 hover:text-white underline decoration-white/20">Back</a>
        </div>

        @php
            $oldSizes = old('sizes', $groupItems->pluck('size')->map(fn ($v) => $v ?? '')->all());
            $oldPrices = old('prices', $groupItems->pluck('price')->all());
            $initialRows = [];
            for ($i = 0; $i < max(count($oldSizes), 1); $i++) {
                $initialRows[] = [
                    'size' => $oldSizes[$i] ?? '',
                    'price' => $oldPrices[$i] ?? '',
                ];
            }
        @endphp

        <form
            method="POST"
            action="{{ route('admin.products.update', $product) }}"
            enctype="multipart/form-data"
            class="mt-5 rounded-[24px] border border-white/10 bg-white/5 p-6"
            x-data="editProductForm({
                category: @js(old('category', $product->category)),
                newCategory: @js(old('new_category', '')),
                sizes: @js($initialRows),
            })"
        >
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <div>
                    <label for="name" class="text-xs text-white/60">Product Name</label>
                    <input id="name" name="name" value="{{ old('name', $product->name) }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="category" class="text-xs text-white/60">Category</label>
                        <select id="category" name="category" class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20" x-model="category" x-bind:disabled="(newCategory || '').trim().length > 0" x-on:change="if ((category || '').trim().length > 0) { newCategory = '' }">
                            <option value="">Select category</option>
                            @foreach ($categories as $value)
                                <option value="{{ $value }}">{{ $value }}</option>
                            @endforeach
                        </select>
                        <div class="mt-2 text-xs text-white/40" x-show="(newCategory || '').trim().length === 0">Or add a new category below.</div>
                        <div class="mt-2 text-xs text-white/40" x-show="(newCategory || '').trim().length > 0">Using new category. Dropdown disabled.</div>
                    </div>

                    <div>
                        <label for="new_category" class="text-xs text-white/60">Add Category (optional)</label>
                        <input id="new_category" name="new_category" value="{{ old('new_category') }}" placeholder="Type new category name" class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" x-model="newCategory" x-on:input="if ((newCategory || '').trim().length > 0) { category = '' }" />
                        <div class="mt-2 text-xs text-white/40">If you fill this, it will be used instead of the selected category.</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="image" class="text-xs text-white/60">Replace Image</label>
                        <input id="image" name="image" type="file" accept="image/*" class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white file:mr-4 file:rounded-xl file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-white/15" />
                        <div class="mt-2 text-xs text-white/40">Leave empty to keep current image.</div>
                    </div>

                    <div>
                        <label class="text-xs text-white/60">Current Image</label>
                        <div class="mt-2 flex items-center gap-4">
                            <div class="h-16 w-16 overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                                @if ($product->image)
                                    <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-contain" loading="lazy" />
                                @else
                                    <img src="{{ asset('images/coffee-doodle.png') }}" alt="Placeholder" class="h-full w-full object-contain" loading="lazy" />
                                @endif
                            </div>
                            <div class="min-w-0 text-xs text-white/50 truncate">{{ $product->image ?? '—' }}</div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3">
                        <label class="text-xs text-white/60">Sizes & Prices</label>
                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-white/80 hover:bg-white/10" x-on:click="addSize()">
                            Add Size
                        </button>
                    </div>

                    <div class="mt-2 space-y-3">
                        <template x-for="(row, idx) in sizes" :key="idx">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-start">
                                <div>
                                    <input name="sizes[]" x-model="row.size" placeholder="OZ label (e.g. 12oz)" class="w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
                                </div>
                                <div>
                                    <input name="prices[]" x-model="row.price" placeholder="Price (e.g. 39)" inputmode="decimal" class="w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
                                </div>
                                <div class="flex justify-end">
                                    <button type="button" class="inline-flex h-[46px] items-center justify-center rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 text-sm font-semibold text-rose-200 hover:bg-rose-500/20" x-on:click="removeSize(idx)" x-bind:disabled="sizes.length === 1" x-bind:class="sizes.length === 1 ? 'opacity-50 cursor-not-allowed' : ''">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="mt-2 text-xs text-white/40">Edit OZ label, edit price, delete rows, or add a new row. Then save.</div>
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-white/70">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ? '1' : '') ? 'checked' : '' }} class="rounded border-white/10 bg-[#1b1b1b] text-white focus:ring-white/20" />
                        Active (visible in POS)
                    </label>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-white/80 hover:bg-white/10">
                    Cancel
                </a>
                <button type="submit" class="rounded-full bg-[#efe9df] px-5 py-3 text-sm font-semibold text-[#1c1c1c] shadow-lg hover:opacity-95 active:opacity-90">
                    Save Changes
                </button>
            </div>
        </form>

        <script>
            function editProductForm(payload) {
                return {
                    category: payload?.category ?? '',
                    newCategory: payload?.newCategory ?? '',
                    sizes: Array.isArray(payload?.sizes) && payload.sizes.length > 0 ? payload.sizes : [{ size: '', price: '' }],
                    addSize() {
                        this.sizes.push({ size: '', price: '' });
                    },
                    removeSize(idx) {
                        if (this.sizes.length <= 1) return;
                        const ok = confirm('Are you sure you want to delete this size?');
                        if (!ok) return;
                        this.sizes.splice(idx, 1);
                    },
                }
            }
        </script>
    </div>
@endsection
