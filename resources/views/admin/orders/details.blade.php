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

        <!-- Money Inventory Section (Read-only for Admin) -->
        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-sm">
            <div class="mb-4">
                <h3 class="text-lg font-semibold">Money Inventory (Staff Reconciliation)</h3>
                <p class="mt-1 text-sm text-white/60">Read-only view of staff's money inventory for this date.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Sales -->
                <div class="rounded-xl border border-purple-500/25 bg-purple-500/10 p-5 shadow-sm">
                    <div class="text-xs text-purple-200">Total Sales</div>
                    <div class="mt-2 text-2xl font-bold text-white">₱{{ number_format((float) $totalSales, 2) }}</div>
                    <div class="mt-1 text-xs text-purple-200/70">System recorded paid orders</div>
                </div>

                <!-- Cash Sales -->
                <div class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 p-5 shadow-sm">
                    <div class="text-xs text-emerald-200">Cash Sales</div>
                    <div class="mt-2 text-2xl font-bold text-white">₱{{ number_format((float) $cashSalesTotal, 2) }}</div>
                    <div class="mt-1 text-xs text-emerald-200/70">Expected cash on hand</div>
                </div>

                <!-- GCash Sales -->
                <div class="rounded-xl border border-blue-500/25 bg-blue-500/10 p-5 shadow-sm">
                    <div class="text-xs text-blue-200">GCash Sales</div>
                    <div class="mt-2 text-2xl font-bold text-white">₱{{ number_format((float) $gcashSalesTotal, 2) }}</div>
                    <div class="mt-1 text-xs text-blue-200/70">Expected GCash payments</div>
                </div>

                <!-- Balance Status -->
                <div class="rounded-xl border border-amber-500/25 bg-amber-500/10 p-5 shadow-sm">
                    <div class="text-xs text-amber-200">Balance Status</div>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-2xl font-bold text-white">₱{{ number_format((float) $totalDifference, 2) }}</span>
                        <span class="rounded-full border px-2 py-0.5 text-xs font-semibold
                            @class([
                                'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' => $reconciliationStatus === 'balanced',
                                'border-rose-500/30 bg-rose-500/10 text-rose-200' => $reconciliationStatus === 'short',
                                'border-amber-500/30 bg-amber-500/10 text-amber-200' => $reconciliationStatus === 'over',
                            ])>
                            {{ ucfirst($reconciliationStatus) }}
                        </span>
                    </div>
                    <div class="mt-1 text-xs text-amber-200/70">
                        @if($reconciliationStatus === 'balanced') All amounts are matched
                        @elseif($reconciliationStatus === 'short') Cash/GCash is short
                        @else Cash/GCash is over
                        @endif
                    </div>
                </div>
            </div>

            <!-- Reconciliation Details -->
            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Cash Details -->
                <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                    <h4 class="text-sm font-semibold text-emerald-200">Cash Reconciliation</h4>
                    <div class="mt-3 space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-white/60">Expected Cash</span>
                            <span class="text-white/80">₱{{ number_format((float) $cashSalesTotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-white/60">Staff Counted Cash</span>
                            <span class="text-white/80">₱{{ number_format((float) $countedCashTotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium">
                            <span class="text-white/80">Difference</span>
                            <span @class([
                                'text-rose-300' => $cashDifference < 0,
                                'text-amber-300' => $cashDifference > 0,
                                'text-emerald-300' => $cashDifference == 0,
                            ])>₱{{ number_format(abs($cashDifference), 2) }}</span>
                        </div>
                    </div>

                    @if(!empty($denominationBreakdown))
                    <div class="mt-4 pt-4 border-t border-white/10">
                        <div class="text-xs font-semibold text-white/60 mb-2">Cash Denomination Breakdown</div>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($denominationBreakdown as $denom => $qty)
                            @if($qty > 0)
                            <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs">
                                <div class="font-semibold">₱{{ number_format($denom) }}</div>
                                <div class="text-white/60">Qty: {{ $qty }} (₱{{ number_format($denom * $qty) }})</div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <!-- GCash Details -->
                <div class="rounded-xl border border-white/10 bg-black/30 p-4">
                    <h4 class="text-sm font-semibold text-blue-200">GCash Verification</h4>
                    <div class="mt-3 space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-white/60">Expected GCash</span>
                            <span class="text-white/80">₱{{ number_format((float) $gcashSalesTotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-white/60">Staff Verified GCash</span>
                            <span class="text-white/80">₱{{ number_format((float) $verifiedGcashTotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium">
                            <span class="text-white/80">Difference</span>
                            <span @class([
                                'text-rose-300' => $gcashDifference < 0,
                                'text-amber-300' => $gcashDifference > 0,
                                'text-emerald-300' => $gcashDifference == 0,
                            ])>₱{{ number_format(abs($gcashDifference), 2) }}</span>
                        </div>
                    </div>

                    @if($paymentEntries->where('payment_type', 'gcash')->count() > 0)
                    <div class="mt-4 pt-4 border-t border-white/10">
                        <div class="text-xs font-semibold text-white/60 mb-2">GCash Verification Entries</div>
                        <div class="space-y-2">
                            @foreach($paymentEntries->where('payment_type', 'gcash') as $entry)
                            <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs">
                                <div class="flex justify-between">
                                    <span class="font-semibold">₱{{ number_format($entry->received_amount) }}</span>
                                    <span class="text-white/60">{{ $entry->created_at->format('g:i A') }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
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

                    @if ($order->payment_type === 'gcash')
                        <div class="mt-4 rounded-xl border border-blue-400/20 bg-blue-500/10 p-4">
                            <div class="text-sm font-semibold text-blue-200">GCash Payment Details</div>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <div class="text-xs text-white/60">Reference Number</div>
                                    <div class="mt-1 text-sm font-semibold text-white">{{ $order->gcash_reference ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-white/60">Sender Name</div>
                                    <div class="mt-1 text-sm font-semibold text-white">{{ $order->gcash_sender_name ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-white/60">Sender Mobile Number</div>
                                    <div class="mt-1 text-sm font-semibold text-white">{{ $order->gcash_sender_mobile ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-white/60">Transaction Proof</div>
                                    <div class="mt-1">
                                        @if ($order->gcash_proof_image)
                                            <a href="{{ Storage::disk('public')->url($order->gcash_proof_image) }}" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-blue-400/30 bg-blue-500/20 px-3 py-1.5 text-xs font-semibold text-blue-200 hover:bg-blue-500/30">
                                                View Proof
                                            </a>
                                        @else
                                            <span class="text-sm text-white/60">No proof image available</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
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
