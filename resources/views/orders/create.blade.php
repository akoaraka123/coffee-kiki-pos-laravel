@extends('layouts.pos-dashboard')

@section('title', 'POS')

@section('pos_sidebar')
    <button type="button" class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm" :class="activeTab === 'coffee' ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white'" x-on:click="activeTab = 'coffee'">
        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">C</span>
        <span class="font-medium">Coffee</span>
    </button>
    <button type="button" class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm" :class="activeTab === 'non-coffee' ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white'" x-on:click="activeTab = 'non-coffee'">
        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">N</span>
        <span class="font-medium">Non-Coffee</span>
    </button>
    <button type="button" class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-sm" :class="activeTab === 'snacks' ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white'" x-on:click="activeTab = 'snacks'">
        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">S</span>
        <span class="font-medium">Snacks</span>
    </button>
@endsection

@section('x-data')
    x-data="posOrder(@js($products))"
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_360px]">
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

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <template x-for="product in filteredProducts()" :key="product.id">
                    <button
                        type="button"
                        class="group rounded-xl border border-white/10 bg-white/5 p-5 text-left shadow-sm transition hover:bg-white/10"
                        x-on:click="add(product)"
                    >
                        <div class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/10 text-white/80">
                            ☕
                        </div>
                        <div class="mt-4 text-sm font-semibold text-white" x-text="product.name"></div>
                        <div class="mt-1 text-xs text-white/50">₱<span x-text="formatPrice(product.price)"></span></div>
                    </button>
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
                                    <div class="mt-0.5 text-xs text-white/50">₱<span x-text="formatPrice(item.price)"></span></div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button" class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 hover:bg-white/10" x-on:click="decrement(item.product_id)">-</button>
                                    <div class="w-8 text-center text-sm font-semibold" x-text="item.quantity"></div>
                                    <button type="button" class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 hover:bg-white/10" x-on:click="increment(item.product_id)">+</button>
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
                activeTab: 'coffee',
                products,
                cart: [],
                filteredProducts() {
                    return this.products.filter(p => p.category === this.activeTab);
                },
                add(product) {
                    const existing = this.cart.find(i => i.product_id === product.id);
                    if (existing) {
                        existing.quantity += 1;
                        return;
                    }

                    this.cart.push({
                        product_id: product.id,
                        name: product.name,
                        price: Number(product.price),
                        quantity: 1,
                    });
                },
                increment(productId) {
                    const item = this.cart.find(i => i.product_id === productId);
                    if (!item) return;
                    item.quantity += 1;
                },
                decrement(productId) {
                    const item = this.cart.find(i => i.product_id === productId);
                    if (!item) return;
                    item.quantity -= 1;
                    if (item.quantity <= 0) {
                        this.cart = this.cart.filter(i => i.product_id !== productId);
                    }
                },
                clear() {
                    this.cart = [];
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
