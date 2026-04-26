@extends('layouts.dashboard')

@section('title', 'Money Inventory')

@section('content')
    <div
        class="space-y-6"
        x-data="moneyInventory({
            date: @js($date),
            dateDisplay: @js($dateDisplay ?? $date),
            denominations: @js($denominations),
            quantities: @js($quantities),
            saveUrl: @js(route('staff.money-inventory.save')),
            clockedIn: @js((bool) ($clockedIn ?? false)),
            todaysTotalSales: @js((float) ($todaysTotalSales ?? 0)),
            todaysCashSales: @js((float) ($todaysCashSales ?? 0)),
            todaysGcashSales: @js((float) ($todaysGcashSales ?? 0)),
            lowerTodaysTotalSales: @js((float) ($lowerTodaysTotalSales ?? $todaysTotalSales ?? 0)),
            reconciledToday: @js((bool) ($reconciledToday ?? false)),
            reconciledAt: @js($reconciledAt ?? null),
            paymentDenominations: @js($paymentDenominations ?? []),
            paymentEntries: @js(($paymentEntries ?? collect())->map(fn ($e) => [
                'id' => (int) $e->id,
                'payment_type' => (string) $e->payment_type,
                'received_amount' => (int) $e->received_amount,
                'created_at' => $e->created_at?->toIso8601String(),
                'order_id' => (int) $e->order_id,
                'items' => ($e->items ?? collect())->map(fn ($i) => [
                    'denomination' => (int) $i->denomination,
                    'quantity' => (int) $i->quantity,
                ])->values()->all(),
            ])->values()->all()),
            paymentSaveUrl: @js(route('staff.money-inventory.payment-entries.store')),
            paymentUpdateUrlTemplate: @js(route('staff.money-inventory.payment-entries.update', ['entry' => '__ENTRY__'])),
            paymentDeleteUrlTemplate: @js(route('staff.money-inventory.payment-entries.destroy', ['entry' => '__ENTRY__'])),
            resetTodaysSalesUrl: @js(route('staff.money-inventory.reset-todays-sales')),
            undoReconcileUrl: @js(route('staff.money-inventory.undo-reconcile')),
            gcashOrders: @js(($gcashOrders ?? collect())->map(fn ($o) => [
                'id' => (int) $o->id,
                'order_number' => (string) $o->order_number,
                'total_amount' => (int) $o->total_amount,
                'created_at' => $o->created_at?->toIso8601String(),
                'gcash_reference' => (string) ($o->gcash_reference ?? ''),
                'gcash_sender_name' => (string) ($o->gcash_sender_name ?? ''),
                'gcash_sender_mobile' => (string) ($o->gcash_sender_mobile ?? ''),
                'items' => ($o->items ?? collect())->map(fn ($i) => [
                    'product_name' => (string) ($i->product_name ?? ''),
                    'size' => (string) ($i->size ?? ''),
                    'quantity' => (int) $i->quantity,
                    'price' => (int) $i->price,
                ])->values()->all(),
            ])->values()->all()),
            confirmedOrderIds: @js(is_array($confirmedOrderIds ?? []) ? ($confirmedOrderIds ?? []) : ($confirmedOrderIds ?? collect())->values()->all()),
        })"
        x-init="init()"
    >
        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold">Money Inventory</h2>
                <p class="mt-1 text-sm text-white/50">Reconcile physical cash and GCash payments for today's sales.</p>
                <p class="mt-1 text-xs text-white/40" x-text="formatDisplayDate(dateDisplay)"></p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <input
                    type="date"
                    class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 shadow-sm hover:bg-white/10 sm:w-auto"
                    x-model="date"
                    x-on:change="window.location = `${window.location.pathname}?date=${encodeURIComponent(date)}`"
                />
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 shadow-sm hover:bg-white/10"
                    x-on:click="refreshSalesData()"
                >
                    Refresh Sales
                </button>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95 disabled:opacity-60"
                    x-on:click="save()"
                    x-bind:disabled="saving"
                >
                    <span x-show="!saving">Save</span>
                    <span x-show="saving" x-cloak>Saving...</span>
                </button>
            </div>
        </div>

        <!-- Toast & Error Messages -->
        <template x-if="toastOpen">
            <div class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200" x-transition.opacity>
                <span x-text="toastMessage"></span>
            </div>
        </template>

        <template x-if="errorMessage">
            <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200" x-transition.opacity>
                <span x-text="errorMessage"></span>
            </div>
        </template>

        <!-- Summary Cards Row -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Card 1: Total Sales -->
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-500/20 text-purple-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-white/60">Total Sales</div>
                        <div class="mt-1 text-2xl font-bold text-white" x-text="formatCurrency(todaysTotalSales)"></div>
                    </div>
                </div>
                <div class="mt-3 text-xs text-white/40">System recorded paid orders</div>
            </div>

            <!-- Card 2: Cash Sales -->
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-white/60">Cash Sales</div>
                        <div class="mt-1 text-2xl font-bold text-white" x-text="formatCurrency(todaysCashSales)"></div>
                    </div>
                </div>
                <div class="mt-3 text-xs text-white/40">Expected cash on hand</div>
            </div>

            <!-- Card 3: GCash Sales -->
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-500/20 text-sky-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-white/60">GCash Sales</div>
                        <div class="mt-1 text-2xl font-bold text-white" x-text="formatCurrency(todaysGcashSales)"></div>
                    </div>
                </div>
                <div class="mt-3 text-xs text-white/40">Expected GCash payments</div>
            </div>

            <!-- Card 4: Balance Status -->
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/20 text-amber-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-white/60">Balance Status</div>
                        <div class="mt-1 text-2xl font-bold text-white" x-text="formatCurrency(calculateBalanceDifference())"></div>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                        x-bind:class="getBalanceStatusClass()"
                        x-text="getBalanceStatusText()">
                    </span>
                    <span class="text-xs text-white/40" x-text="getBalanceStatusMessage()"></span>
                </div>
            </div>
        </div>

        <!-- Main Payment Entry Section -->
        <div class="rounded-xl border border-white/10 bg-white/5 p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-lg font-bold">Payment Entry</div>
                    <div class="mt-1 text-sm text-white/50">Record received payments (Cash / GCash) using quick denomination taps.</div>
                </div>

                <div class="flex items-center gap-2 rounded-xl border border-white/10 bg-[#111]/40 p-1">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold"
                        x-on:click="setPaymentType('cash')"
                        x-bind:class="paymentType === 'cash' ? 'bg-emerald-500 text-white' : 'text-white/80 hover:bg-white/5'"
                    >
                        Cash
                    </button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold"
                        x-on:click="setPaymentType('gcash')"
                        x-bind:class="paymentType === 'gcash' ? 'bg-sky-500 text-white' : 'text-white/80 hover:bg-white/5'"
                    >
                        GCash
                    </button>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Left Column: Cash/GCash Tab Content -->
                <div>
                    <!-- Cash Tab -->
                    <template x-if="paymentType === 'cash'">
                        <div class="rounded-xl border border-white/10 bg-[#111]/40 p-6">
                            <template x-if="isCashVerified()">
                                <div class="flex flex-col items-center justify-center rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-6 py-8">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div class="mt-4 text-lg font-semibold text-emerald-200">All Cash payments verified</div>
                                    <div class="mt-2 text-3xl font-bold text-white" x-text="formatCurrency(paymentsTotalByTypeAfterCutoff('cash'))"></div>
                                </div>
                            </template>

                            <template x-if="!isCashVerified()">
                                <div>
                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                        <template x-for="d in paymentDenominations" :key="d">
                                            <div class="flex flex-col items-center rounded-xl border border-white/10 bg-[#111]/60 p-3">
                                                <div class="text-xs font-semibold text-white/60" x-text="formatDenomination(d)"></div>
                                                <div class="mt-2 flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/5 text-white/80 hover:bg-white/10"
                                                        x-on:click="removePaymentDenomination(d)"
                                                    >
                                                        -
                                                    </button>
                                                    <div class="w-12 text-center text-sm font-semibold" x-text="paymentQty(d)"></div>
                                                    <button
                                                        type="button"
                                                        class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/5 text-white/80 hover:bg-white/10"
                                                        x-on:click="addPaymentDenomination(d)"
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="mt-6 flex items-center justify-between rounded-xl border border-white/10 bg-[#111]/50 px-4 py-3">
                                        <div class="text-sm text-white/60">Total Received</div>
                                        <div class="text-2xl font-bold text-white" x-text="formatCurrency(paymentTotal())"></div>
                                    </div>

                                    <div class="mt-4">
                                        <button
                                            type="button"
                                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#efe9df] px-4 py-3 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95 disabled:opacity-60"
                                            x-on:click="savePaymentEntry()"
                                            x-bind:disabled="paymentSaving"
                                        >
                                            <span x-show="!paymentSaving">Save Cash Entry</span>
                                            <span x-show="paymentSaving" x-cloak>Saving...</span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- GCash Tab -->
                    <template x-if="paymentType === 'gcash'">
                        <div class="rounded-xl border border-white/10 bg-[#111]/40 p-6">
                            <!-- Success State -->
                            <div x-show="isGcashVerified()">
                                <div class="flex flex-col items-center justify-center rounded-xl border border-sky-500/30 bg-sky-500/10 px-6 py-8">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-sky-500/20 text-sky-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div class="mt-4 text-lg font-semibold text-sky-200">All GCash payments verified</div>
                                    <div class="mt-2 text-3xl font-bold text-white" x-text="formatCurrency(paymentsTotalByTypeAfterCutoff('gcash'))"></div>
                                </div>
                            </div>

                            <!-- Transaction List -->
                            <div x-show="!isGcashVerified()">
                                <div x-show="gcashOrders.length === 0">
                                    <div class="flex flex-col items-center justify-center rounded-xl border border-white/10 bg-[#111]/60 px-6 py-12">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/5 text-white/40">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="mt-4 text-sm font-semibold text-white/60">No GCash transactions found for this date.</div>
                                    </div>
                                </div>

                                <div x-show="gcashOrders.length > 0">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="text-sm text-white/60">
                                            <span x-text="gcashOrders.filter(o => verifiedGcashOrderIds.includes(o.id)).length"></span> of <span x-text="gcashOrders.length"></span> verified
                                        </div>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500 disabled:opacity-60"
                                            x-on:click="confirmAllGcashOrders()"
                                            x-bind:disabled="gcashOrders.every(o => verifiedGcashOrderIds.includes(o.id))"
                                        >
                                            Confirm All
                                        </button>
                                    </div>

                                    <div class="space-y-3 max-h-96 overflow-y-auto">
                                        <template x-for="order in gcashOrders" :key="order.id">
                                            <div class="rounded-xl border border-white/10 bg-[#111]/60 p-4">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-sm font-semibold text-white" x-text="order.order_number"></span>
                                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                                                                x-bind:class="verifiedGcashOrderIds.includes(order.id) ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300'"
                                                                x-text="verifiedGcashOrderIds.includes(order.id) ? 'Verified' : 'Pending Verification'">
                                                            </span>
                                                        </div>
                                                        <div class="mt-2 text-xs text-white/50" x-text="formatEntryTime(order.created_at)"></div>
                                                        <div class="mt-3 space-y-1">
                                                            <div class="text-xs text-white/60">
                                                                <span class="font-semibold">Sender:</span> <span x-text="order.gcash_sender_name || 'N/A'"></span>
                                                            </div>
                                                            <div class="text-xs text-white/60">
                                                                <span class="font-semibold">Ref No:</span> <span x-text="order.gcash_reference || 'N/A'"></span>
                                                            </div>
                                                            <div class="text-xs text-white/60">
                                                                <span class="font-semibold">Mobile:</span> <span x-text="order.gcash_sender_mobile || 'N/A'"></span>
                                                            </div>
                                                        </div>
                                                        <div class="mt-3 text-lg font-bold text-sky-400" x-text="formatCurrency(order.total_amount)"></div>
                                                    </div>
                                                    <div class="flex flex-col gap-2 sm:items-end">
                                                        <button
                                                            type="button"
                                                            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-xs font-semibold"
                                                            x-bind:class="verifiedGcashOrderIds.includes(order.id) ? 'bg-emerald-600 text-white cursor-default' : 'bg-sky-600 text-white hover:bg-sky-500'"
                                                            x-bind:disabled="verifiedGcashOrderIds.includes(order.id)"
                                                            x-on:click="verifyGcashOrder(order.id)"
                                                        >
                                                            <span x-text="verifiedGcashOrderIds.includes(order.id) ? 'Verified' : 'Confirm'"></span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="mt-6 flex items-center justify-between rounded-xl border border-white/10 bg-[#111]/50 px-4 py-3">
                                        <div class="text-sm text-white/60">Total Verified GCash</div>
                                        <div class="text-2xl font-bold text-sky-400" x-text="formatCurrency(verifiedGcashTotal())"></div>
                                    </div>

                                    <div class="mt-4">
                                        <button
                                            type="button"
                                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#efe9df] px-4 py-3 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95 disabled:opacity-60"
                                            x-on:click="saveGcashVerification()"
                                            x-bind:disabled="paymentSaving || verifiedGcashOrderIds.length === 0"
                                        >
                                            <span x-show="!paymentSaving">Save GCash Verification</span>
                                            <span x-show="paymentSaving" x-cloak>Saving...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Right Column: Reconciliation Summary -->
                <div class="rounded-xl border border-white/10 bg-[#111]/40 p-6">
                    <div class="text-lg font-bold">Reconciliation Summary</div>
                    <div class="mt-1 text-sm text-white/50">Summary of expected vs counted amounts.</div>

                    <div class="mt-6 space-y-4">
                        <!-- Cash Block -->
                        <div class="rounded-xl border border-white/10 bg-[#111]/60 p-4">
                            <div class="text-sm font-semibold text-emerald-400">Cash</div>
                            <div class="mt-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-white/60">Expected Cash</span>
                                    <span class="text-sm font-semibold text-white" x-text="formatCurrency(todaysCashSales)"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-white/60">Verified Cash</span>
                                    <span class="text-sm font-semibold text-emerald-300" x-text="formatCurrency(paymentsTotalByTypeAfterCutoff('cash'))"></span>
                                </div>
                                <div class="flex items-center justify-between border-t border-white/10 pt-2">
                                    <span class="text-xs font-semibold text-white/80">Difference</span>
                                    <span class="text-sm font-bold" x-bind:class="getDifferenceClass(todaysCashSales, paymentsTotalByTypeAfterCutoff('cash'))" x-text="formatCurrency(calculateDifference(todaysCashSales, paymentsTotalByTypeAfterCutoff('cash')))"></span>
                                </div>
                            </div>
                        </div>

                        <!-- GCash Block -->
                        <div class="rounded-xl border border-white/10 bg-[#111]/60 p-4">
                            <div class="text-sm font-semibold text-sky-400">GCash</div>
                            <div class="mt-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-white/60">Expected GCash</span>
                                    <span class="text-sm font-semibold text-white" x-text="formatCurrency(todaysGcashSales)"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-white/60">Verified GCash</span>
                                    <span class="text-sm font-semibold text-sky-300" x-text="formatCurrency(paymentsTotalByTypeAfterCutoff('gcash'))"></span>
                                </div>
                                <div class="flex items-center justify-between border-t border-white/10 pt-2">
                                    <span class="text-xs font-semibold text-white/80">Difference</span>
                                    <span class="text-sm font-bold" x-bind:class="getDifferenceClass(todaysGcashSales, paymentsTotalByTypeAfterCutoff('gcash'))" x-text="formatCurrency(calculateDifference(todaysGcashSales, paymentsTotalByTypeAfterCutoff('gcash')))"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Total Block -->
                        <div class="rounded-xl border border-white/10 bg-[#111]/60 p-4">
                            <div class="text-sm font-semibold text-amber-400">Total (Cash + GCash)</div>
                            <div class="mt-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-white/60">Total Expected</span>
                                    <span class="text-sm font-semibold text-white" x-text="formatCurrency(todaysTotalSales)"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-white/60">Total Verified</span>
                                    <span class="text-sm font-semibold text-amber-300" x-text="formatCurrency(paymentsTotalByTypeAfterCutoff('cash') + paymentsTotalByTypeAfterCutoff('gcash'))"></span>
                                </div>
                                <div class="flex items-center justify-between border-t border-white/10 pt-2">
                                    <span class="text-xs font-semibold text-white/80">Difference</span>
                                    <span class="text-sm font-bold" x-bind:class="getDifferenceClass(todaysTotalSales, paymentsTotalByTypeAfterCutoff('cash') + paymentsTotalByTypeAfterCutoff('gcash'))" x-text="formatCurrency(calculateDifference(todaysTotalSales, paymentsTotalByTypeAfterCutoff('cash') + paymentsTotalByTypeAfterCutoff('gcash')))"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Payment Entries Section -->
        <div class="rounded-xl border border-white/10 bg-white/5 p-6 shadow-sm">
            <div class="text-lg font-bold">Today's Payment Entries</div>
            <div class="mt-1 text-sm text-white/50">Temporary working entries for the current session.</div>

            <div class="mt-4 overflow-hidden rounded-xl border border-white/10 bg-[#111]/40">
                <template x-if="!Array.isArray(paymentEntries) || paymentEntries.length === 0">
                    <div class="px-5 py-8 text-center text-sm text-white/60">No active payment entries.</div>
                </template>

                <template x-if="Array.isArray(paymentEntries) && paymentEntries.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-white/10 bg-[#111]/30">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/60">Type</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/60">Time</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/60">Total Amount</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/60">Status</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/60">Details</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-white/60">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <!-- Payment Entry rows -->
                                <template x-for="entry in paymentEntries" :key="entry.id">
                                    <tr class="hover:bg-white/5">
                                        <td class="px-5 py-4">
                                            <template x-if="entry.payment_type === 'gcash' && entry.order_id">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-sky-500/20 text-sky-300">GCash Verification</span>
                                            </template>
                                            <template x-if="entry.payment_type === 'gcash' && !entry.order_id">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-sky-500/20 text-sky-300">GCash</span>
                                            </template>
                                            <template x-if="entry.payment_type === 'cash'">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-500/20 text-emerald-300">Cash</span>
                                            </template>
                                        </td>
                                        <td class="px-5 py-4 text-sm text-white/80" x-text="formatEntryTime(entry.created_at)"></td>
                                        <td class="px-5 py-4 text-sm font-semibold text-white" x-text="formatCurrency(entry.received_amount)"></td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-500/20 text-emerald-300">Verified</span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <template x-if="entry.payment_type === 'gcash' && entry.order_id">
                                                <div class="space-y-1">
                                                    <div class="flex flex-wrap gap-1">
                                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-sky-500/20 text-sky-300">Order Verified</span>
                                                    </div>
                                                    <div class="text-xs text-white/60" x-text="'₱' + formatCurrency(entry.received_amount)"></div>
                                                    <template x-if="entry.gcash_details">
                                                        <div class="text-xs text-white/50" x-text="entry.gcash_details.sender_name || ''"></div>
                                                        <div class="text-xs text-white/50" x-text="'Ref: ' + (entry.gcash_details.gcash_reference || '')"></div>
                                                        <template x-if="entry.gcash_details.items && entry.gcash_details.items.length > 0">
                                                            <div class="text-xs text-white/50">
                                                                Items: <span x-text="entry.gcash_details.items.map(i => i.name).join(', ')"></span>
                                                            </div>
                                                        </template>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="entry.payment_type === 'gcash' && !entry.order_id">
                                                <div class="flex flex-wrap gap-1">
                                                    <template x-for="it in (entry.items || []).slice(0, 3)" :key="entry.id + '-' + it.denomination">
                                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-white/10 text-white/70" x-text="formatDenomination(it.denomination) + ' x' + it.quantity"></span>
                                                    </template>
                                                    <template x-if="(entry.items || []).length > 3">
                                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-white/10 text-white/70" x-text="'+' + ((entry.items || []).length - 3) + ' more'"></span>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="entry.payment_type === 'cash'">
                                                <div class="flex flex-wrap gap-1">
                                                    <template x-for="it in (entry.items || []).slice(0, 3)" :key="entry.id + '-' + it.denomination">
                                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-white/10 text-white/70" x-text="formatDenomination(it.denomination) + ' x' + it.quantity"></span>
                                                    </template>
                                                    <template x-if="(entry.items || []).length > 3">
                                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-white/10 text-white/70" x-text="'+' + ((entry.items || []).length - 3) + ' more'"></span>
                                                    </template>
                                                </div>
                                            </template>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <template x-if="entry.payment_type === 'cash' && !isCashVerified()">
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-500"
                                                        x-on:click="openEditEntry(entry)"
                                                    >
                                                        Edit
                                                    </button>
                                                </template>
                                                <template x-if="entry.payment_type === 'gcash' && !entry.order_id">
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-500"
                                                        x-on:click="openEditEntry(entry)"
                                                    >
                                                        Edit
                                                    </button>
                                                </template>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500"
                                                    x-on:click="deleteEntry(entry)"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>

        <template x-if="editEntryOpen">
            <div class="fixed inset-0 z-50" x-on:keydown.escape.window="closeEditEntry()">
                <div class="absolute inset-0 bg-black/70" x-transition.opacity x-on:click="closeEditEntry()"></div>
                <div class="absolute inset-0 grid place-items-center px-4">
                    <div class="w-full max-w-md rounded-2xl border border-white/10 bg-[#111] p-6 shadow-2xl" x-transition x-on:click.stop>
                        <div class="text-lg font-semibold">Edit Payment Entry</div>
                        <div class="mt-1 text-sm text-white/60">Update the received amount.</div>

                        <div class="mt-5 rounded-xl border border-white/10 bg-[#111]/40 p-4">
                            <div class="text-xs text-white/50">Total</div>
                            <div class="mt-1 text-3xl font-semibold" x-text="formatCurrency(editPaymentTotal())"></div>
                        </div>

                        <div class="mt-4 space-y-2">
                            <div class="text-xs font-semibold text-white/60">Denominations</div>
                            <div class="space-y-2">
                                <template x-for="d in paymentDenominations" :key="'edit-denom-' + d">
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-white/10 bg-[#111]/40 px-3 py-2">
                                        <div class="text-sm font-semibold" x-text="formatDenomination(d)"></div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 hover:bg-white/10"
                                                x-on:click="editDecrement(d)"
                                            >
                                                -
                                            </button>
                                            <input
                                                type="number"
                                                min="0"
                                                step="1"
                                                inputmode="numeric"
                                                class="h-9 w-20 rounded-xl border border-white/10 bg-[#111]/60 px-3 text-center text-sm font-semibold text-white"
                                                x-bind:value="editQty(d)"
                                                x-on:input="setEditQty(d, $event.target.value)"
                                            />
                                            <button
                                                type="button"
                                                class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 hover:bg-white/10"
                                                x-on:click="editIncrement(d)"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 hover:bg-white/10"
                                x-on:click="closeEditEntry()"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500 disabled:opacity-60"
                                x-bind:disabled="editSaving"
                                x-on:click="saveEditEntry()"
                            >
                                <span x-show="!editSaving">Save</span>
                                <span x-show="editSaving" x-cloak>Saving...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

    </div>

    <script>
        function moneyInventory(payload) {
            return {
                date: payload?.date || '',
                dateDisplay: payload?.dateDisplay || payload?.date || '',
                denominations: Array.isArray(payload?.denominations) ? payload.denominations : [],
                quantities: payload?.quantities || {},
                initialQuantities: {},
                saveUrl: payload?.saveUrl || '',
                saving: false,
                toastOpen: false,
                toastMessage: '',
                errorMessage: '',

                clockedIn: Boolean(payload?.clockedIn ?? false),
                todaysTotalSales: Number(payload?.todaysTotalSales || 0),
                todaysCashSales: Number(payload?.todaysCashSales || 0),
                todaysGcashSales: Number(payload?.todaysGcashSales || 0),

                paymentDenominations: Array.isArray(payload?.paymentDenominations) ? payload.paymentDenominations : [],
                paymentSaveUrl: payload?.paymentSaveUrl || '',
                paymentUpdateUrlTemplate: payload?.paymentUpdateUrlTemplate || '',
                paymentDeleteUrlTemplate: payload?.paymentDeleteUrlTemplate || '',
                resetTodaysSalesUrl: payload?.resetTodaysSalesUrl || '',
                paymentType: 'cash',
                paymentBreakdown: {},
                initialPaymentBreakdown: {},
                paymentEntries: Array.isArray(payload?.paymentEntries) ? payload.paymentEntries : [],
                paymentSaving: false,
                lowerTodaysTotalSales: Number(payload?.lowerTodaysTotalSales || payload?.todaysTotalSales || 0), // Separate for lower ENTRIES display

                reconciling: false,
                reconciled: Boolean(payload?.reconciledToday ?? false),
                reconciledAt: payload?.reconciledAt || null,
                undoReconcileUrl: payload?.undoReconcileUrl || '',

                editEntryOpen: false,
                editEntry: null,
                editPaymentBreakdown: {},
                editSaving: false,

                gcashOrders: Array.isArray(payload?.gcashOrders) ? payload.gcashOrders : [],
                confirmedOrderIds: Array.isArray(payload?.confirmedOrderIds) ? payload.confirmedOrderIds : [],
                verifiedGcashOrderIds: [],

                init() {
                    console.log('MoneyInventory init payload:', payload);
                    console.log('todaysTotalSales from backend:', payload?.todaysTotalSales);
                    console.log('gcashOrders from backend:', payload?.gcashOrders);
                    console.log('confirmedOrderIds from backend:', payload?.confirmedOrderIds);
                    this.initialQuantities = JSON.parse(JSON.stringify(this.quantities || {}));
                    this.denominations = (this.denominations || []).map(d => Number(d)).filter(d => Number.isFinite(d));
                    this.denominations.sort((a, b) => b - a);

                    this.paymentDenominations = (this.paymentDenominations || []).map(d => Number(d)).filter(d => Number.isFinite(d));
                    this.paymentDenominations.sort((a, b) => b - a);
                    this.paymentBreakdown = this.paymentDenominations.reduce((acc, d) => {
                        acc[String(d)] = 0;
                        return acc;
                    }, {});
                    this.initialPaymentBreakdown = JSON.parse(JSON.stringify(this.paymentBreakdown || {}));

                    if (!this.clockedIn) {
                        this.showToast(`Total Sales (${this.dateDisplay}): ${this.formatCurrency(this.todaysTotalSales)}`);
                    }

                    this.scheduleMidnightRollover();
                    this.startSalesPolling();
                },

                startSalesPolling() {
                    if (!this.isViewingToday()) return;

                    window.clearInterval(this.__salesPoller);
                    this.__salesPoller = window.setInterval(async () => {
                        try {
                            const url = `${window.location.pathname}?date=${encodeURIComponent(this.date)}`;
                            const res = await fetch(url, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            const data = await res.json().catch(() => ({}));
                            if (!res.ok) return;

                            const nextTotal = Number(data?.summary?.total_sales || 0);
                            const nextCash = Number(data?.summary?.cash || 0);
                            const nextGcash = Number(data?.summary?.gcash || 0);
                            const nextLower = Number(data?.summary?.lower_total_sales || 0);
                            const nextReconciled = Boolean(data?.reconciled ?? false);
                            const nextReconciledAt = data?.reconciled_at ?? null;
                            const nextDateDisplay = String(data?.date_display || this.dateDisplay || '').trim();

                            const prevTotal = Number(this.todaysTotalSales || 0);
                            const prevReconciled = Boolean(this.reconciled);

                            this.dateDisplay = nextDateDisplay || this.dateDisplay;
                            this.lowerTodaysTotalSales = Number.isFinite(nextLower) ? nextLower : this.lowerTodaysTotalSales;
                            this.todaysTotalSales = Number.isFinite(nextTotal) ? nextTotal : this.todaysTotalSales;
                            this.todaysCashSales = Number.isFinite(nextCash) ? nextCash : this.todaysCashSales;
                            this.todaysGcashSales = Number.isFinite(nextGcash) ? nextGcash : this.todaysGcashSales;
                            this.reconciled = nextReconciled;
                            this.reconciledAt = nextReconciledAt || this.reconciledAt;

                            if (prevReconciled && !nextReconciled && nextTotal > 0) {
                                this.showToast(`New sales detected (${this.dateDisplay}). Please record the next transaction.`);
                            } else if (nextTotal > prevTotal && nextTotal > 0) {
                                this.showToast(`Sales updated (${this.dateDisplay}): ${this.formatCurrency(nextTotal)}`);
                            }
                        } catch (e) {
                            // ignore polling errors
                        }
                    }, 10000);
                },

                async refreshSalesData() {
                    try {
                        const url = `${window.location.pathname}?date=${encodeURIComponent(this.date)}`;
                        const res = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            this.errorMessage = 'Failed to refresh sales data.';
                            return;
                        }

                        // Update sales summary
                        this.todaysTotalSales = Number(data?.summary?.total_sales || 0);
                        this.todaysCashSales = Number(data?.summary?.cash || 0);
                        this.todaysGcashSales = Number(data?.summary?.gcash || 0);
                        this.lowerTodaysTotalSales = Number(data?.summary?.lower_total_sales || 0);
                        this.dateDisplay = String(data?.date_display || this.dateDisplay || '').trim();

                        // Reload GCash orders
                        if (data?.gcashOrders) {
                            this.gcashOrders = data.gcashOrders;
                            this.confirmedOrderIds = data.confirmedOrderIds || [];
                        }

                        // Reload payment entries
                        if (data?.paymentEntries) {
                            this.paymentEntries = data.paymentEntries;
                        }

                        // Reset active inputs
                        this.resetActiveMoneyInventorySession();

                        this.showToast('Sales data refreshed successfully.');
                    } catch (e) {
                        this.errorMessage = 'Failed to refresh sales data.';
                    }
                },

                resetActiveMoneyInventorySession() {
                    // Reset cash denomination quantities to 0
                    Object.keys(this.quantities).forEach(key => {
                        this.quantities[key] = 0;
                    });
                    this.initialQuantities = JSON.parse(JSON.stringify(this.quantities));

                    // Reset payment breakdown to empty
                    this.paymentBreakdown = this.paymentDenominations.reduce((acc, d) => {
                        acc[String(d)] = 0;
                        return acc;
                    }, {});
                    this.initialPaymentBreakdown = JSON.parse(JSON.stringify(this.paymentBreakdown || {}));

                    // Reset GCash verification state
                    this.verifiedGcashOrderIds = [];
                },

                scheduleMidnightRollover() {
                    if (!this.isViewingToday()) return;

                    const now = new Date();
                    const next = new Date(now);
                    next.setHours(24, 0, 0, 0);
                    const ms = next.getTime() - now.getTime();
                    if (!Number.isFinite(ms) || ms <= 0) return;

                    window.clearTimeout(this.__midnightTimer);
                    this.__midnightTimer = window.setTimeout(() => {
                        const d = new Date();
                        const y = d.getFullYear();
                        const m = String(d.getMonth() + 1).padStart(2, '0');
                        const day = String(d.getDate()).padStart(2, '0');
                        const iso = `${y}-${m}-${day}`;
                        window.location = `${window.location.pathname}?date=${encodeURIComponent(iso)}`;
                    }, ms + 1000);
                },

                quantity(d) {
                    const key = String(d);
                    const v = this.quantities?.[key] ?? this.quantities?.[d] ?? 0;
                    const n = Number(v);
                    return Number.isFinite(n) && n > 0 ? Math.floor(n) : 0;
                },

                setQuantity(d, value) {
                    const key = String(d);
                    const n = Number(value);
                    const qty = Number.isFinite(n) && n > 0 ? Math.floor(n) : 0;
                    this.quantities = { ...(this.quantities || {}), [key]: qty };
                },

                increment(d) {
                    const q = this.quantity(d);
                    this.setQuantity(d, q + 1);
                },

                decrement(d) {
                    const q = this.quantity(d);
                    this.setQuantity(d, q - 1);
                },

                subtotal(d) {
                    const denom = Number(d);
                    return denom * this.quantity(d);
                },

                subtotalLabel(d) {
                    return `Subtotal: ${this.formatCurrency(this.subtotal(d))}`;
                },

                total() {
                    return (this.denominations || []).reduce((sum, d) => sum + this.subtotal(d), 0);
                },

                paymentsTotalByType(type) {
                    const t = String(type || '').toLowerCase();
                    return (this.paymentEntries || [])
                        .filter(e => String(e?.payment_type || '').toLowerCase() === t)
                        .reduce((sum, e) => sum + (Number(e?.received_amount || 0) || 0), 0);
                },

                paymentEntriesAfterCutoff() {
                    const cutoffRaw = this.reconciledAt;
                    if (!cutoffRaw) return (this.paymentEntries || []);

                    const cutoff = Date.parse(String(cutoffRaw));
                    if (!Number.isFinite(cutoff)) return (this.paymentEntries || []);

                    return (this.paymentEntries || []).filter(e => {
                        const ts = Date.parse(String(e?.created_at || ''));
                        if (!Number.isFinite(ts)) return false;
                        return ts > cutoff;
                    });
                },

                paymentsTotalByTypeAfterCutoff(type) {
                    const t = String(type || '').toLowerCase();
                    return this.paymentEntriesAfterCutoff()
                        .filter(e => String(e?.payment_type || '').toLowerCase() === t)
                        .reduce((sum, e) => sum + (Number(e?.received_amount || 0) || 0), 0);
                },

                isViewingToday() {
                    const now = new Date();
                    const y = now.getFullYear();
                    const m = String(now.getMonth() + 1).padStart(2, '0');
                    const d = String(now.getDate()).padStart(2, '0');
                    const today = `${y}-${m}-${d}`;
                    return String(this.date || '') === today;
                },

                async resetTodaysSales() {
                    if (!this.resetTodaysSalesUrl) return;

                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
                    if (!token) return;

                    try {
                        const res = await fetch(this.resetTodaysSalesUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({
                                payment_type: this.paymentType, // 'cash' or 'gcash'
                            }),
                        });

                        if (res.ok) {
                            // Reload sales data to reflect the reset
                            await this.refreshSalesData();
                        }
                    } catch (e) {
                        console.error('Failed to reset today\'s sales:', e);
                    }
                },

                async maybeReconcileDay() {
                    if (!this.isViewingToday()) return;
                    if (this.reconciling || this.reconciled) return;
                    if (!this.resetTodaysSalesUrl) return;

                    const expectedCash = Math.floor(this.todaysCashSales || 0);
                    const expectedGcash = Math.floor(this.todaysGcashSales || 0);
                    const expectedAny = (expectedCash + expectedGcash) > 0;
                    // Only reconcile if there are actual sales totals; do not reconcile when totals are 0
                    if (!expectedAny) return;

                    const actualCash = Math.floor(this.paymentsTotalByTypeAfterCutoff('cash') || 0);
                    const actualGcash = Math.floor(this.paymentsTotalByTypeAfterCutoff('gcash') || 0);

                    const cashOk = expectedCash === 0 ? true : actualCash === expectedCash;
                    const gcashOk = expectedGcash === 0 ? true : actualGcash === expectedGcash;

                    if (!cashOk || !gcashOk) return;

                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
                    if (!token) return;

                    this.reconciling = true;
                    try {
                        const res = await fetch(this.resetTodaysSalesUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({}),
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            return;
                        }

                        this.reconciled = true;
                        this.reconciledAt = data?.reconciled_at || new Date().toISOString();

                        // Allow reset in top card totals, but preserve lower ENTRIES section Today's Total Sales
                        // We'll use a separate variable for the lower section display
                        this.lowerTodaysTotalSales = this.todaysTotalSales; // Preserve for lower display
                        this.todaysTotalSales = 0;
                        this.todaysCashSales = 0;
                        this.todaysGcashSales = 0;
                        this.resetPayment();
                        this.showToast(data?.message || "Today's sales reconciled.");
                    } finally {
                        this.reconciling = false;
                    }
                },

                async maybeUndoReconcileIfNeeded() {
                    if (!this.isViewingToday()) return;
                    if (!this.reconciled) return;
                    if (!this.undoReconcileUrl) return;
                    const hasEntries = Array.isArray(this.paymentEntriesAfterCutoff()) && this.paymentEntriesAfterCutoff().length > 0;
                    if (hasEntries) return;

                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
                    if (!token) return;

                    try {
                        const res = await fetch(this.undoReconcileUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({}),
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            return;
                        }

                        const totals = data?.totals || {};
                        this.todaysTotalSales = Number(totals?.overall || 0);
                        this.todaysCashSales = Number(totals?.cash || 0);
                        this.todaysGcashSales = Number(totals?.gcash || 0);
                        this.reconciled = false;
                        this.reconciledAt = null;
                        this.showToast(data?.message || "Today's reconciliation undone.");
                    } catch (e) {
                    }
                },

                formatDenomination(d) {
                    const denom = Number(d);
                    return `₱${denom.toLocaleString()}`;
                },

                formatCurrency(value) {
                    const n = Number(value || 0);
                    return `₱${n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
                },

                formatEntryTime(iso) {
                    const raw = String(iso || '').trim();
                    if (!raw) return '';
                    const d = new Date(raw);
                    if (Number.isNaN(d.getTime())) return '';
                    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                },

                formatDisplayDate(dateStr) {
                    const raw = String(dateStr || '').trim();
                    if (!raw) return '';
                    const d = new Date(raw);
                    if (Number.isNaN(d.getTime())) return raw;
                    return d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                },

                calculateBalanceDifference() {
                    const expected = this.todaysTotalSales || 0;
                    const verified = (this.paymentsTotalByTypeAfterCutoff('cash') || 0) + (this.paymentsTotalByTypeAfterCutoff('gcash') || 0);
                    return expected - verified;
                },

                getBalanceStatusClass() {
                    const diff = this.calculateBalanceDifference();
                    if (diff === 0) return 'bg-emerald-500/20 text-emerald-300';
                    if (diff > 0) return 'bg-amber-500/20 text-amber-300';
                    return 'bg-rose-500/20 text-rose-300';
                },

                getBalanceStatusText() {
                    const diff = this.calculateBalanceDifference();
                    if (diff === 0) return 'Balanced';
                    if (diff > 0) return 'Short';
                    return 'Over';
                },

                getBalanceStatusMessage() {
                    const diff = this.calculateBalanceDifference();
                    if (diff === 0) return 'Cash/GCash is balanced';
                    if (diff > 0) return 'Cash/GCash is short';
                    return 'Cash/GCash is over';
                },

                isCashVerified() {
                    const expected = this.todaysCashSales || 0;
                    const verified = this.paymentsTotalByTypeAfterCutoff('cash') || 0;
                    return expected > 0 && verified >= expected;
                },

                isGcashVerified() {
                    const expected = this.todaysGcashSales || 0;
                    const verified = this.paymentsTotalByTypeAfterCutoff('gcash') || 0;
                    const hasUnverified = this.gcashOrders.some(o => !this.verifiedGcashOrderIds.includes(o.id));
                    return expected > 0 && verified >= expected && !hasUnverified;
                },

                calculateDifference(expected, actual) {
                    return (Number(expected) || 0) - (Number(actual) || 0);
                },

                getDifferenceClass(expected, actual) {
                    const diff = this.calculateDifference(expected, actual);
                    if (diff === 0) return 'text-emerald-400';
                    if (diff > 0) return 'text-amber-400';
                    return 'text-rose-400';
                },

                verifyGcashOrder(orderId) {
                    const id = Number(orderId);
                    if (this.verifiedGcashOrderIds.includes(id)) {
                        this.verifiedGcashOrderIds = this.verifiedGcashOrderIds.filter(oid => oid !== id);
                    } else {
                        this.verifiedGcashOrderIds.push(id);
                    }
                },

                confirmAllGcashOrders() {
                    this.verifiedGcashOrderIds = this.gcashOrders.map(o => o.id);
                },

                verifiedGcashTotal() {
                    return this.gcashOrders
                        .filter(o => this.verifiedGcashOrderIds.includes(o.id))
                        .reduce((sum, o) => sum + (Number(o.total_amount) || 0), 0);
                },

                async saveGcashVerification() {
                    if (this.verifiedGcashOrderIds.length === 0) {
                        this.errorMessage = 'Please verify at least one GCash transaction.';
                        return;
                    }

                    this.paymentSaving = true;
                    try {
                        const verifiedOrders = this.gcashOrders.filter(o => this.verifiedGcashOrderIds.includes(o.id));

                        // Create a payment entry for each verified GCash order
                        for (const order of verifiedOrders) {
                            const res = await fetch(this.paymentSaveUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                },
                                body: JSON.stringify({
                                    date: this.date,
                                    payment_type: 'gcash',
                                    received_amount: order.total_amount,
                                    order_id: order.id,
                                    breakdown: {},
                                }),
                            });

                            if (!res.ok) {
                                const data = await res.json().catch(() => ({}));
                                this.errorMessage = data?.message || 'Failed to save GCash verification.';
                                this.paymentSaving = false;
                                return;
                            }

                            const data = await res.json();
                            const savedEntry = data?.payment_entry;

                            if (savedEntry) {
                                // Add the saved entry to paymentEntries at the beginning with GCash details
                                const newEntry = {
                                    id: savedEntry.id,
                                    payment_type: 'gcash',
                                    received_amount: savedEntry.received_amount,
                                    created_at: savedEntry.created_at,
                                    order_id: savedEntry.order_id,
                                    items: savedEntry.items || [],
                                    gcash_details: {
                                        sender_name: order.gcash_sender_name || '',
                                        gcash_reference: order.gcash_reference || '',
                                        gcash_sender_mobile: order.gcash_sender_mobile || '',
                                        order_number: order.order_number || '',
                                        items: order.items || [],
                                    },
                                };
                                this.paymentEntries = [newEntry, ...this.paymentEntries];
                            }
                        }

                        // Remove verified orders from gcashOrders
                        this.gcashOrders = this.gcashOrders.filter(o => !this.verifiedGcashOrderIds.includes(o.id));
                        this.verifiedGcashOrderIds = [];

                        // Check if all GCash payments are now verified
                        const totalVerifiedGcash = this.paymentsTotalByTypeAfterCutoff('gcash');
                        const expectedGcash = this.todaysGcashSales || 0;

                        if (this.gcashOrders.length === 0 && totalVerifiedGcash >= expectedGcash) {
                            this.showToast('All GCash payments verified');
                        } else {
                            this.showToast(`Successfully verified ${verifiedOrders.length} GCash transaction(s).`);
                        }
                    } catch (e) {
                        this.errorMessage = 'An error occurred while saving GCash verification.';
                    } finally {
                        this.paymentSaving = false;
                    }
                },

                buildEntryUrl(template, entryId) {
                    if (!template) return '';
                    return String(template).replace('__ENTRY__', encodeURIComponent(String(entryId)));
                },

                openEditEntry(entry) {
                    if (!entry || !entry.items) return;
                    const base = {};
                    const items = Array.isArray(entry.items) ? entry.items : [];
                    items.forEach(it => {
                        const denom = Number(it?.denomination);
                        const qty = Number(it?.quantity);
                        if (!Number.isFinite(denom) || !Number.isFinite(qty)) return;
                        base[String(denom)] = qty > 0 ? Math.floor(qty) : 0;
                    });

                    this.editPaymentBreakdown = base;
                    this.editEntryOpen = true;
                    this.showToast(`Today's Total Sales: ${this.formatCurrency(this.todaysTotalSales)}`);
                },

                closeEditEntry() {
                    this.editEntryOpen = false;
                    this.editEntry = null;
                    this.editPaymentBreakdown = {};
                    this.editSaving = false;
                },

                editQty(d) {
                    const key = String(d);
                    const v = this.editPaymentBreakdown?.[key] ?? 0;
                    const n = Number(v);
                    return Number.isFinite(n) && n > 0 ? Math.floor(n) : 0;
                },

                setEditQty(d, value) {
                    const key = String(d);
                    const n = Number(value);
                    const qty = Number.isFinite(n) && n > 0 ? Math.floor(n) : 0;
                    this.editPaymentBreakdown = { ...(this.editPaymentBreakdown || {}), [key]: qty };
                },

                editIncrement(d) {
                    const q = this.editQty(d);
                    this.setEditQty(d, q + 1);
                },

                editDecrement(d) {
                    const q = this.editQty(d);
                    this.setEditQty(d, q - 1);
                },

                editPaymentTotal() {
                    return (this.paymentDenominations || []).reduce((sum, d) => sum + (Number(d) * this.editQty(d)), 0);
                },

                async saveEditEntry() {
                    this.errorMessage = '';
                    if (!this.editEntry || !this.paymentUpdateUrlTemplate) {
                        this.errorMessage = 'Edit endpoint not configured.';
                        return;
                    }
                    if (this.editSaving) return;

                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
                    if (!token) {
                        this.errorMessage = 'Security token not found. Please refresh the page and try again.';
                        return;
                    }

                    const breakdownTotal = this.editPaymentTotal();
                    if (!Number.isFinite(breakdownTotal) || breakdownTotal <= 0) {
                        this.errorMessage = 'Please enter or build a received amount.';
                        return;
                    }

                    this.editSaving = true;
                    try {
                        const url = this.buildEntryUrl(this.paymentUpdateUrlTemplate, this.editEntry.id);
                        const res = await fetch(url, {
                            method: 'PUT',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({
                                breakdown: this.editPaymentBreakdown || {},
                            }),
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                            this.errorMessage = firstError || data?.message || 'Failed to update payment entry.';
                            return;
                        }

                        const updated = data?.entry || null;
                        if (updated) {
                            this.paymentEntries = (Array.isArray(this.paymentEntries) ? this.paymentEntries : []).map(e =>
                                (String(e?.id) === String(updated.id)) ? updated : e
                            );
                        }

                        // Auto-reset if payment total now matches today's total sales
                        const total = Math.floor(this.paymentTotal());
                        const sales = Math.floor(this.lowerTodaysTotalSales || 0);
                        console.log('Auto-reset check after Edit:', { total, sales, match: total === sales });
                        // Only auto-reset if there are actual sales (sales > 0); do not reset when sales are 0
                        if (total === sales && sales > 0) {
                            console.log('Triggering auto-reset...');
                            this.paymentEntries = [];
                            this.paymentBreakdown = this.paymentDenominations.reduce((acc, d) => {
                                acc[String(d)] = 0;
                                return acc;
                            }, {});
                            this.gcashAmount = '';
                            // Reset todaysTotalSales to 0 so UI reflects cleared day, but only if there were no actual sales
                            // Since we only enter this block when sales > 0, we should NOT reset todaysTotalSales here
                            // this.todaysTotalSales = 0; // REMOVED: preserve sales value
                            // Call backend to mark today's sales as reconciled
                            if (this.resetTodaysSalesUrl) {
                                fetch(this.resetTodaysSalesUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    },
                                }).catch(() => {});
                            }
                            console.log('Auto-reset done.');
                        }

                        this.closeEditEntry();
                        this.showToast(data?.message || 'Payment entry updated.');
                    } catch (e) {
                        this.errorMessage = 'Failed to update payment entry.';
                    } finally {
                        this.editSaving = false;
                    }
                },

                async deleteEntry(entry) {
                    this.errorMessage = '';
                    if (!entry || !this.paymentDeleteUrlTemplate) {
                        this.errorMessage = 'Delete endpoint not configured.';
                        return;
                    }

                    if (!confirm('Delete this payment entry?')) return;

                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
                    if (!token) {
                        this.errorMessage = 'Security token not found. Please refresh the page and try again.';
                        return;
                    }

                    try {
                        const url = this.buildEntryUrl(this.paymentDeleteUrlTemplate, entry.id);
                        const res = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                            },
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            this.errorMessage = data?.message || 'Failed to delete payment entry.';
                            return;
                        }

                        this.paymentEntries = (Array.isArray(this.paymentEntries) ? this.paymentEntries : []).filter(e =>
                            String(e?.id) !== String(entry.id)
                        );
                        await this.maybeUndoReconcileIfNeeded();
                        this.showToast(data?.message || 'Payment entry deleted.');
                    } catch (e) {
                        this.errorMessage = 'Failed to delete payment entry.';
                    }
                },

                setPaymentType(type) {
                    const t = String(type || '').toLowerCase();
                    if (t !== 'cash' && t !== 'gcash') return;
                    this.paymentType = t;
                    this.resetPayment();
                },

                paymentTypeLabel() {
                    return this.paymentType === 'gcash' ? 'GCash' : 'Cash';
                },

                paymentQty(d) {
                    const key = String(d);
                    const v = this.paymentBreakdown?.[key] ?? 0;
                    const n = Number(v);
                    return Number.isFinite(n) && n > 0 ? Math.floor(n) : 0;
                },

                setPaymentQty(d, value) {
                    const key = String(d);
                    const n = Number(value);
                    const qty = Number.isFinite(n) && n > 0 ? Math.floor(n) : 0;
                    this.paymentBreakdown = { ...(this.paymentBreakdown || {}), [key]: qty };
                },

                addPaymentDenomination(d) {
                    const q = this.paymentQty(d);
                    this.setPaymentQty(d, q + 1);
                },

                removePaymentDenomination(d) {
                    const q = this.paymentQty(d);
                    this.setPaymentQty(d, q - 1);
                },

                paymentTotal() {
                    return (this.paymentDenominations || []).reduce((sum, d) => sum + (Number(d) * this.paymentQty(d)), 0);
                },

                resetPayment() {
                    this.errorMessage = '';
                    this.paymentBreakdown = JSON.parse(JSON.stringify(this.initialPaymentBreakdown || {}));
                    this.gcashAmount = '';
                },

                async saveMain() {
                    this.errorMessage = '';
                    if (this.paymentSaving) return;

                    // Check if payment type is verified before allowing save
                    if (this.paymentType === 'cash' && !this.isCashVerified()) {
                        this.errorMessage = 'Please verify all Cash payments before saving.';
                        return;
                    }
                    if (this.paymentType === 'gcash' && !this.isGcashVerified()) {
                        this.errorMessage = 'Please verify all GCash payments before saving.';
                        return;
                    }

                    // If verified, build the received amount from expected totals
                    const token = document.querySelector('meta[name="csrf-token']')?.getAttribute('content') || null;
                    if (!token) {
                        this.errorMessage = 'Security token not found. Please refresh the page and try again.';
                        return;
                    }

                    let receivedAmount = 0;
                    let breakdown = {};

                    if (this.paymentType === 'cash') {
                        // Use expected cash sales as received amount
                        receivedAmount = this.todaysCashSales || 0;
                        // Build breakdown from expected cash
                        breakdown = this.buildCashBreakdownFromExpected(receivedAmount);
                    } else if (this.paymentType === 'gcash') {
                        // Use expected gcash sales as received amount
                        receivedAmount = this.todaysGcashSales || 0;
                    }

                    if (!Number.isFinite(receivedAmount) || receivedAmount <= 0) {
                        this.errorMessage = 'No sales to save for this payment type.';
                        return;
                    }

                    this.paymentSaving = true;
                    try {
                        const res = await fetch(this.paymentSaveUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({
                                date: this.date,
                                payment_type: this.paymentType,
                                received_amount: receivedAmount,
                                breakdown,
                            }),
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                            this.errorMessage = firstError || data?.message || 'Failed to save payment entry.';
                            return;
                        }

                        const entry = data?.payment_entry || data?.entry || null;
                        if (entry) {
                            this.paymentEntries = [entry, ...(Array.isArray(this.paymentEntries) ? this.paymentEntries : [])];
                        }

                        // Reset today's sales after saving payment entry
                        await this.resetTodaysSales();

                        this.resetPayment();
                        this.showToast(data?.message || 'Payment entry saved.');
                    } catch (e) {
                        this.errorMessage = 'Failed to save payment entry. Please check your connection and try again.';
                    } finally {
                        this.paymentSaving = false;
                    }
                },

                buildCashBreakdownFromExpected(amount) {
                    // Simple breakdown: use largest denominations first
                    const denominations = [1000, 500, 200, 100, 50, 20, 10, 5, 1];
                    const breakdown = {};
                    let remaining = amount;

                    for (const denom of denominations) {
                        if (remaining >= denom) {
                            const count = Math.floor(remaining / denom);
                            breakdown[String(denom)] = count;
                            remaining -= count * denom;
                        } else {
                            breakdown[String(denom)] = 0;
                        }
                    }

                    return breakdown;
                },

                async savePaymentEntry() {
                    this.errorMessage = '';
                    if (!this.paymentSaveUrl) {
                        this.errorMessage = 'Payment save endpoint not configured.';
                        return;
                    }
                    if (this.paymentSaving) return;

                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
                    if (!token) {
                        this.errorMessage = 'Security token not found. Please refresh the page and try again.';
                        return;
                    }

                    const breakdown = this.paymentBreakdown || {};
                    const breakdownTotal = this.paymentTotal();
                    const gcashInput = String(this.gcashAmount || '').trim();

                    let receivedAmount = null;
                    if (this.paymentType === 'gcash' && gcashInput !== '') {
                        const n = Number(gcashInput);
                        if (Number.isFinite(n) && n >= 0) {
                            receivedAmount = Math.floor(n);
                        }
                    }

                    if (receivedAmount === null) {
                        receivedAmount = breakdownTotal;
                    }

                    if (!Number.isFinite(receivedAmount) || receivedAmount <= 0) {
                        this.errorMessage = 'Please enter or build a received amount.';
                        return;
                    }

                    // Remove strict validation - allow saving even if amount doesn't match expected total
                    // This allows staff to save working entries during reconciliation process

                    this.paymentSaving = true;
                    try {
                        const res = await fetch(this.paymentSaveUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({
                                date: this.date,
                                payment_type: this.paymentType,
                                received_amount: receivedAmount,
                                breakdown,
                            }),
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                            this.errorMessage = firstError || data?.message || 'Failed to save payment entry.';
                            return;
                        }

                        const entry = data?.payment_entry || data?.entry || null;
                        if (entry) {
                            this.paymentEntries = [entry, ...(Array.isArray(this.paymentEntries) ? this.paymentEntries : [])];
                        }

                        // Reset today's sales after saving payment entry
                        await this.resetTodaysSales();

                        this.resetPayment();
                        this.showToast(data?.message || 'Payment entry saved.');
                    } catch (e) {
                        this.errorMessage = 'Failed to save payment entry. Please check your connection and try again.';
                    } finally {
                        this.paymentSaving = false;
                    }
                },

                reset() {
                    this.errorMessage = '';
                    // Only allow reset if there are no sales today; otherwise preserve current quantities
                    if (this.todaysTotalSales > 0) {
                        this.showToast('Cannot reset while today\'s sales are recorded.');
                        return;
                    }
                    this.quantities = JSON.parse(JSON.stringify(this.initialQuantities || {}));
                },

                showToast(message) {
                    this.toastMessage = String(message || '').trim();
                    this.toastOpen = true;
                    window.clearTimeout(this.__toastTimer);
                    this.__toastTimer = window.setTimeout(() => {
                        this.toastOpen = false;
                    }, 2000);
                },

                async save() {
                    this.errorMessage = '';
                    if (!this.saveUrl) {
                        this.errorMessage = 'Save endpoint not configured.';
                        return;
                    }
                    if (this.saving) return;

                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
                    if (!token) {
                        this.errorMessage = 'Security token not found. Please refresh the page and try again.';
                        return;
                    }

                    this.saving = true;
                    try {
                        const res = await fetch(this.saveUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({
                                date: this.date,
                                quantities: this.quantities || {},
                            }),
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                            this.errorMessage = firstError || data?.message || 'Failed to save money inventory.';
                            return;
                        }

                        // Only update initialQuantities if there are no sales; otherwise preserve current quantities
                        if (this.todaysTotalSales <= 0) {
                            this.initialQuantities = JSON.parse(JSON.stringify(this.quantities || {}));
                        }
                        this.showToast(data?.message || 'Money inventory saved.');
                    } catch (e) {
                        this.errorMessage = 'Failed to save money inventory. Please check your connection and try again.';
                    } finally {
                        this.saving = false;
                    }
                },
            };
        }
    </script>
@endsection
