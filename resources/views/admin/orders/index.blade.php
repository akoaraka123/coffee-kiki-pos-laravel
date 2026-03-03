@extends('layouts.dashboard')

@section('title', 'Orders')

@section('content')
    <div class="space-y-6" x-data="adminOrders" data-details-json-url="{{ route('admin.orders.details-json') }}">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Orders</h2>
                <p class="mt-1 text-sm text-white/50">Read-only order history for monitoring and reports.</p>
            </div>

            <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center gap-3">
                <select name="staff" class="w-56 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-white/80 focus:outline-none focus:ring-2 focus:ring-white/20">
                    <option value="" {{ empty($staffId) ? 'selected' : '' }}>All staff</option>
                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}" {{ (string) $staffId === (string) $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                    Filter
                </button>

                <input type="hidden" name="summary" value="today" />
                <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 shadow-sm hover:bg-white/10">
                    Today Sales
                </button>
            </form>
        </div>

        @if ($summary === 'today')
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-sm font-semibold text-white/80">Today Sales</div>
                <div class="mt-1 text-xs text-white/50">
                    {{ $staffId ? 'Selected staff' : 'All staff' }} • Paid orders only
                </div>
                <div class="mt-3 text-2xl font-bold">₱{{ number_format((float) ($todaySales ?? 0), 2) }}</div>
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-white/10 bg-white/5 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-white/5 text-white/70">
                        <tr>
                            <th class="px-5 py-4 font-medium">Date</th>
                            <th class="px-5 py-4 font-medium">Staff</th>
                            <th class="px-5 py-4 font-medium">Total Orders</th>
                            <th class="px-5 py-4 font-medium">Total Items</th>
                            <th class="px-5 py-4 font-medium">Total Sales</th>
                            <th class="px-5 py-4 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($summaries as $row)
                            <tr>
                                <td class="px-5 py-4 font-medium">{{ $row['date_display'] ?? $row['date'] }}</td>
                                <td class="px-5 py-4 text-white/70">{{ $row['staff_name'] }}</td>
                                <td class="px-5 py-4 text-white/70">{{ number_format((int) $row['total_orders']) }}</td>
                                <td class="px-5 py-4 text-white/70">{{ number_format((int) $row['total_items']) }}</td>
                                <td class="px-5 py-4">₱{{ number_format((float) $row['total_sales'], 2) }}</td>
                                <td class="px-5 py-4">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-white/80 shadow-sm hover:bg-white/10"
                                        x-on:click="openDaily('{{ $row['date'] }}', '{{ $row['staff_id'] }}')"
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
            {{ $summaries->links() }}
        </div>

        <div class="fixed inset-0 z-50" x-show="dailyModalOpen" x-cloak>
        <div class="absolute inset-0 bg-black/70" x-transition.opacity x-on:click="closeDaily()"></div>
        <div class="absolute inset-0 grid place-items-center px-4">
            <div class="w-full max-w-6xl overflow-hidden rounded-2xl border border-white/10 bg-[#111] shadow-2xl" x-transition x-on:keydown.escape.window="closeDaily()">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-white/10 bg-[#111] px-6 py-4">
                    <div class="min-w-0">
                        <div class="text-lg font-semibold truncate">Daily Sales Details</div>
                        <div class="mt-0.5 text-xs text-white/60" x-text="dailyPayload ? ((dailyPayload.date_display || dailyPayload.date) + ' — ' + dailyPayload.staff.name) : ''"></div>
                    </div>
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white/80 hover:bg-white/10" x-on:click="closeDaily()">
                        ✕
                    </button>
                </div>

                <div class="max-h-[75vh] overflow-y-auto overflow-x-hidden px-6 py-5">
                    <template x-if="dailyLoading">
                        <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/70">Loading details...</div>
                    </template>

                    <template x-if="dailyError">
                        <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200" x-text="dailyError"></div>
                    </template>

                    <template x-if="dailyPayload && !dailyLoading">
                        <div class="space-y-5">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-sm">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                                        <div class="text-xs text-white/60">Total Orders</div>
                                        <div class="mt-2 text-2xl font-bold" x-text="Number(dailyPayload.summary.total_orders).toLocaleString()"></div>
                                    </div>
                                    <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                                        <div class="text-xs text-white/60">Total Items Sold</div>
                                        <div class="mt-2 text-2xl font-bold" x-text="Number(dailyPayload.summary.total_items).toLocaleString()"></div>
                                    </div>
                                    <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                                        <div class="text-xs text-white/60">Total Sales Amount</div>
                                        <div class="mt-2 text-2xl font-bold">₱<span x-text="formatPrice(dailyPayload.summary.total_sales)"></span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <template x-for="order in dailyPayload.orders" :key="order.id">
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-sm">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <div class="text-sm font-semibold" x-text="'Order #' + order.order_number"></div>

                                                    <template x-if="(order.item_edited_count || 0) > 0">
                                                        <span class="inline-flex items-center rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-200">Modified</span>
                                                    </template>

                                                    <template x-if="(order.item_deleted_count || 0) > 0">
                                                        <span class="inline-flex items-center rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-200">Item Deleted</span>
                                                    </template>
                                                </div>

                                                <div class="mt-1 text-xs text-white/60">
                                                    Created: <span class="text-white/80" x-text="order.created_at || '—'"></span>
                                                    <span class="text-white/30">•</span>
                                                    Status: <span class="text-white/80" x-text="order.status"></span>
                                                    <span class="text-white/30">•</span>
                                                    Payment: <span class="text-white/80" x-text="(order.payment_type || '—').toUpperCase()"></span>
                                                    <span class="text-white/30">•</span>
                                                    Amount: <span class="text-white/80" x-text="'₱' + formatPrice(order.total) + (order.payment_type === 'cash' ? ('/₱' + formatPrice(order.cash_received || 0)) : '')"></span>
                                                    <span class="text-white/30">•</span>
                                                    Change: <span class="text-white/80" x-text="order.payment_type === 'cash' ? ('₱' + formatPrice(order.change_amount || 0)) : '—'"></span>
                                                </div>
                                            </div>

                                            <div class="sm:text-right">
                                                <div class="text-xs text-white/60">Subtotal</div>
                                                <div class="mt-1 text-lg font-bold">₱<span x-text="formatPrice(order.total)"></span></div>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <div class="text-xs font-semibold text-white/60">Items</div>
                                            <div class="mt-2 overflow-hidden rounded-xl border border-white/10">
                                                <table class="min-w-full text-left text-sm">
                                                    <thead class="bg-white/5 text-white/70">
                                                        <tr>
                                                            <th class="px-4 py-3 font-medium">Item</th>
                                                            <th class="px-4 py-3 font-medium">Size</th>
                                                            <th class="px-4 py-3 font-medium">Qty</th>
                                                            <th class="px-4 py-3 font-medium">Price</th>
                                                            <th class="px-4 py-3 font-medium">Line Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-white/10">
                                                        <template x-for="item in (order.items || []).filter(i => !i.deleted_at)" :key="item.id">
                                                            <tr>
                                                                <td class="px-4 py-3 font-medium" x-text="item.name"></td>
                                                                <td class="px-4 py-3 text-white/70" x-text="item.size || '—'"></td>
                                                                <td class="px-4 py-3 text-white/70" x-text="'x' + item.quantity"></td>
                                                                <td class="px-4 py-3 text-white/70">₱<span x-text="formatPrice(item.price)"></span></td>
                                                                <td class="px-4 py-3">₱<span x-text="formatPrice(item.line_total)"></span></td>
                                                            </tr>
                                                        </template>
                                                        <template x-if="(order.items || []).filter(i => !i.deleted_at).length === 0">
                                                            <tr>
                                                                <td class="px-4 py-6 text-center text-white/60" colspan="5">No active items.</td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <template x-if="(order.items || []).some(i => i.deleted_at)">
                                                <div class="mt-4">
                                                    <div class="text-xs font-semibold text-rose-200">Deleted Items</div>
                                                    <div class="mt-2 overflow-hidden rounded-xl border border-rose-500/30 bg-rose-500/5">
                                                        <table class="min-w-full text-left text-sm">
                                                            <thead class="bg-rose-500/10 text-rose-100">
                                                                <tr>
                                                                    <th class="px-4 py-3 font-medium">Item</th>
                                                                    <th class="px-4 py-3 font-medium">Size</th>
                                                                    <th class="px-4 py-3 font-medium">Qty</th>
                                                                    <th class="px-4 py-3 font-medium">Price</th>
                                                                    <th class="px-4 py-3 font-medium">Line Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-rose-500/20">
                                                                <template x-for="item in (order.items || []).filter(i => i.deleted_at)" :key="item.id">
                                                                    <tr>
                                                                        <td class="px-4 py-3 font-medium">
                                                                            <span x-text="item.name"></span>
                                                                            <span class="ml-2 inline-flex items-center rounded-full border border-rose-500/30 bg-rose-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-rose-200">Deleted</span>
                                                                        </td>
                                                                        <td class="px-4 py-3 text-rose-100/80" x-text="item.size || '—'"></td>
                                                                        <td class="px-4 py-3 text-rose-100/80" x-text="'x' + item.quantity"></td>
                                                                        <td class="px-4 py-3 text-rose-100/80">₱<span x-text="formatPrice(item.price)"></span></td>
                                                                        <td class="px-4 py-3 text-rose-100/90">₱<span x-text="formatPrice(item.line_total)"></span></td>
                                                                    </tr>
                                                                </template>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <details class="mt-4 rounded-xl border border-white/10 bg-black/30 p-4">
                                            <summary class="cursor-pointer text-sm font-semibold text-white/80">Order Activity Log</summary>
                                            <div class="mt-3 space-y-3">
                                                <template x-if="(order.activities || []).length === 0">
                                                    <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/60">No activity recorded.</div>
                                                </template>
                                                <template x-for="act in (order.activities || [])" :key="act.id">
                                                    <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                            <div class="text-sm font-semibold" x-text="(act.created_at || '—') + ' — ' + (act.actor_name || 'System')"></div>
                                                            <div class="text-xs font-semibold text-white/60" x-text="act.action"></div>
                                                        </div>
                                                        <template x-if="act.note">
                                                            <div class="mt-2 text-sm text-white/70">Note: <span class="text-white/90" x-text="act.note"></span></div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </details>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="sticky bottom-0 z-10 flex items-center justify-end gap-3 border-t border-white/10 bg-[#111] px-6 py-4">
                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 hover:bg-white/10" x-on:click="closeDaily()">Close</button>
                </div>
            </div>
        </div>
        </div>
    </div>

@endsection
