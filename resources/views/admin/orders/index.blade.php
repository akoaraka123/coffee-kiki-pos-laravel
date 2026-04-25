@extends('layouts.dashboard')

@section('title', 'Orders')

@section('content')
    <div class="space-y-6" x-data="adminOrders" data-details-json-url="{{ route('admin.orders.details-json') }}">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Orders</h2>
                <p class="mt-1 text-sm text-white/50">Read-only order history for monitoring and reports.</p>
            </div>

            <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <select name="staff" class="w-full sm:w-56 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-white/80 focus:outline-none focus:ring-2 focus:ring-white/20">
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

                <button type="button" x-on:click="deleteTodaySales('{{ $staffId ?? '' }}')" x-bind:disabled="deletingToday" class="inline-flex items-center justify-center rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-sm font-semibold text-rose-200 shadow-sm hover:bg-rose-500/20 disabled:opacity-60">
                    <span x-show="!deletingToday">Delete Today Sales</span>
                    <span x-show="deletingToday" x-cloak>Deleting...</span>
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
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" x-transition.opacity x-on:click="closeDaily()"></div>
        <div class="absolute inset-0 grid place-items-center px-4">
            <div class="w-full max-w-6xl max-h-[90vh] overflow-hidden rounded-2xl border border-white/10 bg-[#111] shadow-2xl flex flex-col" x-transition x-on:keydown.escape.window="closeDaily()">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-white/10 bg-[#111] px-6 py-4">
                    <div class="min-w-0">
                        <div class="text-lg font-semibold truncate">Daily Sales Details</div>
                        <div class="mt-0.5 text-xs text-white/60" x-text="dailyPayload ? ((dailyPayload.date_display || dailyPayload.date) + ' — ' + dailyPayload.staff.name) : ''"></div>
                    </div>
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white/80 hover:bg-white/10" x-on:click="closeDaily()">
                        ✕
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-5">
                    <template x-if="dailyLoading">
                        <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/70">Loading details...</div>
                    </template>

                    <template x-if="dailyError">
                        <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200" x-text="dailyError"></div>
                    </template>

                    <template x-if="dailyPayload && !dailyLoading">
                        <div class="space-y-5">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="rounded-xl border border-purple-500/20 bg-purple-500/10 p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500/20">
                                            <svg class="h-5 w-5 text-purple-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-xs text-white/60">Total Orders</div>
                                            <div class="mt-1 text-xl font-bold" x-text="Number(dailyPayload.summary.total_orders).toLocaleString()"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/20">
                                            <svg class="h-5 w-5 text-emerald-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-xs text-white/60">Total Sales</div>
                                            <div class="mt-1 text-xl font-bold">₱<span x-text="formatPrice(dailyPayload.summary.total_sales)"></span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/20">
                                            <svg class="h-5 w-5 text-emerald-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-xs text-white/60">Cash Payments</div>
                                            <div class="mt-1 text-xl font-bold">₱<span x-text="formatPrice((dailyPayload.summary?.paid_sales?.cash || 0))"></span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-blue-500/20 bg-blue-500/10 p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/20">
                                            <svg class="h-5 w-5 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-xs text-white/60">GCash Payments</div>
                                            <div class="mt-1 text-xl font-bold">₱<span x-text="formatPrice((dailyPayload.summary?.paid_sales?.gcash || 0))"></span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <div class="text-sm font-semibold">Money Inventory Breakdown</div>
                                        <div class="mt-1 text-xs text-white/60">Cash denominations on hand</div>
                                    </div>
                                    <div class="text-sm font-semibold text-white">
                                        Total: ₱<span x-text="formatPrice(dailyPayload.money_inventory?.total_cash || 0)"></span>
                                    </div>
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-6">
                                    <template x-for="b in (dailyPayload.money_inventory?.breakdown || []).filter(x => (x.quantity || 0) > 0)" :key="'mi-' + b.denomination">
                                        <div class="rounded-xl border border-white/10 bg-[#111]/40 px-3 py-2 text-xs text-white/80">
                                            <div class="font-semibold" x-text="'₱' + Number(b.denomination || 0).toLocaleString()"></div>
                                            <div class="text-white/60" x-text="'Qty: ' + Number(b.quantity || 0)"></div>
                                        </div>
                                    </template>
                                    <template x-if="(dailyPayload.money_inventory?.breakdown || []).filter(x => (x.quantity || 0) > 0).length === 0">
                                        <div class="col-span-2 sm:col-span-4 lg:col-span-6 rounded-xl border border-white/10 bg-[#111]/40 px-3 py-2 text-xs text-white/60">No money inventory saved.</div>
                                    </template>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <template x-for="order in dailyPayload.orders" :key="order.id">
                                    <div class="rounded-xl border border-white/10 bg-white/5 overflow-hidden">
                                        <button type="button" x-on:click="toggleOrder(order.id)" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left hover:bg-white/5 transition">
                                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/5">
                                                    <svg class="h-4 w-4 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                </div>
                                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                                    <span class="text-sm font-semibold truncate" x-text="'Order #' + order.order_number"></span>
                                                    <template x-if="(order.item_edited_count || 0) > 0">
                                                        <span class="inline-flex items-center rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[11px] font-semibold text-amber-200">Modified</span>
                                                    </template>
                                                    <template x-if="(order.item_deleted_count || 0) > 0">
                                                        <span class="inline-flex items-center rounded-full border border-rose-500/30 bg-rose-500/10 px-2 py-0.5 text-[11px] font-semibold text-rose-200">Deleted</span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-white/60" x-text="order.created_at ? order.created_at.split(' – ')[1] : '—'"></span>
                                                <span class="inline-flex items-center rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-200" x-text="order.status"></span>
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold" x-bind:class="order.payment_type === 'cash' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' : 'border-blue-500/30 bg-blue-500/10 text-blue-200'" x-text="(order.payment_type || '—').toUpperCase()"></span>
                                                <span class="text-sm font-semibold">₱<span x-text="formatPrice(order.total)"></span></span>
                                                <svg class="h-4 w-4 text-white/60 transition-transform" x-bind:class="expandedOrderId === order.id ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </button>

                                        <template x-if="expandedOrderId === order.id">
                                            <div class="border-t border-white/10 p-4 space-y-4">
                                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                                    <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                                                        <div class="text-xs font-semibold text-white/60">Order Information</div>
                                                        <div class="mt-3 space-y-2">
                                                            <div class="flex justify-between">
                                                                <span class="text-xs text-white/60">Order #</span>
                                                                <span class="text-xs font-semibold text-white" x-text="order.order_number"></span>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <span class="text-xs text-white/60">Staff</span>
                                                                <span class="text-xs font-semibold text-white" x-text="dailyPayload.staff.name"></span>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <span class="text-xs text-white/60">Date & Time</span>
                                                                <span class="text-xs font-semibold text-white" x-text="order.created_at || '—'"></span>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <span class="text-xs text-white/60">Payment Type</span>
                                                                <span class="text-xs font-semibold text-white" x-text="(order.payment_type || '—').toUpperCase()"></span>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <span class="text-xs text-white/60">Total</span>
                                                                <span class="text-xs font-semibold text-white">₱<span x-text="formatPrice(order.total)"></span></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                                                        <div class="text-xs font-semibold text-white/60">Payment Details</div>
                                                        <template x-if="order.payment_type === 'cash'">
                                                            <div class="mt-3 space-y-2">
                                                                <div class="flex justify-between">
                                                                    <span class="text-xs text-white/60">Cash Received</span>
                                                                    <span class="text-xs font-semibold text-white">₱<span x-text="formatPrice(order.cash_received || 0)"></span></span>
                                                                </div>
                                                                <div class="flex justify-between">
                                                                    <span class="text-xs text-white/60">Change</span>
                                                                    <span class="text-xs font-semibold text-white">₱<span x-text="formatPrice(order.change_amount || 0)"></span></span>
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <template x-if="order.payment_type === 'gcash'">
                                                            <div class="mt-3 space-y-2">
                                                                <div class="text-xs font-semibold text-blue-200 mb-2">GCash Payment Details</div>
                                                                <div class="flex justify-between">
                                                                    <span class="text-xs text-white/60">Reference Number</span>
                                                                    <span class="text-xs font-semibold text-white" x-text="order.gcash_reference || '—'"></span>
                                                                </div>
                                                                <div class="flex justify-between">
                                                                    <span class="text-xs text-white/60">Sender Name</span>
                                                                    <span class="text-xs font-semibold text-white" x-text="order.gcash_sender_name || '—'"></span>
                                                                </div>
                                                                <div class="flex justify-between">
                                                                    <span class="text-xs text-white/60">Sender Mobile</span>
                                                                    <span class="text-xs font-semibold text-white" x-text="order.gcash_sender_mobile || '—'"></span>
                                                                </div>
                                                                <div class="flex justify-between items-center">
                                                                    <span class="text-xs text-white/60">Proof</span>
                                                                    <template x-if="order.gcash_proof_image">
                                                                        <button type="button" x-on:click="openImagePreview(order.gcash_proof_image)" class="h-12 w-12 rounded-lg border border-blue-400/30 bg-blue-500/20 overflow-hidden hover:bg-blue-500/30 transition">
                                                                            <img :src="order.gcash_proof_image" class="h-full w-full object-cover" alt="Proof" />
                                                                        </button>
                                                                    </template>
                                                                    <template x-if="!order.gcash_proof_image">
                                                                        <span class="text-xs text-white/60">No proof image</span>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                                <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                                                    <div class="text-xs font-semibold text-white/60 mb-3">Order Items</div>
                                                    <div class="overflow-x-auto">
                                                        <table class="min-w-full text-left text-sm">
                                                            <thead class="bg-white/5 text-white/70">
                                                                <tr>
                                                                    <th class="px-3 py-2 font-medium">Item</th>
                                                                    <th class="px-3 py-2 font-medium">Size</th>
                                                                    <th class="px-3 py-2 font-medium">Qty</th>
                                                                    <th class="px-3 py-2 font-medium">Price</th>
                                                                    <th class="px-3 py-2 font-medium">Line Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-white/10">
                                                                <template x-for="item in (order.items || []).filter(i => !i.deleted_at)" :key="item.id">
                                                                    <tr>
                                                                        <td class="px-3 py-2 font-medium text-xs" x-text="item.name"></td>
                                                                        <td class="px-3 py-2 text-white/70 text-xs" x-text="item.size || '—'"></td>
                                                                        <td class="px-3 py-2 text-white/70 text-xs" x-text="'x' + item.quantity"></td>
                                                                        <td class="px-3 py-2 text-white/70 text-xs">₱<span x-text="formatPrice(item.price)"></span></td>
                                                                        <td class="px-3 py-2 text-xs">₱<span x-text="formatPrice(item.line_total)"></span></td>
                                                                    </tr>
                                                                </template>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                                                    <div class="text-xs font-semibold text-white/60 mb-3">Order Activity Log</div>
                                                    <div class="space-y-3">
                                                        <template x-if="(order.activities || []).length === 0">
                                                            <div class="text-xs text-white/60">No activity recorded.</div>
                                                        </template>
                                                        <template x-for="act in (order.activities || [])" :key="act.id">
                                                            <div class="flex items-start gap-3">
                                                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-500/20">
                                                                    <div class="h-2 w-2 rounded-full bg-blue-400"></div>
                                                                </div>
                                                                <div class="flex-1 min-w-0">
                                                                    <div class="flex items-center justify-between gap-2">
                                                                        <span class="text-xs font-semibold text-white" x-text="act.action"></span>
                                                                        <span class="text-[11px] text-white/60" x-text="act.created_at || '—'"></span>
                                                                    </div>
                                                                    <template x-if="act.actor_name">
                                                                        <div class="text-[11px] text-white/60" x-text="act.actor_name"></div>
                                                                    </template>
                                                                    <template x-if="act.note">
                                                                        <div class="text-[11px] text-white/70 mt-1">Note: <span x-text="act.note"></span></div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
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

    <template x-if="imagePreviewOpen">
        <div class="fixed inset-0 z-[60] flex items-center justify-center px-4">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" x-transition.opacity x-on:click="closeImagePreview()"></div>
            <div class="relative w-full max-w-4xl">
                <button type="button" class="absolute -top-12 right-0 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80 hover:bg-white/10" x-on:click="closeImagePreview()">✕</button>
                <img :src="imagePreviewUrl" class="w-full rounded-xl border border-white/10 shadow-2xl" alt="Transaction Proof" />
            </div>
        </div>
    </template>

@endsection
