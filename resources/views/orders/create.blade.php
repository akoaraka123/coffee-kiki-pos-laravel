@extends('layouts.pos-dashboard')

@section('title', 'POS')

@section('pos_sidebar')
    <template x-for="cat in categories()" :key="cat.key">
        <button
            type="button"
            class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm"
            :class="activeTab === cat.key ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white'"
            x-on:click="activeTab = cat.key"
            :title="sidebarCollapsed ? cat.label : ''"
        >
            <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10" x-text="cat.icon"></span>
            <span class="font-medium" x-show="!sidebarCollapsed" x-text="cat.label"></span>
        </button>
    </template>
@endsection

@section('x-data')
    x-data="posOrder(@js($products))"
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_360px] h-[calc(100vh-120px)] overflow-hidden">
        <script>
            window.__assetBaseUrl = @js(rtrim(asset(''), '/') . '/');
        </script>
        <div class="space-y-4 overflow-y-auto pr-2 min-h-0">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm font-semibold">Menu</div>
                    <div class="mt-1 text-xs text-white/50">Select items to add to the order.</div>
                </div>

                <a href="{{ route('orders.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">
                    Order History
                </a>
            </div>

            <div class="mt-4 mb-5">
                <div class="relative">
                    <input
                        type="text"
                        placeholder="Search drinks…"
                        x-model.debounce.150ms="searchQuery"
                        class="w-full rounded-xl bg-white/5 border border-white/10 px-4 py-3 pl-11 text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-white/20"
                    />
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-white/40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="1.8" />
                        <path d="M16.3 16.3 21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="product in groupedProducts()" :key="product.name">
                    <div
                        class="group rounded-2xl border border-white/10 bg-white/5 shadow-lg hover:bg-white/10 transition p-5 flex flex-col h-full"
                        :id="productCardId(product.name)"
                        :data-name="product.name"
                        :data-category="product.category"
                        :class="focusedProductName && focusedProductName === product.name ? 'ring-2 ring-white/20' : ''"
                    >
                        <h3 class="text-xl font-semibold tracking-wide text-white mb-2" x-text="product.name"></h3>

                        <div class="flex items-start justify-center pt-2">
                            <img
                                :src="product.image ? ((window.__assetBaseUrl || '/') + product.image) : ((window.__assetBaseUrl || '/') + 'images/coffee-doodle.png')"
                                :alt="product.name"
                                x-on:error="if (!$el.dataset.fallbackTried) { $el.dataset.fallbackTried = '1'; $el.src = (window.__assetBaseUrl || '/') + 'images/coffee-doodle.png'; }"
                                class="max-h-60 w-auto object-contain drop-shadow-xl"
                                loading="lazy"
                                style="image-rendering: -webkit-optimize-contrast;"
                            />
                        </div>

                        <div class="mt-3 space-y-2">
                            <template x-for="size in product.sizes" :key="size.size">
                                <button
                                    type="button"
                                    class="w-full flex items-center justify-between px-4 py-3 rounded-xl bg-black/40 border border-white/10 hover:bg-black/60 transition text-white text-lg font-medium"
                                    x-on:click="add(product.name, size)"
                                >
                                    <span x-text="size.size"></span>
                                    <span>₱<span x-text="formatPrice(size.price)"></span></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="sticky top-6 self-start h-[calc(100vh-160px)]">
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
            <div class="text-sm font-semibold">Current Order</div>

            <div class="mt-4">
                <template x-if="cart.length === 0">
                    <div class="grid place-items-center rounded-xl border border-white/10 bg-white/5 px-6 py-16 text-center">
                        <div class="text-4xl">📦</div>
                        <div class="mt-3 text-sm text-white/60">No items in order</div>
                    </div>
                </template>

                <template x-if="cart.length > 0">
                    <div class="space-y-3">
                        <template x-for="item in cart" :key="item.product_id">
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-white/10 bg-[#111] px-4 py-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold" x-text="item.name"></div>
                                    <div class="mt-0.5 text-xs text-white/50" x-text="item.size || 'Regular'"></div>
                                    <div class="mt-0.5 text-xs text-white/50">₱<span x-text="formatPrice(item.price)"></span> each</div>
                                    <div class="mt-0.5 text-xs font-semibold text-white/80">Subtotal: ₱<span x-text="formatPrice(item.price * item.quantity)"></span></div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button" class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 hover:bg-white/10" x-on:click="decrement(item.product_id, item.size)">-</button>
                                    <div class="w-8 text-center text-sm font-semibold" x-text="item.quantity"></div>
                                    <button type="button" class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 hover:bg-white/10" x-on:click="increment(item.product_id, item.size)">+</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="mt-6 border-t border-white/10 pt-5">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-white/70">Total:</div>
                    <div class="text-xl font-bold">₱<span x-text="formatPrice(total())"></span></div>
                </div>
            </div>

            <form method="POST" action="{{ route('orders.store') }}" class="mt-5 space-y-3">
                @csrf
                <input type="hidden" name="status" value="paid" />
                <input type="hidden" name="items" x-bind:value="JSON.stringify(payloadItems())" />

                <input
                    type="text"
                    name="customer_name"
                    placeholder="Customer name (optional)"
                    class="w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                    value="{{ old('customer_name') }}"
                />

                <button
                    type="submit"
                    class="w-full rounded-full bg-[#efe9df] px-4 py-3 text-sm font-semibold text-[#1c1c1c] shadow-lg hover:opacity-95 active:opacity-90"
                    x-bind:disabled="cart.length === 0"
                    x-bind:class="cart.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                >
                    Checkout
                </button>

                <button
                    type="button"
                    class="w-full rounded-full border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-white/70 hover:bg-white/10"
                    x-on:click="clear()"
                    x-bind:disabled="cart.length === 0"
                >
                    Clear Order
                </button>
            </form>
            </div>
        </div>
    </div>

    <script>
        function posOrder(products) {
            return {
                isDesktop: window.innerWidth >= 1024,
                sidebarOpen: window.innerWidth >= 1024,
                sidebarCollapsed: false,
                hoverOpened: false,
                activeTab: 'milk_tea',
                searchQuery: '',
                focusedProductName: '',
                products,
                cart: [],
                normalizeCategory(value) {
                    return String(value || '')
                        .trim()
                        .toLowerCase()
                        .replace(/\s+/g, '_')
                        .replace(/-+/g, '_');
                },
                displayCategory(value) {
                    const v = String(value || '').trim();
                    if (!v) return 'UNCATEGORIZED';
                    return v
                        .replace(/[_-]+/g, ' ')
                        .replace(/\s+/g, ' ')
                        .trim()
                        .toUpperCase();
                },
                categories() {
                    const map = new Map();
                    (this.products || []).forEach(p => {
                        const raw = p?.category;
                        const key = this.normalizeCategory(raw);
                        if (!key) return;
                        if (!map.has(key)) {
                            const label = this.displayCategory(raw);
                            const icon = (label || 'C').trim().charAt(0) || 'C';
                            map.set(key, { key, label, icon });
                        }
                    });
                    return Array.from(map.values());
                },
                init() {
                    const cats = this.categories();
                    if (cats.length > 0 && !cats.some(c => c.key === this.activeTab)) {
                        this.activeTab = cats[0].key;
                    }

                    window.addEventListener('resize', () => {
                        this.isDesktop = window.innerWidth >= 1024;
                        if (this.isDesktop) {
                            this.sidebarOpen = true;
                        } else {
                            this.sidebarCollapsed = false;
                            this.sidebarOpen = false;
                        }
                    });

                    try {
                        const saved = localStorage.getItem('pos_cart_v1');
                        if (saved) {
                            const parsed = JSON.parse(saved);
                            if (Array.isArray(parsed)) {
                                this.cart = parsed;
                            }
                        }
                    } catch (e) {
                        // ignore
                    }

                    this.$watch('searchQuery', (value) => {
                        const q = (value || '').trim().toLowerCase();
                        if (!q) {
                            this.focusedProductName = '';
                            return;
                        }

                        const match = this.products.find(p => String(p.name || '').toLowerCase().includes(q));
                        if (!match) {
                            this.focusedProductName = '';
                            return;
                        }

                        this.focusedProductName = match.name;

                        const matchCategory = this.normalizeCategory(match.category);
                        if (matchCategory && matchCategory !== this.activeTab) {
                            this.activeTab = matchCategory;
                        }

                        this.$nextTick(() => {
                            const el = document.getElementById(this.productCardId(match.name));
                            if (el && typeof el.scrollIntoView === 'function') {
                                el.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
                            }
                        });
                    });
                },
                toggleSidebar() {
                    if (this.isDesktop) {
                        this.sidebarCollapsed = !this.sidebarCollapsed;
                        this.sidebarOpen = true;
                        this.hoverOpened = false;
                        return;
                    }
                    this.sidebarOpen = !this.sidebarOpen;
                },
                productCardId(name) {
                    return 'pos-product-' + String(name || '')
                        .toLowerCase()
                        .trim()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/(^-|-$)/g, '');
                },
                groupedProducts() {
                    const q = (this.searchQuery || '').trim().toLowerCase();
                    const filtered = this.products.filter(p => {
                        if (this.normalizeCategory(p.category) !== this.activeTab) return false;
                        if (!q) return true;
                        return String(p.name || '').toLowerCase().includes(q);
                    });
                    const grouped = {};
                    
                    filtered.forEach(product => {
                        const key = product.name;
                        if (!grouped[key]) {
                            grouped[key] = {
                                name: product.name,
                                image: product.image,
                                category: this.normalizeCategory(product.category),
                                sizes: []
                            };
                        }
                        grouped[key].sizes.push({
                            id: product.id,
                            size: product.size || 'Regular',
                            price: Number(product.price)
                        });
                    });
                    
                    return Object.values(grouped);
                },
                add(name, sizeInfo) {
                    const existing = this.cart.find(i => i.name === name && i.size === sizeInfo.size);
                    if (existing) {
                        existing.quantity += 1;
                        this.persistCart();
                        return;
                    }

                    this.cart.push({
                        product_id: sizeInfo.id,
                        name: name,
                        size: sizeInfo.size,
                        price: Number(sizeInfo.price),
                        quantity: 1,
                    });
                    this.persistCart();
                },
                increment(productId, size) {
                    const item = this.cart.find(i => i.product_id === productId && i.size === size);
                    if (!item) return;
                    item.quantity += 1;
                    this.persistCart();
                },
                decrement(productId, size) {
                    const item = this.cart.find(i => i.product_id === productId && i.size === size);
                    if (!item) return;
                    item.quantity -= 1;
                    if (item.quantity <= 0) {
                        this.cart = this.cart.filter(i => !(i.product_id === productId && i.size === size));
                    }
                    this.persistCart();
                },
                clear() {
                    this.cart = [];
                    this.persistCart();
                },
                persistCart() {
                    try {
                        localStorage.setItem('pos_cart_v1', JSON.stringify(this.cart));
                    } catch (e) {
                        // ignore
                    }
                },
                total() {
                    return this.cart.reduce((sum, i) => sum + (Number(i.price) * Number(i.quantity)), 0);
                },
                payloadItems() {
                    return this.cart.map(i => ({
                        product_id: i.product_id,
                        quantity: i.quantity,
                    }));
                },
                formatPrice(value) {
                    const num = Number(value || 0);
                    return num.toFixed(2);
                },
            }
        }
    </script>
@endsection
