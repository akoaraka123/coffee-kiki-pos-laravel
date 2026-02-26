@extends('layouts.pos-dashboard')

@section('title', 'POS')

@section('pos_sidebar')
    <button type="button" class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm" :class="activeTab === 'milk_tea' ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white'" x-on:click="activeTab = 'milk_tea'" :title="sidebarCollapsed ? 'MILK TEA' : ''">
        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">M</span>
        <span class="font-medium" x-show="!sidebarCollapsed">MILK TEA</span>
    </button>
    <button type="button" class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm" :class="activeTab === 'iced_coffee' ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white'" x-on:click="activeTab = 'iced_coffee'" :title="sidebarCollapsed ? 'ICED COFFEE' : ''">
        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">I</span>
        <span class="font-medium" x-show="!sidebarCollapsed">ICED COFFEE</span>
    </button>
    <button type="button" class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm" :class="activeTab === 'milky_fruit_jam' ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white'" x-on:click="activeTab = 'milky_fruit_jam'" :title="sidebarCollapsed ? 'MILKY FRUIT JAM SERIES' : ''">
        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">F</span>
        <span class="font-medium" x-show="!sidebarCollapsed">MILKY FRUIT JAM SERIES</span>
    </button>
    <button type="button" class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm" :class="activeTab === 'sticky_milk' ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white'" x-on:click="activeTab = 'sticky_milk'" :title="sidebarCollapsed ? 'STICKY MILK SERIES' : ''">
        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">S</span>
        <span class="font-medium" x-show="!sidebarCollapsed">STICKY MILK SERIES</span>
    </button>
    <button type="button" class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm" :class="activeTab === 'fruit_soda' ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white'" x-on:click="activeTab = 'fruit_soda'" :title="sidebarCollapsed ? 'FRUIT SODA SERIES' : ''">
        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">D</span>
        <span class="font-medium" x-show="!sidebarCollapsed">FRUIT SODA SERIES</span>
    </button>
    <button type="button" class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm" :class="activeTab === 'egg_waffle' ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white'" x-on:click="activeTab = 'egg_waffle'" :title="sidebarCollapsed ? 'EGG WAFFLE' : ''">
        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">E</span>
        <span class="font-medium" x-show="!sidebarCollapsed">EGG WAFFLE</span>
    </button>
@endsection

@section('x-data')
    x-data="posOrder(@js($products))"
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_360px]">
        <script>
            window.__assetBaseUrl = @js(asset(''));
        </script>
        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm font-semibold">Menu</div>
                    <div class="mt-1 text-xs text-white/50">Select items to add to the order.</div>
                </div>

                <a href="{{ route('orders.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">
                    Order History
                </a>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="product in groupedProducts()" :key="product.name">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                        <div class="text-lg font-bold tracking-wide text-white" x-text="product.name"></div>

                        <!-- Product Image -->
                        <template x-if="product.image">
                            <div class="mt-3 mb-4">
                                <img 
                                    :src="(window.__assetBaseUrl || '/') + product.image" 
                                    :alt="product.name"
                                    class="w-full h-36 rounded-xl object-contain"
                                    loading="lazy"
                                    style="image-rendering: -webkit-optimize-contrast;"
                                />
                            </div>
                        </template>
                        
                        <!-- Fallback Icon for items without images -->
                        <template x-if="!product.image">
                            <div class="mt-3 grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/10 text-white/80 mb-4">
                                ☕
                            </div>
                        </template>

                        <template x-if="product.sizes && product.sizes.length > 1">
                            <div class="mt-3 space-y-2">
                                <template x-for="size in product.sizes" :key="size.size">
                                    <button
                                        type="button"
                                        class="w-full rounded-lg border border-white/10 bg-[#111] px-4 py-3 text-left text-base font-semibold transition hover:bg-white/10"
                                        x-on:click="add(product.name, size)"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-white/90" x-text="size.size"></span>
                                            <span class="text-white">₱<span x-text="formatPrice(size.price)"></span></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </template>
                        
                        <template x-if="!product.sizes || product.sizes.length === 1">
                            <button
                                type="button"
                                class="mt-3 w-full rounded-lg border border-white/10 bg-[#111] px-4 py-3 text-left text-base font-semibold transition hover:bg-white/10"
                                x-on:click="add(product.name, product.sizes[0])"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-white/90" x-text="product.sizes[0]?.size || 'Regular'"></span>
                                    <span class="text-white">₱<span x-text="formatPrice(product.sizes[0]?.price || product.price)"></span></span>
                                </div>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </div>

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

    <script>
        function posOrder(products) {
            return {
                isDesktop: window.innerWidth >= 1024,
                sidebarOpen: window.innerWidth >= 1024,
                sidebarCollapsed: false,
                hoverOpened: false,
                activeTab: 'milk_tea',
                products,
                cart: [],
                init() {
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
                groupedProducts() {
                    const filtered = this.products.filter(p => p.category === this.activeTab);
                    const grouped = {};
                    
                    filtered.forEach(product => {
                        const key = product.name;
                        if (!grouped[key]) {
                            grouped[key] = {
                                name: product.name,
                                image: product.image,
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
