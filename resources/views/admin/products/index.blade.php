@extends('layouts.dashboard')

@section('title', 'Products')

@section('content')
    <div class="space-y-6" x-data="adminProductsIndex(@js($groups))">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Products</h2>
                <p class="mt-1 text-sm text-white/50">Create, edit, and remove products shown in the POS menu.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 shadow-sm hover:bg-white/10">
                    Manage Categories
                </a>
                <a href="{{ route('admin.products.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                    Add Product
                </a>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="rounded-full px-4 py-2 text-sm font-semibold transition"
                x-on:click="activeTab = 'all'"
                x-bind:class="activeTab === 'all' ? 'bg-[#efe9df] text-[#1c1c1c]' : 'border border-white/10 bg-white/5 text-white/70 hover:bg-white/10 hover:text-white'"
            >
                All
            </button>

            <template x-for="cat in categories" :key="cat.key">
                <button
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition"
                    x-on:click="activeTab = cat.key"
                    x-bind:class="activeTab === cat.key ? 'bg-[#efe9df] text-[#1c1c1c]' : 'border border-white/10 bg-white/5 text-white/70 hover:bg-white/10 hover:text-white'"
                    x-text="cat.label"
                ></button>
            </template>
        </div>

        <div class="relative">
            <input
                type="text"
                placeholder="Search products..."
                x-model="searchQuery"
                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 pl-12 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
            />
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-white/40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="1.8" />
                <path d="M16.3 16.3 21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="item in filteredGroups()" :key="item.key">
                <div class="group rounded-2xl border border-white/10 bg-white/5 shadow-lg hover:bg-white/10 transition p-5 flex flex-col h-full">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-xl font-semibold tracking-wide text-white" x-text="item.product.name"></h3>
                        <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/80" x-text="item.product.is_active ? 'active' : 'inactive'"></span>
                    </div>

                    <div class="mt-1 text-xs text-white/50" x-text="item.product.category"></div>

                    <div class="flex items-start justify-center pt-2">
                        <img
                            :src="item.product.image ? ((window.__assetBaseUrl || '/') + item.product.image) : ((window.__assetBaseUrl || '/') + 'images/coffee-doodle.png')"
                            :alt="item.product.name"
                            x-on:error="if (!$el.dataset.fallbackTried) { $el.dataset.fallbackTried = '1'; $el.src = (window.__assetBaseUrl || '/') + 'images/coffee-doodle.png'; }"
                            class="max-h-60 w-auto object-contain drop-shadow-xl"
                            loading="lazy"
                            style="image-rendering: -webkit-optimize-contrast;"
                        />
                    </div>

                    <div class="mt-3 space-y-2">
                        <template x-for="size in item.sizes" :key="String(size.size || '') + '-' + String(size.price)">
                            <div class="w-full flex items-center justify-between px-4 py-3 rounded-xl bg-black/40 border border-white/10 text-white text-lg font-medium">
                                <span x-text="size.size || 'Regular'"></span>
                                <span>₱<span x-text="formatPrice(size.price)"></span></span>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-4">
                        <a :href="item.editUrl" class="inline-flex items-center px-3 py-2 rounded-lg bg-white/10 text-white/70 hover:bg-white/20 hover:text-white font-medium text-sm transition">Edit</a>
                        <form method="POST" :action="item.deleteUrl" x-on:submit.prevent="confirmDelete($event)">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg bg-rose-500/10 text-rose-300 hover:bg-rose-500/20 hover:text-rose-200 font-medium text-sm transition">Delete</button>
                        </form>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="filteredGroups().length === 0" class="rounded-xl border border-white/10 bg-white/5 px-6 py-10 text-center text-white/60">
            No products found.
        </div>
    </div>

    <script>
        window.__assetBaseUrl = @js(rtrim(asset(''), '/') . '/');

        function adminProductsIndex(groups) {
            return {
                searchQuery: '',
                groups,
                activeTab: 'all',
                normalizeCategory(value) {
                    return String(value || '')
                        .trim()
                        .toLowerCase()
                        .replace(/\s+/g, '_')
                        .replace(/-+/g, '_');
                },
                displayCategory(value) {
                    const v = String(value || '').trim();
                    if (!v) return 'Uncategorized';
                    return v
                        .replace(/[_-]+/g, ' ')
                        .replace(/\s+/g, ' ')
                        .trim()
                        .replace(/\b\w/g, c => c.toUpperCase());
                },
                get categories() {
                    const map = new Map();
                    (this.groups || []).forEach(item => {
                        const raw = item?.product?.category;
                        const key = this.normalizeCategory(raw);
                        if (!key) return;
                        if (!map.has(key)) {
                            map.set(key, { key, label: this.displayCategory(raw) });
                        }
                    });
                    return Array.from(map.values());
                },
                formatPrice(value) {
                    const n = Number(value || 0);
                    return n.toFixed(2);
                },
                filteredGroups() {
                    const q = String(this.searchQuery || '').trim().toLowerCase();
                    const base = (this.groups || []).filter(item => {
                        if (this.activeTab === 'all') return true;
                        const catKey = this.normalizeCategory(item?.product?.category);
                        return catKey === this.activeTab;
                    });

                    if (!q || q.length < 2) return base;

                    return base.filter(item => {
                        const name = String(item.product?.name || '').toLowerCase();
                        const category = String(item.product?.category || '').toLowerCase();
                        return name.includes(q) || category.includes(q);
                    });
                },
                confirmDelete(e) {
                    const ok = confirm('Are you sure you want to delete this product?');
                    if (!ok) return;
                    e.target.submit();
                },
            }
        }
    </script>
@endsection
