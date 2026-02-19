@extends('layouts.dashboard')

@section('title', 'Staff Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Today</h2>
                <p class="mt-1 text-sm text-white/50">Quick snapshot for staff operations.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <a href="{{ route('orders.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                    New Order
                </a>
                <a href="{{ route('orders.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 shadow-sm hover:bg-white/10">
                    View Orders
                </a>
                <button type="button" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 shadow-sm hover:bg-white/10">
                    View Products
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-xs text-white/50">Total Sales Today</div>
                <div class="mt-2 text-2xl font-semibold">—</div>
                <div class="mt-1 text-xs text-white/35">Placeholder</div>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-xs text-white/50">Orders Today</div>
                <div class="mt-2 text-2xl font-semibold">—</div>
                <div class="mt-1 text-xs text-white/35">Placeholder</div>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-xs text-white/50">Total Products</div>
                <div class="mt-2 text-2xl font-semibold">—</div>
                <div class="mt-1 text-xs text-white/35">Placeholder</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm lg:col-span-2">
                <div class="text-sm font-semibold">Orders</div>
                <div class="mt-1 text-xs text-white/50">Orders table placeholder.</div>

                <div class="mt-4 overflow-hidden rounded-xl border border-white/10">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-white/5 text-white/70">
                            <tr>
                                <th class="px-4 py-3 font-medium">Order</th>
                                <th class="px-4 py-3 font-medium">Customer</th>
                                <th class="px-4 py-3 font-medium">Total</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <tr>
                                <td class="px-4 py-3 text-white/60" colspan="4">No data yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-sm font-semibold">Quick Actions</div>
                <div class="mt-1 text-xs text-white/50">Common staff tasks.</div>

                <div class="mt-4 grid grid-cols-1 gap-3">
                    <a href="{{ route('orders.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                        New Order
                    </a>
                    <a href="{{ route('orders.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">
                        View Orders
                    </a>
                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">
                        View Products
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
