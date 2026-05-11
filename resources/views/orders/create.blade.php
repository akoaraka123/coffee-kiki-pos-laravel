@extends('layouts.pos-dashboard')

@php
    $posUser = auth()->user();
    $posName = trim((string) ($posUser?->name ?? ''));
    $posEmail = trim((string) ($posUser?->email ?? ''));
    $posEmailLocal = $posEmail !== '' ? preg_replace('/@.*$/', '', $posEmail) : '';
    $receiptCashierDisplay = ($posName !== '' && strcasecmp($posName, 'Staff') !== 0)
        ? $posName
        : ($posEmailLocal !== '' ? $posEmailLocal : ($posEmail !== '' ? $posEmail : 'Staff'));
@endphp

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

    <a href="{{ route('staff.inventory.index') }}" class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('staff.inventory.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}" :title="sidebarCollapsed ? 'Inventory' : ''">
        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">I</span>
        <span class="font-medium" x-show="!sidebarCollapsed">Inventory</span>
    </a>
@endsection

@section('x-data')
    x-data="posOrder('{{ auth()->user()->pos_layout === 'left' ? 'left' : 'right' }}')"
    data-products='@json($products)'
    data-inventory='@json($inventoryMap)'
    data-initial-layout="{{ auth()->user()->pos_layout === 'left' ? 'left' : 'right' }}"
    data-receipt-logo-url="{{ asset('images/khopi-kiki-logo.png') }}"
    data-receipt-cashier-name="{{ $receiptCashierDisplay }}"
@endsection

@section('content')
    <div
        class="grid grid-cols-1 gap-6 h-[calc(100vh-120px)] overflow-hidden"
        :class="({ left: 'lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]', right: 'lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]' })[layoutPosition] || 'lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]'"
    >
        <script>
            window.__assetBaseUrl = @js(rtrim(asset(''), '/') . '/');
            window.__posCashierName = @js($receiptCashierDisplay);
        </script>
        <div
            class="space-y-4 overflow-y-auto pr-2 min-h-0"
            :class="layoutPosition === 'left' ? 'lg:order-2' : 'lg:order-1'"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm font-semibold">Menu</div>
                    <div class="mt-1 text-xs text-white/50">Select items to add to the order.</div>
                    <div class="mt-1 text-xs text-emerald-400">Total Stock: {{ number_format($totalStock ?? 0) }}</div>
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

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3">
                <template x-for="product in groupedProducts()" :key="product.name">
                    <div
                        class="group rounded-2xl border border-white/10 bg-white/5 shadow-lg hover:bg-white/10 transition p-5 flex flex-col h-full"
                        :id="productCardId(product.name)"
                        :data-name="product.name"
                        :data-category="product.category"
                        x-on:click="openProductModal(product)"
                        :class="focusedProductName && focusedProductName === product.name ? 'ring-2 ring-white/20' : ''"
                    >
                        <h3 class="text-xl font-semibold tracking-wide text-white mb-3 leading-tight" x-text="product.name"></h3>

                        <div class="flex items-start justify-center py-3">
                            <img
                                :src="productImageSrc(product)"
                                :alt="product.name"
                                x-on:error="if (!$el.dataset.fallbackTried) { $el.dataset.fallbackTried = '1'; $el.src = (window.__assetBaseUrl || '/') + 'images/coffee-doodle.png'; }"
                                class="max-h-48 sm:max-h-52 w-auto object-contain drop-shadow-xl"
                                x-on:click.stop="openProductModal(product)"
                                loading="lazy"
                                style="image-rendering: -webkit-optimize-contrast;"
                            />
                        </div>

                        <div class="mt-4 space-y-3 flex-1">
                            <template
                                x-if="Array.isArray(product.sizes) && product.sizes.length > 0"
                            >
                                <template
                                    x-for="(size, idx) in product.sizes"
                                    :key="String(size.id || '') + '-' + idx"
                                >
                                <div class="relative">
                                    <button
                                        type="button"
                                        class="w-full flex items-start justify-between gap-3 px-4 py-3 rounded-xl bg-black/40 border border-white/10 transition text-left"
                                        :class="getStockStatus(size.id) === 'out_of_stock' ? 'opacity-50 cursor-not-allowed' : 'hover:bg-black/60'"
                                        :disabled="getStockStatus(size.id) === 'out_of_stock'"
                                        x-on:click.stop="getStockStatus(size.id) !== 'out_of_stock' && add(product.name, size)"
                                    >
                                        <div class="flex-1 min-w-0">
                                            <div class="text-white font-medium text-base sm:text-lg" x-text="size.size || 'Regular'"></div>
                                            <div class="text-xs text-white/60 mt-1" x-text="'Stock: ' + (getStockQuantity(size.id) === '-' ? '0' : getStockQuantity(size.id))"></div>
                                        </div>
                                        <div class="flex flex-col items-end gap-1 min-w-fit">
                                            <div class="text-white font-semibold text-base sm:text-lg">₱<span x-text="formatPrice(size.price)"></span></div>
                                            <div class="text-[10px] sm:text-xs font-semibold px-2 py-0.5 rounded-full"
                                                :class="{
                                                    'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': getStockStatus(size.id) === 'in_stock',
                                                    'bg-amber-500/20 text-amber-300 border border-amber-500/30': getStockStatus(size.id) === 'low_stock',
                                                    'bg-rose-500/20 text-rose-300 border border-rose-500/30': getStockStatus(size.id) === 'out_of_stock'
                                                }"
                                                x-text="getStockStatus(size.id) === 'out_of_stock' ? 'Out of Stock' : (getStockStatus(size.id) === 'low_stock' ? 'Low Stock' : 'In Stock')"
                                            ></div>
                                        </div>
                                    </button>
                                </div>
                                </template>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div
            class="sticky top-6 self-start h-[calc(100vh-160px)]"
            :class="layoutPosition === 'left' ? 'lg:order-1' : 'lg:order-2'"
        >
            <div class="rounded-xl border border-white/10 bg-white/5 p-4 shadow-sm flex flex-col h-full min-h-0">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold">Current Order</div>
                <template x-if="cart.length > 0">
                    <div class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-medium text-white/80" x-text="cart.length + ' items'"></div>
                </template>
            </div>

            <div class="mt-3 flex-1 min-h-0 overflow-y-auto pr-1" style="max-height: calc(100vh - 400px);">
                <template x-if="cart.length === 0">
                    <div class="grid place-items-center rounded-xl border border-white/10 bg-white/5 px-6 py-12 text-center">
                        <div class="text-3xl">📦</div>
                        <div class="mt-2 text-xs text-white/60">No items in order</div>
                    </div>
                </template>

                <template x-if="cart.length > 0">
                    <div class="space-y-2">
                        <template x-for="item in cart" :key="item.product_id">
                            <div class="flex items-center justify-between gap-2 rounded-xl border border-white/10 bg-[#111] px-3 py-2.5">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-semibold text-white" x-text="item.name"></div>
                                    <div class="mt-0.5 text-xs text-white/50" x-text="item.size || 'Regular'"></div>
                                    <div class="mt-0.5 flex items-center gap-2">
                                        <span class="text-xs text-white/50">₱<span x-text="formatPrice(item.price)"></span> each</span>
                                        <span class="text-xs font-semibold text-white/70">Subtotal: ₱<span x-text="formatPrice(item.price * item.quantity)"></span></span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-sm" x-on:click="decrement(item.product_id, item.size)">-</button>
                                    <div class="w-7 text-center text-sm font-semibold text-white" x-text="item.quantity"></div>
                                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-sm" x-on:click="increment(item.product_id, item.size)">+</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="mt-3 border-t border-white/10 pt-3">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-semibold text-white/50">Items: <span x-text="cart.reduce((sum, i) => sum + i.quantity, 0)"></span></div>
                    <div class="text-xl font-bold text-white">₱<span x-text="formatPrice(total())"></span></div>
                </div>
            </div>

            <form id="pos-checkout-form" method="POST" action="{{ route('orders.store') }}" class="mt-3 space-y-2.5" x-on:submit.prevent="startCheckout()">
                @csrf
                <input type="hidden" name="status" value="paid" />
                <input type="hidden" name="items" x-bind:value="JSON.stringify(payloadItems())" />
                <input type="hidden" name="payment_type" x-bind:value="paymentType" />
                <input type="hidden" name="total_amount" x-bind:value="formatPrice(total())" />
                <input type="hidden" name="cash_received" x-bind:value="paymentType === 'cash' ? cashReceived : ''" />
                <input type="hidden" name="gcash_reference" x-bind:value="paymentType === 'gcash' ? gcashReferenceNumber : ''" />
                <input type="hidden" name="gcash_sender_name" x-bind:value="paymentType === 'gcash' ? gcashSenderName : ''" />
                <input type="hidden" name="gcash_sender_mobile" x-bind:value="paymentType === 'gcash' ? gcashSenderMobile : ''" />
                <input type="hidden" name="gcash_proof_image" x-bind:value="paymentType === 'gcash' ? gcashProofPreview : ''" />

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-white/60">Payment Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="rounded-xl border px-3 py-2.5 text-sm font-semibold transition"
                            x-on:click="selectPaymentType('cash')"
                            x-bind:class="paymentType === 'cash' ? 'border-emerald-400/30 bg-emerald-500 text-white shadow-sm' : 'border-white/10 bg-[#111] text-white/80 hover:bg-white/5'"
                        >
                            Cash
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border px-3 py-2.5 text-sm font-semibold transition"
                            x-on:click="selectPaymentType('gcash')"
                            x-bind:class="paymentType === 'gcash' ? 'border-blue-400/30 bg-[#007bff] text-white shadow-sm' : 'border-white/10 bg-[#111] text-white/80 hover:bg-white/5'"
                        >
                            GCash
                        </button>
                    </div>
                </div>

                <template x-if="paymentType === 'gcash'">
                    <div class="rounded-xl border border-blue-400/20 bg-blue-500/10 px-3 py-2 text-xs" x-cloak>
                        <template x-if="!gcashDetailsSaved">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-blue-200">Please complete GCash payment details before checkout.</span>
                            </div>
                        </template>
                        <template x-if="gcashDetailsSaved">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-emerald-200">GCash details saved.</span>
                                </div>
                                <button type="button" class="text-xs font-medium text-blue-300 hover:text-blue-200 underline" x-on:click="openGcashModal()">
                                    Edit
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="paymentType === 'cash'">
                    <div class="space-y-1.5" x-cloak>
                        <label class="text-xs font-semibold text-white/60">Cash Received</label>
                        <input
                            type="number"
                            inputmode="decimal"
                            step="0.01"
                            min="0"
                            placeholder="Enter cash amount"
                            class="w-full rounded-xl border border-white/10 bg-[#111] px-3 py-2.5 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                            x-model="cashReceived"
                        />

                        <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm" x-show="cashReceived !== ''">
                            <div class="flex items-center justify-between text-white/70">
                                <span>Change</span>
                                <span class="font-semibold text-white">₱<span x-text="formatPrice(changeAmount())"></span></span>
                            </div>
                            <div class="mt-0.5 text-xs text-white/40" x-show="Number(cashReceived || 0) < Number(total() || 0)">Insufficient payment amount.</div>
                        </div>
                    </div>
                </template>

                <button
                    type="button"
                    class="w-full rounded-full bg-orange-500 px-4 py-2.5 text-sm font-bold text-white shadow-lg hover:bg-orange-600 active:bg-orange-700"
                    x-bind:disabled="cart.length === 0 || isSubmitting"
                    x-bind:class="(cart.length === 0 || isSubmitting) ? 'opacity-50 cursor-not-allowed' : ''"
                    x-on:click="startCheckout()"
                >
                    Checkout
                </button>

                <button
                    type="button"
                    class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 hover:bg-white/10"
                    x-on:click="clear()"
                >
                    Clear Order
                </button>
            </form>
        </div>
    </div>
</div>

    <div class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2" x-show="toastOpen" x-cloak x-transition.opacity>
        <div class="rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-sm font-semibold text-white shadow-2xl" x-text="toastMessage"></div>
    </div>

    <template x-if="checkoutModal">
        <div class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/70" x-transition.opacity x-on:click="checkoutModal = false"></div>
            <div class="absolute inset-0 grid place-items-center px-4">
                <div class="w-full max-w-md rounded-2xl border border-white/10 bg-[#111] p-6 shadow-2xl" x-transition>
                <div class="text-lg font-semibold">Confirm Checkout</div>
                <div class="mt-1 text-sm text-white/60">Review the payment details before saving.</div>

                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-white/70">Total</span>
                        <span class="font-semibold">₱<span x-text="formatPrice(total())"></span></span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-white/70">Payment</span>
                        <span class="font-semibold" x-text="paymentType === 'cash' ? 'Cash' : 'GCash'"></span>
                    </div>
                    <div class="flex items-center justify-between text-sm" x-show="paymentType === 'cash'">
                        <span class="text-white/70">Cash Received</span>
                        <span class="font-semibold">₱<span x-text="formatPrice(Number(cashReceived || 0))"></span></span>
                    </div>
                    <div class="flex items-center justify-between text-sm" x-show="paymentType === 'cash'">
                        <span class="text-white/70">Change</span>
                        <span class="font-semibold">₱<span x-text="formatPrice(changeAmount())"></span></span>
                    </div>

                    <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200" x-show="checkoutError">
                        <div class="font-semibold">Action failed</div>
                        <div class="mt-1" x-text="checkoutError"></div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 hover:bg-white/10" x-on:click="checkoutModal = false">
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-rose-600/35 bg-rose-600/25 px-4 py-2 text-sm font-semibold text-rose-100 shadow-sm hover:bg-rose-600/30"
                        x-bind:disabled="isSubmitting"
                        x-bind:class="isSubmitting ? 'opacity-60 cursor-not-allowed' : ''"
                        x-on:click="confirmCheckout()"
                    >
                        Confirm
                    </button>
                </div>
                </div>
            </div>
        </div>
    </template>

    <template x-if="printReceiptModalOpen">
        <div class="fixed inset-0 z-[55]" x-on:keydown.escape.window="finishPrintReceiptPrompt(false)">
            <div class="absolute inset-0 bg-black/70" x-transition.opacity x-on:click="finishPrintReceiptPrompt(false)"></div>
            <div class="absolute inset-0 grid place-items-center px-4">
                <div class="w-full max-w-md rounded-2xl border border-white/10 bg-[#111] p-6 shadow-2xl" x-transition x-on:click.stop>
                    <div class="text-lg font-semibold">Print receipt?</div>
                    <div class="mt-1 text-sm text-white/60">Would you like to print a receipt for this order?</div>
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 hover:bg-white/10"
                            x-on:click="finishPrintReceiptPrompt(false)"
                        >
                            No
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-rose-600/35 bg-rose-600/25 px-4 py-2 text-sm font-semibold text-rose-100 shadow-sm hover:bg-rose-600/30"
                            x-on:click="finishPrintReceiptPrompt(true)"
                        >
                            Yes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template x-if="gcashModal">
        <div class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/70" x-transition.opacity x-on:click="closeGcashModal()"></div>
            <div class="absolute inset-0 grid place-items-center px-4">
                <div class="w-full max-w-lg rounded-2xl border border-white/10 bg-[#111] p-6 shadow-2xl max-h-[90vh] overflow-y-auto" x-transition>
                    <div class="flex items-center justify-between">
                        <div class="text-lg font-semibold">GCash Payment Details</div>
                        <button type="button" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/5 text-white/80 hover:bg-white/10" x-on:click="closeGcashModal()">✕</button>
                    </div>
                    <div class="mt-1 text-sm text-white/60">Please complete the GCash payment details before checkout.</div>

                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-white/60 mb-2">Transaction Proof</label>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        capture="environment"
                                        x-on:change="handleGcashProofUpload($event)"
                                        class="hidden"
                                        id="gcash-proof-input"
                                    />
                                    <button
                                        type="button"
                                        x-on:click="document.getElementById('gcash-proof-input').click()"
                                        class="w-full h-32 rounded-xl border-2 border-dashed border-white/20 bg-white/5 hover:bg-white/10 transition flex flex-col items-center justify-center gap-2"
                                    >
                                        <div class="text-2xl">📷</div>
                                        <div class="text-sm font-medium text-white">Take Picture</div>
                                        <div class="text-xs text-white/50">or click to upload</div>
                                        <div class="text-xs text-white/40">PNG, JPG up to 5MB</div>
                                    </button>
                                </div>
                                <template x-if="gcashProofPreview">
                                    <div class="flex-1 flex flex-col items-center justify-center">
                                        <div class="relative w-full h-32 rounded-xl border border-white/10 overflow-hidden">
                                            <img :src="gcashProofPreview" class="w-full h-full object-cover" alt="Transaction proof" />
                                            <div class="absolute top-2 right-2">
                                                <div class="h-6 w-6 rounded-full bg-emerald-500 flex items-center justify-center">
                                                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            x-on:click="removeGcashProof()"
                                            class="mt-2 text-xs font-medium text-rose-400 hover:text-rose-300"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-white/60 mb-1.5">Reference Number</label>
                            <input
                                type="text"
                                placeholder="Enter GCash reference number"
                                x-model="gcashReferenceNumber"
                                class="w-full rounded-xl border border-white/10 bg-[#111] px-3 py-2.5 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-white/60 mb-1.5">Sender Name <span class="text-rose-400">*</span></label>
                            <input
                                type="text"
                                placeholder="Enter sender name"
                                x-model="gcashSenderName"
                                class="w-full rounded-xl border border-white/10 bg-[#111] px-3 py-2.5 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-white/60 mb-1.5">Sender Mobile Number <span class="text-rose-400">*</span></label>
                            <input
                                type="tel"
                                placeholder="09XXXXXXXXX"
                                x-model="gcashSenderMobile"
                                maxlength="11"
                                class="w-full rounded-xl border border-white/10 bg-[#111] px-3 py-2.5 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                            />
                            <template x-if="gcashSenderMobile && !validatePhilippineMobile(gcashSenderMobile)">
                                <div class="mt-1 text-xs text-rose-400">Please enter a valid Philippine mobile number (09XXXXXXXXX)</div>
                            </template>
                        </div>

                        <template x-if="gcashModalError">
                            <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200" x-text="gcashModalError"></div>
                        </template>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white/80 hover:bg-white/10" x-on:click="closeGcashModal()">
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-blue-600/35 bg-[#007bff] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#0069d9]"
                            x-on:click="saveGcashDetails()"
                        >
                            Save GCash Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <div class="fixed inset-0 z-[60] grid place-items-center px-4 pointer-events-none" x-show="successOpen" x-cloak x-transition.opacity.duration.150ms>
        <div class="w-full max-w-sm rounded-2xl border border-white/10 bg-[#111]/95 px-6 py-4 text-center shadow-2xl backdrop-blur">
            <div class="text-base font-semibold text-white" x-text="successMessage"></div>
        </div>
    </div>

    <template x-if="productModalOpen">
        <div class="fixed inset-0 z-50" x-on:keydown.escape.window="closeProductModal()">
            <div class="absolute inset-0 bg-black/70" x-transition.opacity x-on:click="closeProductModal()"></div>
            <div class="absolute inset-0 grid place-items-center px-4">
                <div class="w-full max-w-md rounded-2xl border border-white/10 bg-[#111] p-6 shadow-2xl" x-transition x-on:click.stop>
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="text-lg font-semibold truncate" x-text="modalProduct?.name || ''"></div>
                        <div class="mt-1 text-sm text-white/60">Select size</div>
                    </div>
                    <button
                        type="button"
                        class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 hover:bg-white/10"
                        x-on:click="closeProductModal()"
                        aria-label="Close"
                        title="Close"
                    >
                        ✕
                    </button>
                </div>

                <div class="mt-4 flex items-start justify-center">
                    <img
                        :src="modalProduct ? productImageSrc(modalProduct) : ((window.__assetBaseUrl || '/') + 'images/coffee-doodle.png')"
                        :alt="modalProduct?.name || 'Product image'"
                        x-on:error="if (!$el.dataset.fallbackTried) { $el.dataset.fallbackTried = '1'; $el.src = (window.__assetBaseUrl || '/') + 'images/coffee-doodle.png'; }"
                        class="max-h-72 w-auto object-contain drop-shadow-xl"
                        loading="lazy"
                        style="image-rendering: -webkit-optimize-contrast;"
                    />
                </div>

                <div class="mt-5 space-y-2">
                    <template x-if="!modalProduct || !Array.isArray(modalProduct.sizes) || modalProduct.sizes.length === 0">
                        <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-4 text-sm text-white/60">
                            No sizes available
                        </div>
                    </template>

                    <template x-if="modalProduct && Array.isArray(modalProduct.sizes) && modalProduct.sizes.length > 0">
                        <div class="space-y-2">
                            <template
                                x-for="(size, idx) in modalProduct.sizes"
                                :key="String(size.id || '') + '-' + idx"
                            >
                                <button
                                    type="button"
                                    class="w-full flex items-center justify-between px-4 py-3 rounded-xl bg-black/40 border border-white/10 hover:bg-black/60 transition text-white text-lg font-medium"
                                    x-on:click="add(modalProduct.name, size); closeProductModal()"
                                >
                                    <span x-text="size.size"></span>
                                    <span>₱<span x-text="formatPrice(size.price)"></span></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection
