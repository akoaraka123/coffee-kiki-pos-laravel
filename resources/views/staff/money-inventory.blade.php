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
            paymentUpdateUrlTemplate: @js(route('staff.money-inventory.payment-entries.update', ['entry' => '__ENTRY__'])),
            paymentDeleteUrlTemplate: @js(route('staff.money-inventory.payment-entries.destroy', ['entry' => '__ENTRY__'])),
            resetTodaysSalesUrl: @js(route('staff.money-inventory.reset-todays-sales')),
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

        <div x-show="!clockedIn" x-cloak class="rounded-2xl border border-white/10 bg-gradient-to-r from-emerald-600/25 via-white/5 to-sky-600/20 p-6 shadow-sm">
            <div class="text-sm font-semibold text-white/80">Today's Total Sales</div>
            <div class="mt-2 text-5xl font-extrabold tracking-tight text-white" x-text="formatCurrency(todaysTotalSales)"></div>
            <div class="mt-2 text-xs text-white/50">Automatically calculated from paid orders created today.</div>
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
                        <div
                            class="mt-3 rounded-xl border px-4 py-3 text-sm"
                            x-bind:class="paymentTotal() === Math.floor(todaysTotalSales || 0)
                                ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200'
                                : 'border-rose-500/30 bg-rose-500/10 text-rose-200'"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">Today's Total Sales</span>
                                <span class="font-semibold" x-text="formatCurrency(todaysTotalSales)"></span>
                            </div>
                            <div class="mt-1 text-xs" x-text="paymentTotal() === Math.floor(todaysTotalSales || 0) ? 'Matched' : 'Not matched'"> </div>
                        </div>
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
                                <div>
                                    <div class="text-sm font-semibold" x-text="entry.payment_type === 'gcash' ? 'GCash' : 'Cash'"></div>
                                    <div class="mt-0.5 text-xs text-white/50" x-text="formatEntryTime(entry.created_at)"></div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="mr-2 text-lg font-semibold" x-text="formatCurrency(entry.received_amount)"></div>
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-3 py-2 text-xs font-semibold text-white hover:bg-sky-500"
                                        x-on:click="openEditEntry(entry)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500"
                                        x-on:click="deleteEntry(entry)"
                                    >
                                        Delete
                                    </button>
                                </div>
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
                            <div
                                class="mt-3 rounded-xl border px-3 py-2 text-sm"
                                x-bind:class="editPaymentTotal() === Math.floor(todaysTotalSales || 0)
                                    ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200'
                                    : 'border-rose-500/30 bg-rose-500/10 text-rose-200'"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold">Today's Total Sales</span>
                                    <span class="font-semibold" x-text="formatCurrency(todaysTotalSales)"></span>
                                </div>
                                <div class="mt-1 text-xs" x-text="editPaymentTotal() === Math.floor(todaysTotalSales || 0) ? 'Matched' : 'Not matched'"> </div>
                            </div>
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
                paymentUpdateUrlTemplate: payload?.paymentUpdateUrlTemplate || '',
                paymentDeleteUrlTemplate: payload?.paymentDeleteUrlTemplate || '',
                resetTodaysSalesUrl: payload?.resetTodaysSalesUrl || '',
                paymentType: 'cash',
                paymentBreakdown: {},
                initialPaymentBreakdown: {},
                paymentEntries: Array.isArray(payload?.paymentEntries) ? payload.paymentEntries : [],
                paymentSaving: false,
                gcashAmount: '',

                editEntryOpen: false,
                editEntry: null,
                editPaymentBreakdown: {},
                editSaving: false,

                init() {
                    console.log('MoneyInventory init payload:', payload);
                    console.log('todaysTotalSales from backend:', payload?.todaysTotalSales);
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

                formatEntryTime(iso) {
                    const raw = String(iso || '').trim();
                    if (!raw) return '';
                    const d = new Date(raw);
                    if (Number.isNaN(d.getTime())) return '';
                    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                },

                buildEntryUrl(template, entryId) {
                    if (!template) return '';
                    return String(template).replace('__ENTRY__', encodeURIComponent(String(entryId)));
                },

                openEditEntry(entry) {
                    this.errorMessage = '';
                    this.editEntry = entry || null;
                    const base = (this.paymentDenominations || []).reduce((acc, d) => {
                        acc[String(d)] = 0;
                        return acc;
                    }, {});

                    const items = Array.isArray(entry?.items) ? entry.items : [];
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
                        const sales = Math.floor(this.todaysTotalSales || 0);
                        console.log('Auto-reset check after Edit:', { total, sales, match: total === sales });
                        if (total === sales) {
                            console.log('Triggering auto-reset...');
                            this.paymentEntries = [];
                            this.paymentBreakdown = this.paymentDenominations.reduce((acc, d) => {
                                acc[String(d)] = 0;
                                return acc;
                            }, {});
                            this.gcashAmount = '';
                            // Reset todaysTotalSales to 0 so UI reflects cleared day
                            this.todaysTotalSales = 0;
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
                        this.showToast(data?.message || 'Payment entry deleted.');
                    } catch (e) {
                        this.errorMessage = 'Failed to delete payment entry.';
                    }
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

                        // Auto-reset if payment total now matches today's total sales
                        const total = Math.floor(this.paymentTotal());
                        const sales = Math.floor(this.todaysTotalSales || 0);
                        console.log('Auto-reset check after Save:', { total, sales, match: total === sales });
                        if (total === sales) {
                            console.log('Triggering auto-reset...');
                            this.paymentEntries = [];
                            this.paymentBreakdown = this.paymentDenominations.reduce((acc, d) => {
                                acc[String(d)] = 0;
                                return acc;
                            }, {});
                            this.gcashAmount = '';
                            // Reset todaysTotalSales to 0 so UI reflects cleared day
                            this.todaysTotalSales = 0;
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
