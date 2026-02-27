@extends('layouts.dashboard')

@section('title', 'Orders')

@section('content')
    <div class="space-y-6">
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
                            <th class="px-5 py-4 font-medium">Order #</th>
                            <th class="px-5 py-4 font-medium">Staff</th>
                            <th class="px-5 py-4 font-medium">Customer</th>
                            <th class="px-5 py-4 font-medium">Items</th>
                            <th class="px-5 py-4 font-medium">Total</th>
                            <th class="px-5 py-4 font-medium">Status</th>
                            <th class="px-5 py-4 font-medium">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-5 py-4 font-medium">{{ $order->order_number }}</td>
                                <td class="px-5 py-4 text-white/70">{{ $order->creator?->name ?? '—' }}</td>
                                <td class="px-5 py-4 text-white/70">{{ $order->customer_name ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <div class="space-y-1">
                                        @foreach ($order->items as $item)
                                            <div class="text-xs text-white/70">
                                                <span class="font-semibold text-white/80">{{ $item->quantity }}x</span>
                                                {{ $item->name }}
                                                <span class="text-white/40">(₱{{ number_format((float) $item->price, 2) }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-4">{{ number_format((float) $order->total, 2) }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/80">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-white/70">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-5 py-6 text-white/60" colspan="7">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $orders->links() }}
        </div>
    </div>
@endsection
