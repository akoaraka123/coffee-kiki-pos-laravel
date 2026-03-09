@extends('layouts.dashboard')

@section('title', 'Money Inventory')

@section('content')
    <div
        class="space-y-6"
        x-data="moneyInventory({
            date: @js($date),
            denominations: @js($denominations),
            quantities: @js($quantities),
            saveUrl: @js(route('staff.money-inventory.save')),
            clockedIn: @js((bool) ($clockedIn ?? false)),
            todaysTotalSales: @js((float) ($todaysTotalSales ?? 0)),
            paymentDenominations: @js($paymentDenominations ?? []),
            paymentEntries: @js(($paymentEntries ?? collect())->map(fn ($e) => [
                'id' => (int) $e->id,
                'payment_type' => (string) $e->payment_type,
                'received_amount' => (int) $e->received_amount,
                'created_at' => $e->created_at?->toIso8601String(),
                'items' => ($e->items ?? collect())->map(fn ($i) => [
                    'denomination' => (int) $i->denomination,
                    'quantity' => (int) $i->quantity,
                ])->values()->all(),
            ])->values()->all()),
            paymentSaveUrl: @js(route('staff.money-inventory.payment-entries.store')),
        })"
        x-init="init()"
    >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Today</h2>
                <p class="mt-1 text-sm text-white/50">Record your physical cash denominations for {{ $date }}.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95 disabled:opacity-60"
                    x-on:click="save()"
                    x-bind:disabled="saving"
                >
                    <span x-show="!saving">Save</span>
                    <span x-show="saving" x-cloak>Saving...</span>
                </button>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 shadow-sm hover:bg-white/10"
                    x-on:click="reset()"
                >
                    Reset
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-sm font-semibold">Total Cash</div>
                <div class="mt-2 text-3xl font-semibold" x-text="formatCurrency(total())"></div>
                <div class="mt-1 text-xs text-white/50">Automatically calculated from denominations × quantity.</div>
            </div>

            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-sm font-semibold">Notes</div>
                <div class="mt-1 text-xs text-white/50">This inventory is saved per day and per staff account.</div>
                <div class="mt-4 rounded-xl border border-white/10 bg-[#111]/40 p-4 text-sm text-white/70">
                    Date: <span class="font-semibold text-white" x-text="date"></span>
                </div>
            </div>
        </div>

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

        <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-sm font-semibold">Payment Entry</div>
                    <div class="mt-1 text-xs text-white/50">Record received payments (Cash / GCash) using quick denomination taps.</div>
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

            <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-white/10 bg-[#111]/40 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm font-semibold">Received Amount</div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10"
                            x-on:click="resetPayment()"
                        >
                            Clear
                        </button>
                    </div>

                    <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-5">
                        <template x-for="d in paymentDenominations" :key="d">
                            <button
                                type="button"
                                class="inline-flex h-14 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-base font-semibold text-white/90 hover:bg-white/10 active:scale-[0.98]"
                                x-on:click="addPaymentDenomination(d)"
                            >
                                <span x-text="formatDenomination(d)"></span>
                            </button>
                        </template>
                    </div>

                    <div class="mt-4 rounded-xl border border-white/10 bg-[#111]/50 p-4">
                        <div class="text-xs text-white/50">Total Received</div>
                        <div class="mt-1 text-3xl font-semibold" x-text="formatCurrency(paymentTotal())"></div>
                        <template x-if="paymentType === 'gcash'">
                            <div class="mt-4">
                                <label class="text-xs text-white/50">Optional: direct amount input (GCash)</label>
                                <input
                                    type="number"
                                    inputmode="numeric"
                                    min="0"
                                    step="1"
                                    class="mt-1 w-full rounded-xl border border-white/10 bg-[#111]/40 px-3 py-3 text-base text-white"
                                    placeholder="Enter exact received amount"
                                    x-model="gcashAmount"
                                />
                                <div class="mt-1 text-xs text-white/50">If entered, this will be saved as the received amount (denomination taps still saved as breakdown).</div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#efe9df] px-4 py-3 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95 disabled:opacity-60"
                            x-on:click="savePaymentEntry()"
                            x-bind:disabled="paymentSaving"
                        >
                            <span x-show="!paymentSaving">Save Payment Entry</span>
                            <span x-show="paymentSaving" x-cloak>Saving...</span>
                        </button>
                        <div class="w-full rounded-xl border border-white/10 bg-[#111]/40 px-4 py-3 text-sm text-white/70">
                            Type: <span class="font-semibold text-white" x-text="paymentTypeLabel()"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <div class="text-sm font-semibold">Today’s Payment Entries</div>
                <div class="mt-1 text-xs text-white/50">Saved entries for {{ $date }} (this user).</div>

                <div class="mt-4 grid grid-cols-1 gap-3">
                    <template x-if="!Array.isArray(paymentEntries) || paymentEntries.length === 0">
                        <div class="rounded-xl border border-white/10 bg-[#111]/40 px-4 py-3 text-sm text-white/60">No entries yet.</div>
                    </template>

                    <template x-for="entry in paymentEntries" :key="entry.id">
                        <div class="rounded-xl border border-white/10 bg-[#111]/40 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-semibold" x-text="entry.payment_type === 'gcash' ? 'GCash' : 'Cash'"></div>
                                <div class="text-lg font-semibold" x-text="formatCurrency(entry.received_amount)"></div>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <template x-for="it in (entry.items || [])" :key="entry.id + '-' + it.denomination">
                                    <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs text-white/80">
                                        <div class="font-semibold" x-text="formatDenomination(it.denomination)"></div>
                                        <div class="text-white/60" x-text="'Qty: ' + it.quantity"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <script>
        function moneyInventory(payload) {
            return {
                date: payload?.date || '',
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

                paymentDenominations: Array.isArray(payload?.paymentDenominations) ? payload.paymentDenominations : [],
                paymentSaveUrl: payload?.paymentSaveUrl || '',
                paymentType: 'cash',
                paymentBreakdown: {},
                initialPaymentBreakdown: {},
                paymentEntries: Array.isArray(payload?.paymentEntries) ? payload.paymentEntries : [],
                paymentSaving: false,
                gcashAmount: '',

                init() {
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
                        this.showToast(`Today's Total Sales: ${this.formatCurrency(this.todaysTotalSales)}`);
                    }
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

                formatDenomination(d) {
                    const denom = Number(d);
                    return `₱${denom.toLocaleString()}`;
                },

                formatCurrency(value) {
                    const n = Number(value || 0);
                    return `₱${n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
                },

                setPaymentType(type) {
                    const t = String(type || '').toLowerCase();
                    if (t !== 'cash' && t !== 'gcash') return;
                    this.paymentType = t;
                    if (t !== 'gcash') {
                        this.gcashAmount = '';
                    }
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

                        const entry = data?.entry || null;
                        if (entry) {
                            this.paymentEntries = [entry, ...(Array.isArray(this.paymentEntries) ? this.paymentEntries : [])];
                        }
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
                                quantities: this.quantities || {},
                            }),
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                            this.errorMessage = firstError || data?.message || 'Failed to save money inventory.';
                            return;
                        }

                        this.initialQuantities = JSON.parse(JSON.stringify(this.quantities || {}));
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
