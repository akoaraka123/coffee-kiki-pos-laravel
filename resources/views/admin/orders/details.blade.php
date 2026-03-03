@extends('layouts.dashboard')

@section('title', 'Daily Sales Details')

@section('content')
    <div class="mx-auto w-full max-w-[980px] space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold">Daily Sales Details</h2>
                <div class="mt-1 text-sm text-white/60">Full breakdown of transactions for the selected staff and date.</div>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="shrink-0 inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 shadow-sm hover:bg-white/10">
                Back
            </a>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <div class="text-xs font-semibold text-white/60">Date</div>
                    <div class="mt-1 text-sm font-semibold">{{ $dateDisplay ?? $date }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-white/60">Staff</div>
                    <div class="mt-1 text-sm font-semibold">{{ $staff->name }}</div>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                    <div class="text-xs text-white/60">Total Orders</div>
                    <div class="mt-2 text-2xl font-bold">{{ number_format((int) $totalOrders) }}</div>
                </div>
                <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                    <div class="text-xs text-white/60">Total Items Sold</div>
                    <div class="mt-2 text-2xl font-bold">{{ number_format((int) $totalItems) }}</div>
                </div>
                <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                    <div class="text-xs text-white/60">Total Sales Amount</div>
                    <div class="mt-2 text-2xl font-bold">₱{{ number_format((float) $totalSales, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($orders as $order)
                <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-sm font-semibold">Order #{{ $order->order_number }}</div>

                                @if ((int) ($order->item_edited_count ?? 0) > 0)
                                    <span class="inline-flex items-center rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-200">
                                        Modified
                                    </span>
                                @endif

                                @if ((int) ($order->item_deleted_count ?? 0) > 0)
                                    <span class="inline-flex items-center rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-200">
                                        Item Deleted
                                    </span>
                                @endif
                            </div>
                            <div class="mt-1 text-xs text-white/60">
                                Created: {{ $order->created_at->format('F j, Y (l) – g:i A') }}
                                <span class="text-white/30">•</span>
                                Status: <span class="text-white/80">{{ $order->status }}</span>
                                <span class="text-white/30">•</span>
                                Payment: <span class="text-white/80">{{ $order->payment_type ? strtoupper($order->payment_type) : '—' }}</span>
                                <span class="text-white/30">•</span>
                                Amount: <span class="text-white/80">{{ number_format((float) ($order->total_amount ?? $order->total ?? 0), 0) }} / {{ $order->payment_type === 'cash' ? number_format((float) ($order->cash_received ?? 0), 0) : '—' }}</span>
                                <span class="text-white/30">•</span>
                                Change: <span class="text-white/80">{{ $order->payment_type === 'cash' ? number_format((float) ($order->change_amount ?? 0), 0) : '—' }}</span>
                            </div>
                        </div>

                        <div class="sm:text-right">
                            <div class="text-xs text-white/60">Subtotal</div>
                            <div class="mt-1 text-lg font-bold">₱{{ number_format((float) ($order->total_amount ?? $order->total ?? 0), 2) }}</div>
                            <div class="mt-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-white/80 shadow-sm hover:bg-white/10">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-xl border border-white/10">
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
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td class="px-4 py-3 font-medium">
                                            {{ $item->name }}
                                            @if ($item->deleted_at)
                                                <span class="ml-2 inline-flex items-center rounded-full border border-rose-500/30 bg-rose-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-rose-200">
                                                    Deleted
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-white/70">{{ $item->product?->size ?? '—' }}</td>
                                        <td class="px-4 py-3 text-white/70">x{{ $item->quantity }}</td>
                                        <td class="px-4 py-3 text-white/70">₱{{ number_format((float) $item->price, 2) }}</td>
                                        <td class="px-4 py-3">₱{{ number_format((float) $item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-white/10 bg-white/5 px-6 py-10 text-center text-white/60">
                    No orders found for this staff and date.
                </div>
            @endforelse
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm font-semibold">Daily Summary</div>
                    <div class="mt-1 text-xs text-white/60">Totals for the selected staff and date.</div>
                </div>

                <div class="text-sm font-semibold">
                    Total Orders: <span class="text-white/80">{{ number_format((int) $totalOrders) }}</span>
                    <span class="text-white/30">•</span>
                    Total Items: <span class="text-white/80">{{ number_format((int) $totalItems) }}</span>
                    <span class="text-white/30">•</span>
                    Total Sales: <span class="text-white/80">₱{{ number_format((float) $totalSales, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
