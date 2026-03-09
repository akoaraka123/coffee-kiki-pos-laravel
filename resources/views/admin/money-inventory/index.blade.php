@extends('layouts.dashboard')

@section('title', 'Money Inventory')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Daily Money Inventory</h2>
                <p class="mt-1 text-sm text-white/50">Compare staff cash inventory against daily sales.</p>
            </div>
        </div>

        <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
            <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="text-xs text-white/50">Staff</label>
                    <select name="staff" class="mt-1 w-full rounded-xl border border-white/10 bg-[#111]/40 px-3 py-2 text-sm text-white">
                        <option value="">All staff</option>
                        @foreach ($staffUsers as $staff)
                            <option value="{{ $staff->id }}" @selected($staffId == (string) $staff->id)>{{ $staff->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs text-white/50">Date</label>
                    <input type="date" name="date" value="{{ $date ?? '' }}" class="mt-1 w-full rounded-xl border border-white/10 bg-[#111]/40 px-3 py-2 text-sm text-white" />
                </div>

                <div class="flex items-end gap-3">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.money-inventory.index') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 shadow-sm hover:bg-white/10">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-white/10 bg-white/5 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-white/5 text-white/70">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Staff</th>
                            <th class="px-4 py-3 font-medium">Total Cash</th>
                            <th class="px-4 py-3 font-medium">Breakdown</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($inventories as $row)
                            <tr>
                                <td class="px-4 py-3">{{ $row['date_display'] }}</td>
                                <td class="px-4 py-3 text-white/80">{{ $row['staff_name'] }}</td>
                                <td class="px-4 py-3 font-semibold">₱{{ number_format((float) $row['total_cash'], 0) }}</td>
                                <td class="px-4 py-3">
                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                        @foreach (($row['breakdown'] ?? []) as $b)
                                            @if (($b['quantity'] ?? 0) > 0)
                                                <div class="rounded-xl border border-white/10 bg-[#111]/40 px-3 py-2 text-xs text-white/80">
                                                    <div class="font-semibold">₱{{ number_format((int) $b['denomination']) }}</div>
                                                    <div class="text-white/60">Qty: {{ (int) $b['quantity'] }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-8 text-center text-white/60" colspan="4">No money inventory records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (method_exists($inventories, 'links'))
            <div>
                {{ $inventories->links() }}
            </div>
        @endif
    </div>
@endsection
