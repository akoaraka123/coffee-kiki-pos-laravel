@extends('layouts.dashboard')

@section('title', 'Orders')

@section('content')
    <div class="space-y-6">
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-5 py-4 font-medium">{{ $order->order_number }}</td>
                                <td class="px-5 py-4 text-white/70">{{ $order->customer_name ?? '—' }}</td>
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
                                <td class="px-5 py-6 text-white/60" colspan="5">No orders found.</td>
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
