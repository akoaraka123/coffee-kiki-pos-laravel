@extends('layouts.dashboard')

@section('title', 'Order Details')

@section('content')
    <div class="mx-auto w-full max-w-[980px] space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold">Order #{{ $order->order_number }}</h2>

                    @if ($hasEdits)
                        <span class="inline-flex items-center rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-200">
                            Modified
                        </span>
                    @endif

                    @if ($hasDeletes)
                        <span class="inline-flex items-center rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-200">
                            Item Deleted
                        </span>
                    @endif
                </div>
                <div class="mt-1 text-sm text-white/60">Full order record and activity log.</div>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="shrink-0 inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 shadow-sm hover:bg-white/10">
                Back
            </a>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <div class="text-xs font-semibold text-white/60">Date & Time</div>
                    <div class="mt-1 text-sm font-semibold">{{ $order->created_at->format('F j, Y (l) – g:i A') }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-white/60">Staff</div>
                    <div class="mt-1 text-sm font-semibold">{{ $order->creator?->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-white/60">Customer</div>
                    <div class="mt-1 text-sm font-semibold">{{ $order->customer_name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-white/60">Status</div>
                    <div class="mt-1 text-sm font-semibold">{{ $order->status }}</div>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                    <div class="text-xs text-white/60">Payment</div>
                    <div class="mt-2 text-lg font-bold">{{ $order->payment_type ? strtoupper($order->payment_type) : '—' }}</div>
                </div>
                <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                    <div class="text-xs text-white/60">Total</div>
                    <div class="mt-2 text-lg font-bold">₱{{ number_format((float) ($order->total_amount ?? $order->total ?? 0), 2) }}</div>
                </div>
                <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                    <div class="text-xs text-white/60">Cash Received</div>
                    <div class="mt-2 text-lg font-bold">
                        {{ $order->payment_type === 'cash' ? '₱' . number_format((float) ($order->cash_received ?? 0), 2) : '—' }}
                    </div>
                </div>
                <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                    <div class="text-xs text-white/60">Change</div>
                    <div class="mt-2 text-lg font-bold">
                        {{ $order->payment_type === 'cash' ? '₱' . number_format((float) ($order->change_amount ?? 0), 2) : '—' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold">Order Items</div>
                    <div class="mt-1 text-xs text-white/60">Includes soft-deleted items for full history.</div>
                </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-xl border border-white/10">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-white/5 text-white/70">
                        <tr>
                            <th class="px-4 py-3 font-medium">Item</th>
                            <th class="px-4 py-3 font-medium">Size</th>
                            <th class="px-4 py-3 font-medium">Qty</th>
                            <th class="px-4 py-3 font-medium">Unit Price</th>
                            <th class="px-4 py-3 font-medium">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach ($order->items as $item)
                            <tr class="{{ $item->deleted_at ? 'bg-rose-500/5' : '' }}">
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

        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold">Order Activity Log</div>
                    <div class="mt-1 text-xs text-white/60">Full history of edits/deletions made by staff.</div>
                </div>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($order->activities as $activity)
                    <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm font-semibold">
                                {{ $activity->created_at->format('F j, Y (l) – g:i A') }} — {{ $activity->actor?->name ?? 'System' }}
                            </div>
                            <div class="text-xs font-semibold text-white/60">{{ $activity->action }}</div>
                        </div>

                        @if (!empty($activity->note))
                            <div class="mt-2 text-sm text-white/70">
                                Note: <span class="text-white/90">{{ $activity->note }}</span>
                            </div>
                        @endif

                        @if (!empty($activity->meta))
                            <div class="mt-3 overflow-hidden rounded-lg border border-white/10 bg-white/5">
                                <pre class="whitespace-pre-wrap break-words p-3 text-xs text-white/70">{{ json_encode($activity->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-white/10 bg-white/5 px-6 py-10 text-center text-white/60">
                        No activity recorded for this order.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
