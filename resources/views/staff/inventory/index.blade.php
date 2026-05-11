@extends('layouts.dashboard')

@section('title', 'Inventory Management')

@section('content')
    <div class="space-y-6" x-data="{
        addStockModalOpen: false,
        deleteStockModalOpen: false,
        selectedInventory: null,
        addQuantity: 1,
        deleteQuantity: 1,
        openAddStockModal(id) {
            this.selectedInventory = id;
            this.addQuantity = 1;
            this.addStockModalOpen = true;
        },
        closeAddStockModal() {
            this.addStockModalOpen = false;
            this.selectedInventory = null;
            this.addQuantity = 1;
        },
        openDeleteStockModal(id) {
            this.selectedInventory = id;
            this.deleteQuantity = 1;
            this.deleteStockModalOpen = true;
        },
        closeDeleteStockModal() {
            this.deleteStockModalOpen = false;
            this.selectedInventory = null;
            this.deleteQuantity = 1;
        },
        async submitAddStock() {
            if (!this.selectedInventory) return;

            const form = document.getElementById('addStockForm');
            const formData = new FormData(form);

            try {
                const response = await fetch('{{ route('staff.inventory.add-stock') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to add stock');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        },
        async submitDeleteStock() {
            if (!this.selectedInventory) return;

            try {
                const response = await fetch('{{ route('staff.inventory.delete-stock') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        inventory_id: this.selectedInventory,
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to delete stock');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        }
    }">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Inventory Management</h2>
                <p class="mt-1 text-sm text-white/50">View stock levels and add inventory.</p>
            </div>
            <div class="rounded-xl border border-emerald-600/50 bg-emerald-600/10 px-6 py-3">
                <p class="text-sm text-white/60">Total Stock</p>
                <p class="text-2xl font-semibold text-emerald-400">{{ number_format($totalStock ?? 0) }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form method="GET" action="{{ route('staff.inventory.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <select name="category" class="inventory-category-filter rounded-xl bg-white/5 border border-white/10 px-4 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-white/20">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ $selectedCategory === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="search" placeholder="Search products..." value="{{ $search }}" class="rounded-xl bg-white/5 border border-white/10 px-4 py-2 text-sm text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-white/20">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                        Filter
                    </button>
                    @if($selectedCategory || $search)
                        <a href="{{ route('staff.inventory.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">
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
                                <th class="px-4 py-3 font-medium">Product</th>
                                <th class="px-4 py-3 font-medium">Category</th>
                                <th class="px-4 py-3 font-medium">Size</th>
                                <th class="px-4 py-3 font-medium">Stock</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            @forelse($inventories as $inventory)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if($inventory->product && $inventory->product->image)
                                                <img src="{{ $inventory->product->image ? asset($inventory->product->image) : asset('images/coffee-doodle.png') }}" alt="{{ $inventory->product_name }}" class="h-10 w-10 rounded-lg object-cover" onerror="this.src='{{ asset('images/coffee-doodle.png') }}'">
                                            @else
                                                <div class="h-10 w-10 rounded-lg bg-white/10 flex items-center justify-center">
                                                    <span class="text-lg">☕</span>
                                                </div>
                                            @endif
                                            <div class="font-medium">{{ $inventory->product_name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-white/70">{{ $inventory->category }}</td>
                                    <td class="px-4 py-3 text-white/70">{{ $inventory->size ?? 'Regular' }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ $inventory->stock_quantity }}</td>
                                    <td class="px-4 py-3">
                                        @if($inventory->stock_quantity === 0)
                                            <span class="inline-flex items-center rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-200">
                                                Out of Stock
                                            </span>
                                        @elseif($inventory->stock_quantity <= $inventory->low_stock_threshold)
                                            <span class="inline-flex items-center rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-200">
                                                Low Stock
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-200">
                                                In Stock
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <button type="button" x-on:click="openAddStockModal({{ $inventory->id }})" class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-3 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                                                Add Stock
                                            </button>
                                            <button type="button" x-on:click="openDeleteStockModal({{ $inventory->id }})" class="inline-flex items-center justify-center rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm font-semibold text-rose-200 shadow-sm hover:bg-rose-500/20">
                                                Delete Stock
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-white/60">
                                        <div class="text-4xl mb-2">📦</div>
                                        <div>No inventory items found.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Stock Modal -->
        <template x-if="addStockModalOpen">
            <div class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/60" x-on:click="closeAddStockModal()"></div>
                <div class="relative z-10 w-full max-w-md rounded-2xl border border-white/10 bg-[#1b1b1b] p-6 shadow-xl">
                    <h3 class="text-lg font-semibold">Add Stock</h3>
                    <p class="mt-1 text-sm text-white/50">Enter the quantity to add to inventory.</p>

                    <form id="addStockForm" x-on:submit.prevent="submitAddStock()">
                        @csrf
                        <input type="hidden" name="inventory_id" x-model="selectedInventory">

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-white/70">Quantity to Add</label>
                                <input type="number" name="quantity" x-model="addQuantity" min="1" required class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-white/20">
                            </div>
                        </div>

                        <div class="mt-6 flex gap-3">
                            <button type="button" x-on:click="closeAddStockModal()" class="flex-1 inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">
                                Cancel
                            </button>
                            <button type="submit" class="flex-1 inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                                Add Stock
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Delete Stock Modal -->
    <template x-if="deleteStockModalOpen">
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60" x-on:click="closeDeleteStockModal()"></div>
            <div class="relative z-10 w-full max-w-md rounded-2xl border border-white/10 bg-[#1b1b1b] p-6 shadow-xl">
                <h3 class="text-lg font-semibold">Delete Stock</h3>
                <p class="mt-1 text-sm text-white/50">Delete all stock from this item? This will set stock to 0.</p>

                <div class="mt-6 flex gap-3">
                    <button type="button" x-on:click="closeDeleteStockModal()" class="flex-1 inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">
                        Cancel
                    </button>
                    <button type="button" x-on:click="submitDeleteStock()" class="flex-1 inline-flex items-center justify-center rounded-xl bg-rose-500/20 border border-rose-500/30 px-4 py-2 text-sm font-semibold text-rose-200 shadow-sm hover:bg-rose-500/30">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
