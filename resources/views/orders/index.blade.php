@extends('layouts.dashboard')

@section('title', 'Orders')

@section('content')
    <div
        class="space-y-6"
        x-data="orderHistory"
        data-details-url-template="{{ route('orders.details', ['order' => '__ORDER__']) }}"
        data-update-item-url-template="{{ route('orders.items.update', ['order' => '__ORDER__', 'item' => '__ITEM__']) }}"
        data-delete-item-url-template="{{ route('orders.items.delete', ['order' => '__ORDER__', 'item' => '__ITEM__']) }}"
        data-order-totals='@json($orders->getCollection()->mapWithKeys(fn ($o) => [(int) $o->id => (float) ($o->total_amount ?? $o->total ?? 0)]))'
        data-order-statuses='@json($orders->getCollection()->mapWithKeys(fn ($o) => [(int) $o->id => (string) $o->status]))'
    >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Orders</h2>
                <p class="mt-1 text-sm text-white/50">Order history. Use POS for new orders.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <a href="{{ route('pos') }}" class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                    Open POS
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-xs text-white/50">Today Sales</div>
                <div class="mt-2 text-2xl font-semibold">₱{{ number_format((float) ($todaySales ?? 0), 2) }}</div>
                <div class="mt-1 text-xs text-white/35">Paid orders today</div>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-xs text-white/50">Orders Today</div>
                <div class="mt-2 text-2xl font-semibold">{{ (int) ($todayOrders ?? 0) }}</div>
                <div class="mt-1 text-xs text-white/35">All statuses today</div>
            </div>
        </div>

        <div class="rounded-xl border border-white/10 bg-white/5 shadow-sm">
            <div class="flex flex-col gap-2 border-b border-white/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm font-semibold">Daily Summary</div>
                    <div class="mt-1 text-xs text-white/60">Click a date to view all transactions for that day.</div>
                </div>
                @if (!empty($selectedDate))
                    <a href="{{ route('orders.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-white/80 shadow-sm hover:bg-white/10">
                        Clear Filter
                    </a>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-white/5 text-white/70">
                        <tr>
                            <th class="px-5 py-4 font-medium">Date</th>
                            <th class="px-5 py-4 font-medium">Total Orders</th>
                            <th class="px-5 py-4 font-medium">Total Items Sold</th>
                            <th class="px-5 py-4 font-medium">Total Sales</th>
                            <th class="px-5 py-4 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($dailySummaries as $row)
                            <tr class="{{ (string) ($selectedDate ?? '') === (string) $row['date'] ? 'bg-white/5' : '' }}">
                                <td class="px-5 py-4 font-medium">{{ $row['date'] }}</td>
                                <td class="px-5 py-4 text-white/70">{{ number_format((int) $row['total_orders']) }}</td>
                                <td class="px-5 py-4 text-white/70">{{ number_format((int) $row['total_items']) }}</td>
                                <td class="px-5 py-4">₱{{ number_format((float) $row['total_sales'], 2) }}</td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('orders.index', ['date' => $row['date']]) }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-white/80 shadow-sm hover:bg-white/10">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-5 py-6 text-white/60" colspan="5">No summary data yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (!empty($selectedDate))
            <div class="rounded-xl border border-white/10 bg-white/5 px-5 py-4 text-sm text-white/70">
                Showing transactions for <span class="font-semibold text-white">{{ $selectedDate }}</span>
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-white/10 bg-white/5 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-white/5 text-white/70">
                        <tr>
                            <th class="px-5 py-4 font-medium">Order #</th>
                            <th class="px-5 py-4 font-medium">Customer</th>
                            <th class="px-5 py-4 font-medium">Total</th>
                            <th class="px-5 py-4 font-medium">Status</th>
                            <th class="px-5 py-4 font-medium">Created</th>
                            <th class="px-5 py-4 font-medium">View</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-5 py-4 font-medium">{{ $order->order_number }}</td>
                                <td class="px-5 py-4 text-white/70">{{ $order->customer_name ?? '—' }}</td>
                                <td class="px-5 py-4">₱<span x-text="formatPrice(orderTotals[{{ (int) $order->id }}] ?? {{ (float) ($order->total_amount ?? $order->total ?? 0) }})"></span></td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/80">
                                        <span x-text="orderStatuses[{{ (int) $order->id }}] ?? '{{ $order->status }}'"></span>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-white/70">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-5 py-4">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-white/80 shadow-sm hover:bg-white/10"
                                        x-on:click="openOrder({{ (int) $order->id }})"
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-5 py-6 text-white/60" colspan="6">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $orders->links() }}
        </div>

        <div class="fixed inset-0 z-50" x-show="modalOpen" x-cloak>
        <div class="absolute inset-0 bg-black/70" x-transition.opacity x-on:click="closeModal()"></div>
        <div class="absolute inset-0 grid place-items-center px-4">
            <div class="w-full max-w-5xl overflow-hidden rounded-2xl border border-white/10 bg-[#111] shadow-2xl" x-transition x-on:keydown.escape.window="closeModal()">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-white/10 bg-[#111] px-6 py-4">
                    <div class="min-w-0">
                        <div class="text-lg font-semibold truncate">Order Details</div>
                        <div class="mt-0.5 text-xs text-white/60" x-text="selectedOrder ? ('Order #' + selectedOrder.order_number) : ''"></div>
                    </div>
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white/80 hover:bg-white/10" x-on:click="closeModal()">
                        ✕
                    </button>
                </div>

                <div class="max-h-[75vh] overflow-y-auto overflow-x-hidden px-6 py-5">
                    <template x-if="loading">
                        <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/70">
                            Loading order details...
                        </div>
                    </template>

                    <template x-if="errorMessage">
                        <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200" x-text="errorMessage"></div>
                    </template>

                    <template x-if="selectedOrder && !loading">
                        <div class="space-y-5">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                    <div class="text-xs font-semibold text-white/60">Order #</div>
                                    <div class="mt-1 text-sm font-semibold" x-text="selectedOrder.order_number"></div>
                                    <div class="mt-3 text-xs font-semibold text-white/60">Date & Time</div>
                                    <div class="mt-1 text-sm text-white/80" x-text="selectedOrder.created_at"></div>
                                </div>
                                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                    <div class="text-xs font-semibold text-white/60">Staff</div>
                                    <div class="mt-1 text-sm font-semibold" x-text="selectedOrder.staff_name || '—'"></div>
                                    <div class="mt-3 text-xs font-semibold text-white/60">Customer</div>
                                    <div class="mt-1 text-sm text-white/80" x-text="selectedOrder.customer_name || '—'"></div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div>
                                        <div class="text-xs font-semibold text-white/60">Payment</div>
                                        <div class="mt-1 text-sm font-semibold" x-text="selectedOrder.payment_type === 'cash' ? 'Cash' : 'GCash'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-white/60">Cash Received</div>
                                        <div class="mt-1 text-sm font-semibold" x-text="selectedOrder.payment_type === 'cash' ? ('₱' + formatPrice(selectedOrder.cash_received || 0)) : '—'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-white/60">Change</div>
                                        <div class="mt-1 text-sm font-semibold" x-text="selectedOrder.payment_type === 'cash' ? ('₱' + formatPrice(selectedOrder.change_amount || 0)) : '—'"></div>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between border-t border-white/10 pt-4">
                                    <div class="text-sm font-semibold text-white/70">Total</div>
                                    <div class="text-xl font-bold">₱<span x-text="formatPrice(selectedOrder.total || 0)"></span></div>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-xl border border-white/10">
                                <table class="min-w-full text-left text-sm">
                                    <thead class="bg-white/5 text-white/70">
                                        <tr>
                                            <th class="px-4 py-3 font-medium">Item</th>
                                            <th class="px-4 py-3 font-medium">Size</th>
                                            <th class="px-4 py-3 font-medium">Qty</th>
                                            <th class="px-4 py-3 font-medium">Price</th>
                                            <th class="px-4 py-3 font-medium">Line Total</th>
                                            <th class="px-4 py-3 font-medium"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/10">
                                        <template x-for="item in selectedOrder.items" :key="item.id">
                                            <tr>
                                                <td class="px-4 py-3 font-medium" x-text="item.name"></td>
                                                <td class="px-4 py-3 text-white/70" x-text="item.size || '—'"></td>

                                                <td class="px-4 py-3">
                                                    <template x-if="editingItemId !== item.id">
                                                        <span class="text-white/80" x-text="'x' + item.quantity"></span>
                                                    </template>
                                                    <template x-if="editingItemId === item.id">
                                                        <input type="number" min="0" class="w-20 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white/90 focus:outline-none focus:ring-2 focus:ring-white/20" x-model.number="editForm.quantity" />
                                                    </template>
                                                </td>

                                                <td class="px-4 py-3">
                                                    <span class="text-white/70">₱<span x-text="formatPrice(item.price)"></span></span>
                                                </td>

                                                <td class="px-4 py-3">₱<span x-text="formatPrice(item.line_total)"></span></td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <template x-if="editingItemId !== item.id">
                                                            <span class="inline-flex items-center gap-2">
                                                                <button type="button" class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-white/80 hover:bg-white/10" x-on:click="startEdit(item)">Edit</button>
                                                                <button type="button" class="rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-xs font-semibold text-rose-200 hover:bg-rose-500/20" x-on:click="deleteItem(item)">Delete</button>
                                                            </span>
                                                        </template>

                                                        <template x-if="editingItemId === item.id">
                                                            <span class="inline-flex items-center gap-2">
                                                                <button type="button" class="rounded-lg bg-[#efe9df] px-3 py-2 text-xs font-semibold text-[#1c1c1c] hover:opacity-95" x-on:click="saveEdit(item)">Save</button>
                                                                <button type="button" class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-white/80 hover:bg-white/10" x-on:click="cancelEdit()">Cancel</button>
                                                            </span>
                                                        </template>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="selectedOrder.items.length === 0">
                                            <tr>
                                                <td class="px-4 py-6 text-center text-white/60" colspan="6">No items in this order.</td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="rounded-xl border border-white/10 bg-white/5 p-4" x-show="editingItemId !== null">
                                <div class="text-xs font-semibold text-white/60">Notes (optional)</div>
                                <input type="text" maxlength="255" class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/90 focus:outline-none focus:ring-2 focus:ring-white/20" placeholder="Reason for edit/delete (optional)" x-model="editForm.note" />
                                <div class="mt-2 text-xs text-white/50">This will be saved in the activity log.</div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="sticky bottom-0 z-10 flex items-center justify-end gap-3 border-t border-white/10 bg-[#111] px-6 py-4">
                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 hover:bg-white/10" x-on:click="closeModal()">
                        Close
                    </button>
                </div>
            </div>
        </div>
        </div>
    </div>

@endsection
