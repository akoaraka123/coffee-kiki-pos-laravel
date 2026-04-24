@extends('layouts.dashboard')

@section('title', 'Inventory History')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Inventory History</h2>
                <p class="mt-1 text-sm text-white/50">Track all stock additions and deductions.</p>
            </div>

            <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 shadow-sm hover:bg-white/10">
                Back to Inventory
            </a>
        </div>

        <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form method="GET" action="{{ route('admin.inventory.history') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <select name="inventory" class="rounded-xl bg-white/5 border border-white/10 px-4 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-white/20">
                        <option value="">All Products</option>
                        @foreach($inventories as $inv)
                            <option value="{{ $inv->id }}" {{ $selectedInventory == $inv->id ? 'selected' : '' }}>{{ $inv->product_name }} ({{ $inv->size ?? 'Regular' }})</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                        Filter
                    </button>
                    @if($selectedInventory)
                        <a href="{{ route('admin.inventory.history') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            <div class="mt-4 overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-white/5 text-white/70">
                            <tr>
                                <th class="px-4 py-3 font-medium">Date & Time</th>
                                <th class="px-4 py-3 font-medium">Product</th>
                                <th class="px-4 py-3 font-medium">Size</th>
                                <th class="px-4 py-3 font-medium">Action</th>
                                <th class="px-4 py-3 font-medium">Quantity</th>
                                <th class="px-4 py-3 font-medium">User</th>
                                <th class="px-4 py-3 font-medium">Order</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            @forelse($histories as $history)
                                <tr>
                                    <td class="px-4 py-3 text-white/70">{{ $history->created_at->format('M j, Y g:i A') }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $history->product_name }}</td>
                                    <td class="px-4 py-3 text-white/70">{{ $history->size ?? 'Regular' }}</td>
                                    <td class="px-4 py-3">
                                        @if($history->action_type === 'ADD_STOCK')
                                            <span class="inline-flex items-center rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-200">
                                                Added
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-200">
                                                Deducted
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-semibold">{{ $history->quantity }}</td>
                                    <td class="px-4 py-3 text-white/70">{{ $history->user_name }}</td>
                                    <td class="px-4 py-3 text-white/70">{{ $history->order ? $history->order->order_number : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-white/60">
                                        <div class="text-4xl mb-2">📜</div>
                                        <div>No history records found.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($histories->hasPages())
                <div class="mt-4 flex items-center justify-between">
                    <div class="text-sm text-white/50">
                        Showing {{ $histories->firstItem() }} to {{ $histories->lastItem() }} of {{ $histories->total() }} results
                    </div>
                    {{ $histories->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
