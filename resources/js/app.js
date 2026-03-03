import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('orderHistory', () => ({
    modalOpen: false,
    loading: false,
    errorMessage: '',
    selectedOrder: null,
    editingItemId: null,
    editForm: {
        quantity: 1,
        note: '',
    },

    buildUrl(template, orderId, itemId = null) {
        if (!template) return null;
        let url = template;
        url = url.replace('__ORDER__', encodeURIComponent(String(orderId)));
        if (itemId !== null) {
            url = url.replace('__ITEM__', encodeURIComponent(String(itemId)));
        }
        return url;
    },
    csrfToken: null,
    orderTotals: {},
    orderStatuses: {},
    detailsUrlTemplate: null,
    updateItemUrlTemplate: null,
    deleteItemUrlTemplate: null,

    init() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        this.csrfToken = meta ? meta.getAttribute('content') : null;

        this.detailsUrlTemplate = this.$el.dataset.detailsUrlTemplate || null;
        this.updateItemUrlTemplate = this.$el.dataset.updateItemUrlTemplate || null;
        this.deleteItemUrlTemplate = this.$el.dataset.deleteItemUrlTemplate || null;

        try {
            this.orderTotals = JSON.parse(this.$el.dataset.orderTotals || '{}');
        } catch (e) {
            this.orderTotals = {};
        }

        try {
            this.orderStatuses = JSON.parse(this.$el.dataset.orderStatuses || '{}');
        } catch (e) {
            this.orderStatuses = {};
        }
    },

    formatPrice(value) {
        const num = Number(value || 0);
        return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    closeModal() {
        this.modalOpen = false;
        this.loading = false;
        this.errorMessage = '';
        this.selectedOrder = null;
        this.cancelEdit();
    },

    async openOrder(orderId) {
        this.modalOpen = true;
        this.loading = true;
        this.errorMessage = '';
        this.selectedOrder = null;
        this.cancelEdit();

        try {
            const detailsUrl = this.buildUrl(this.detailsUrlTemplate, orderId) || `/orders/${orderId}/details`;

            const res = await fetch(detailsUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const contentType = (res.headers.get('content-type') || '').toLowerCase();
            const data = await res.json().catch(() => null);
            if (!res.ok) {
                this.errorMessage = (data && data.message) ? data.message : 'Failed to load order details.';
                return;
            }

            if (!contentType.includes('application/json') || !data || !data.order) {
                this.errorMessage = 'Unexpected response while loading order details. Please refresh the page and try again.';
                return;
            }

            this.selectedOrder = data.order;
        } catch (e) {
            this.errorMessage = 'Failed to load order details.';
        } finally {
            this.loading = false;
        }
    },

    startEdit(item) {
        if (!this.selectedOrder) return;
        if (this.selectedOrder.status === 'cancelled') {
            this.errorMessage = 'This order is locked and cannot be modified.';
            return;
        }

        this.errorMessage = '';
        this.editingItemId = item.id;
        this.editForm.quantity = Number(item.quantity || 1);
        this.editForm.note = '';
    },

    cancelEdit() {
        this.editingItemId = null;
        this.editForm.quantity = 1;
        this.editForm.note = '';
    },

    async saveEdit(item) {
        if (!this.selectedOrder) return;

        const qty = Number(this.editForm.quantity);
        if (!Number.isFinite(qty) || qty < 0) {
            this.errorMessage = 'Quantity must be 0 or higher.';
            return;
        }

        if (qty === 0) {
            this.cancelEdit();
            await this.deleteItem(item);
            return;
        }

        this.errorMessage = '';
        try {
            const updateUrl =
                this.buildUrl(this.updateItemUrlTemplate, this.selectedOrder.id, item.id) ||
                `/orders/${this.selectedOrder.id}/items/${item.id}`;

            const res = await fetch(updateUrl, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(this.csrfToken ? { 'X-CSRF-TOKEN': this.csrfToken } : {}),
                },
                body: JSON.stringify({
                    quantity: qty,
                    note: this.editForm.note || null,
                }),
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                this.errorMessage = data.message || 'Failed to update item.';
                return;
            }

            this.selectedOrder = data.order;
            this.orderTotals[this.selectedOrder.id] = Number(this.selectedOrder.total || 0);
            this.orderStatuses[this.selectedOrder.id] = this.selectedOrder.status;
            this.cancelEdit();
        } catch (e) {
            this.errorMessage = 'Failed to update item.';
        }
    },

    async deleteItem(item) {
        if (!this.selectedOrder) return;
        if (this.selectedOrder.status === 'cancelled') {
            this.errorMessage = 'This order is locked and cannot be modified.';
            return;
        }

        if (!confirm('Delete this item from the order?')) {
            return;
        }

        this.errorMessage = '';
        try {
            const deleteUrl =
                this.buildUrl(this.deleteItemUrlTemplate, this.selectedOrder.id, item.id) ||
                `/orders/${this.selectedOrder.id}/items/${item.id}`;

            const res = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(this.csrfToken ? { 'X-CSRF-TOKEN': this.csrfToken } : {}),
                },
                body: JSON.stringify({
                    note: this.editingItemId === item.id ? (this.editForm.note || null) : null,
                }),
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                this.errorMessage = data.message || 'Failed to delete item.';
                return;
            }

            this.selectedOrder = data.order;
            this.orderTotals[this.selectedOrder.id] = Number(this.selectedOrder.total || 0);
            this.orderStatuses[this.selectedOrder.id] = this.selectedOrder.status;
            this.cancelEdit();
        } catch (e) {
            this.errorMessage = 'Failed to delete item.';
        }
    },
}));

Alpine.data('adminOrders', () => ({
    dailyModalOpen: false,
    dailyLoading: false,
    dailyError: '',
    dailyPayload: null,
    detailsJsonUrl: null,

    init() {
        this.detailsJsonUrl = this.$el.dataset.detailsJsonUrl || null;
    },

    formatPrice(value) {
        const num = Number(value || 0);
        return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    closeDaily() {
        this.dailyModalOpen = false;
        this.dailyLoading = false;
        this.dailyError = '';
        this.dailyPayload = null;
    },

    async openDaily(date, staffId) {
        this.dailyModalOpen = true;
        this.dailyLoading = true;
        this.dailyError = '';
        this.dailyPayload = null;

        try {
            if (!this.detailsJsonUrl) {
                this.dailyError = 'Missing details URL.';
                return;
            }

            const url = `${this.detailsJsonUrl}?staff=${encodeURIComponent(staffId)}&date=${encodeURIComponent(date)}`;
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                this.dailyError = data.message || 'Failed to load details.';
                return;
            }

            this.dailyPayload = data;
        } catch (e) {
            this.dailyError = 'Failed to load details.';
        } finally {
            this.dailyLoading = false;
        }
    },
}));

Alpine.start();
