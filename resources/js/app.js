import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.posOrder = function posOrder(initialLayout) {
    return {
        isDesktop: window.innerWidth >= 768,
        sidebarOpen: window.innerWidth >= 768,
        sidebarCollapsed: false,
        hoverOpened: false,
        layoutPosition: (initialLayout === 'left') ? 'left' : 'right',
        clockedIn: true,
        activeTab: 'milk_tea',
        searchQuery: '',
        focusedProductName: '',
        products: [],
        inventory: [],
        init() {
            console.log('Inventory data:', this.inventory);
        },
        cart: [],
        paymentType: 'cash',
        cashReceived: '',
        gcashReference: '',
        customerName: '',
        checkoutModal: false,
        checkoutError: '',
        isSubmitting: false,
        toastOpen: false,
        toastMessage: '',
        successOpen: false,
        successMessage: '',
        productModalOpen: false,
        modalProduct: null,
        gcashModal: false,
        gcashReferenceNumber: '',
        gcashSenderName: '',
        gcashSenderMobile: '',
        gcashProofImage: null,
        gcashProofPreview: null,
        gcashModalError: '',
        gcashDetailsSaved: false,
        normalizeCategory(value) {
            return String(value || '')
                .trim()
                .toLowerCase()
                .replace(/\s+/g, '_')
                .replace(/-+/g, '_');
        },
        displayCategory(value) {
            const v = String(value || '').trim();
            if (!v) return 'UNCATEGORIZED';
            return v
                .replace(/[_-]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .toUpperCase();
        },
        categories() {
            const map = new Map();
            (this.products || []).forEach(p => {
                const raw = p?.category;
                const key = this.normalizeCategory(raw);
                if (!key) return;
                if (!map.has(key)) {
                    const label = this.displayCategory(raw);
                    const icon = (label || 'C').trim().charAt(0) || 'C';
                    map.set(key, { key, label, icon });
                }
            });
            return Array.from(map.values());
        },
        init() {
            if ((!Array.isArray(this.products) || this.products.length === 0) && this.$el?.dataset?.products) {
                try {
                    const parsed = JSON.parse(this.$el.dataset.products);
                    if (Array.isArray(parsed)) {
                        this.products = parsed;
                    }
                } catch (e) {
                    this.products = [];
                }
            }

            if (!this.inventory || Object.keys(this.inventory).length === 0) {
                if (this.$el?.dataset?.inventory) {
                    try {
                        const parsed = JSON.parse(this.$el.dataset.inventory);
                        if (typeof parsed === 'object' && parsed !== null) {
                            this.inventory = parsed;
                        }
                    } catch (e) {
                        this.inventory = {};
                    }
                }
            }

            console.log('Inventory data:', this.inventory);

            const cats = this.categories();
            if (cats.length > 0 && !cats.some(c => c.key === this.activeTab)) {
                this.activeTab = cats[0].key;
            }

            window.addEventListener('resize', () => {
                this.isDesktop = window.innerWidth >= 768;
                if (this.isDesktop) {
                    this.sidebarOpen = true;
                } else {
                    this.sidebarCollapsed = false;
                    this.sidebarOpen = false;
                }
            });

            try {
                const saved = localStorage.getItem('pos_cart_v1');
                if (saved) {
                    const parsed = JSON.parse(saved);
                    if (Array.isArray(parsed)) {
                        this.cart = parsed;
                    }
                }
            } catch (e) {
                // ignore
            }

            const fromAttr = this.$el?.dataset?.initialLayout;
            if (typeof fromAttr === 'string' && fromAttr.length > 0) {
                this.layoutPosition = fromAttr === 'left' ? 'left' : 'right';
            }

            const clockedAttr = this.$el?.dataset?.clockedIn;
            if (typeof clockedAttr === 'string') {
                this.clockedIn = clockedAttr === '1' || clockedAttr.toLowerCase() === 'true';
            }

            this.$watch('searchQuery', (value) => {
                const q = (value || '').trim().toLowerCase();
                if (!q) {
                    this.focusedProductName = '';
                    return;
                }

                const match = this.products.find(p => String(p.name || '').toLowerCase().includes(q));
                if (!match) {
                    this.focusedProductName = '';
                    return;
                }

                this.focusedProductName = match.name;

                const matchCategory = this.normalizeCategory(match.category);
                if (matchCategory && matchCategory !== this.activeTab) {
                    this.activeTab = matchCategory;
                }

                this.$nextTick(() => {
                    const el = document.getElementById(this.productCardId(match.name));
                    if (el && typeof el.scrollIntoView === 'function') {
                        el.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
                    }
                });
            });

            this.$watch('paymentType', (value) => {
                if (value !== 'cash') {
                    this.cashReceived = '';
                }

                if (value !== 'gcash') {
                    this.gcashReference = '';
                }
            });
        },
        toggleSidebar() {
            if (this.isDesktop) {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                this.sidebarOpen = true;
                this.hoverOpened = false;
                return;
            }
            this.sidebarOpen = !this.sidebarOpen;
        },
        productCardId(name) {
            return 'pos-product-' + String(name || '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
        },
        productImageSrc(product) {
            const image = product?.image;
            if (image) return (window.__assetBaseUrl || '/') + image;
            return (window.__assetBaseUrl || '/') + 'images/coffee-doodle.png';
        },
        openProductModal(product) {
            if (!this.ensureClockedIn()) return;
            this.modalProduct = product || null;
            this.productModalOpen = true;
        },
        closeProductModal() {
            this.productModalOpen = false;
            this.modalProduct = null;
        },
        groupedProducts() {
            const q = (this.searchQuery || '').trim().toLowerCase();
            const filtered = (this.products || []).filter(p => {
                if (!p) return false;
                if (this.normalizeCategory(p.category) !== this.activeTab) return false;
                if (!q) return true;
                return String(p.name || '').toLowerCase().includes(q);
            });
            const grouped = {};

            filtered.forEach(product => {
                if (!product) return;

                const rawName = String(product.name || '').trim();
                if (!rawName) return;

                const price = Number(product.price);
                if (!Number.isFinite(price)) return;

                const key = rawName;
                if (!grouped[key]) {
                    grouped[key] = {
                        name: rawName,
                        image: product.image,
                        category: this.normalizeCategory(product.category),
                        sizes: [],
                    };
                }

                const sizeLabel = (typeof product.size === 'string' && product.size.trim() !== '')
                    ? product.size.trim()
                    : 'Regular';

                const alreadyExists = grouped[key].sizes.some(s =>
                    s.size === sizeLabel && Number(s.price) === price
                );

                if (!alreadyExists) {
                    grouped[key].sizes.push({
                        id: product.id,
                        size: sizeLabel,
                        price,
                    });
                }
            });

            return Object.values(grouped);
        },
        add(name, sizeInfo) {
            if (!this.ensureClockedIn()) return;
            if (!sizeInfo) return;

            const sizeLabel = (typeof sizeInfo.size === 'string' && sizeInfo.size.trim() !== '')
                ? sizeInfo.size.trim()
                : 'Regular';
            const price = Number(sizeInfo.price);
            if (!Number.isFinite(price)) return;

            // Check stock availability
            const stock = this.getStockQuantity(sizeInfo.id);
            const currentCartQuantity = this.cart
                .filter(i => i.product_id === sizeInfo.id)
                .reduce((sum, i) => sum + i.quantity, 0);

            if (stock === 0 || stock === '-') {
                this.showToast('This item is out of stock');
                return;
            }

            if (currentCartQuantity >= stock) {
                this.showToast(`Only ${stock} ${stock === 1 ? 'item' : 'items'} available in stock`);
                return;
            }

            const existing = this.cart.find(i => i.name === name && i.size === sizeLabel);
            if (existing) {
                if (existing.quantity >= stock) {
                    this.showToast(`Only ${stock} ${stock === 1 ? 'item' : 'items'} available in stock`);
                    return;
                }
                existing.quantity += 1;
                this.persistCart();
                return;
            }

            this.cart.push({
                product_id: sizeInfo.id,
                name,
                size: sizeLabel,
                price,
                quantity: 1,
            });
            this.persistCart();
        },
        increment(productId, size) {
            if (!this.ensureClockedIn()) return;
            const item = this.cart.find(i => i.product_id === productId && i.size === size);
            if (!item) return;

            // Check stock availability
            const stock = this.getStockQuantity(productId);
            if (stock === 0 || stock === '-') {
                this.showToast('This item is out of stock');
                return;
            }

            if (item.quantity >= stock) {
                this.showToast(`Only ${stock} ${stock === 1 ? 'item' : 'items'} available in stock`);
                return;
            }

            item.quantity += 1;
            this.persistCart();
        },
        decrement(productId, size) {
            if (!this.ensureClockedIn()) return;
            const item = this.cart.find(i => i.product_id === productId && i.size === size);
            if (!item) return;
            item.quantity -= 1;
            if (item.quantity <= 0) {
                this.cart = this.cart.filter(i => !(i.product_id === productId && i.size === size));
            }
            this.persistCart();
        },
        clear() {
            if (!this.ensureClockedIn()) return;
            this.cart = [];
            this.persistCart();
        },
        resetAfterCheckout() {
            this.cart = [];
            this.persistCart();
            this.paymentType = 'cash';
            this.cashReceived = '';
            this.customerName = '';
            this.checkoutModal = false;
            this.checkoutError = '';
        },
        showToast(message) {
            this.toastMessage = message || '';
            this.toastOpen = true;
            window.clearTimeout(this.__toastTimer);
            this.__toastTimer = window.setTimeout(() => {
                this.toastOpen = false;
            }, 2500);
        },
        ensureClockedIn() {
            if (this.clockedIn) return true;
            this.showToast('Staff is currently clocked out. You cannot add or checkout orders.');
            return false;
        },
        showSuccess(message) {
            this.successMessage = message || '';
            this.successOpen = true;
            window.clearTimeout(this.__successTimer);
            this.__successTimer = window.setTimeout(() => {
                this.successOpen = false;
            }, 1000);
        },
        persistCart() {
            try {
                localStorage.setItem('pos_cart_v1', JSON.stringify(this.cart));
            } catch (e) {
                // ignore
            }
        },
        total() {
            return this.cart.reduce((sum, i) => sum + (Number(i.price) * Number(i.quantity)), 0);
        },
        payloadItems() {
            return this.cart.map(i => ({
                product_id: i.product_id,
                quantity: i.quantity,
            }));
        },
        formatPrice(value) {
            const n = Number(value || 0);
            return n.toFixed(2);
        },
        openGcashModal() {
            this.gcashModal = true;
            this.gcashModalError = '';
        },
        closeGcashModal() {
            this.gcashModal = false;
            this.gcashModalError = '';
        },
        handleGcashProofUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                this.gcashModalError = 'Please select an image file';
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                this.gcashModalError = 'File size must be less than 5MB';
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                this.gcashProofImage = file;
                this.gcashProofPreview = e.target.result;
            };
            reader.readAsDataURL(file);
        },
        removeGcashProof() {
            this.gcashProofImage = null;
            this.gcashProofPreview = null;
            document.getElementById('gcash-proof-input').value = '';
        },
        validatePhilippineMobile(mobile) {
            if (!mobile) return false;
            const cleaned = String(mobile).trim();
            return /^09\d{9}$/.test(cleaned);
        },
        saveGcashDetails() {
            this.gcashModalError = '';

            if (!this.gcashProofImage && (!this.gcashReferenceNumber || !this.gcashReferenceNumber.trim())) {
                this.gcashModalError = 'Please upload transaction proof or enter reference number';
                return;
            }

            if (!this.gcashSenderName || !this.gcashSenderName.trim()) {
                this.gcashModalError = 'Please enter sender name';
                return;
            }

            if (!this.gcashSenderMobile || !this.gcashSenderMobile.trim()) {
                this.gcashModalError = 'Please enter sender mobile number';
                return;
            }

            if (!this.validatePhilippineMobile(this.gcashSenderMobile)) {
                this.gcashModalError = 'Please enter a valid Philippine mobile number (09XXXXXXXXX)';
                return;
            }

            this.gcashDetailsSaved = true;
            this.gcashReference = this.gcashReferenceNumber;
            this.closeGcashModal();
            this.showToast('GCash details saved successfully');
        },
        selectPaymentType(type) {
            this.paymentType = type;
            if (type === 'gcash') {
                this.openGcashModal();
            }
        },
        getStockStatus(productId) {
            if (!productId || !this.inventory) return 'in_stock';
            const inv = this.inventory[productId];
            if (!inv || typeof inv !== 'object') return 'in_stock';
            const stock = Number(inv.stock_quantity || 0);
            const threshold = Number(inv.low_stock_threshold || 10);
            if (stock === 0) return 'out_of_stock';
            if (stock <= threshold) return 'low_stock';
            return 'in_stock';
        },
        getStockQuantity(productId) {
            if (!productId || !this.inventory) return '-';
            const inv = this.inventory[productId];
            if (!inv || typeof inv !== 'object') return '-';
            return Number(inv.stock_quantity || 0);
        },
        changeAmount() {
            const total = Number(this.total() || 0);
            const cash = Number(this.cashReceived || 0);
            const diff = cash - total;
            return diff > 0 ? diff : 0;
        },
        startCheckout() {
            if (!this.ensureClockedIn()) return;
            this.checkoutError = '';
            if (this.cart.length === 0) return;

            const total = Number(this.total() || 0);

            if (this.paymentType === 'cash') {
                const cash = Number(this.cashReceived || 0);
                if (!Number.isFinite(cash) || cash <= 0) {
                    this.checkoutError = 'Please enter cash received.';
                    this.checkoutModal = true;
                    return;
                }

                if (cash < total) {
                    this.checkoutError = 'Insufficient payment amount.';
                    this.checkoutModal = true;
                    return;
                }
            }

            if (this.paymentType === 'gcash') {
                if (!this.gcashDetailsSaved) {
                    this.checkoutError = 'Please complete GCash payment details before checkout.';
                    this.checkoutModal = true;
                    return;
                }
            }

            this.checkoutModal = true;
        },
        async confirmCheckout() {
            if (!this.ensureClockedIn()) return;
            this.checkoutError = '';
            if (this.isSubmitting) return;
            if (this.cart.length === 0) {
                this.checkoutModal = false;
                return;
            }

            const total = Number(this.total() || 0);

            if (this.paymentType === 'cash') {
                const cash = Number(this.cashReceived || 0);
                if (!Number.isFinite(cash) || cash <= 0) {
                    this.checkoutError = 'Please enter cash received.';
                    return;
                }

                if (cash < total) {
                    this.checkoutError = 'Insufficient payment amount.';
                    return;
                }
            }

            const form = document.getElementById('pos-checkout-form');
            if (!form) {
                this.checkoutError = 'Checkout form not found. Please refresh the page and try again.';
                return;
            }

            const action = form.getAttribute('action');
            if (!action) {
                this.checkoutError = 'Checkout action not found. Please refresh the page and try again.';
                return;
            }

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
            if (!token) {
                this.checkoutError = 'Security token not found. Please refresh the page and try again.';
                return;
            }

            this.isSubmitting = true;
            try {
                const res = await fetch(action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        customer_name: (this.customerName || '').trim() || null,
                        status: 'paid',
                        items: JSON.stringify(this.payloadItems()),
                        payment_type: this.paymentType,
                        cash_received: this.paymentType === 'cash' ? Number(this.cashReceived || 0) : null,
                        gcash_reference: this.paymentType === 'gcash' ? String(this.gcashReferenceNumber || '').trim() || null : null,
                        gcash_sender_name: this.paymentType === 'gcash' ? String(this.gcashSenderName || '').trim() || null : null,
                        gcash_sender_mobile: this.paymentType === 'gcash' ? String(this.gcashSenderMobile || '').trim() || null : null,
                        gcash_proof_image: this.paymentType === 'gcash' ? String(this.gcashProofPreview || '').trim() || null : null,
                        total_amount: Number(this.formatPrice(this.total())),
                    }),
                });

                const contentType = (res.headers.get('content-type') || '').toLowerCase();
                const isJson = contentType.includes('application/json');
                const data = isJson ? await res.json().catch(() => ({})) : {};

                if (!res.ok) {
                    const message = data?.message || 'Failed to confirm order.';
                    const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                    this.checkoutError = firstError || message;
                    return;
                }

                const orderNumber = data?.order_number ? String(data.order_number) : '';
                this.resetAfterCheckout();
                this.showSuccess(orderNumber ? `Order ${orderNumber} saved.` : 'Order saved.');
            } catch (e) {
                this.checkoutError = 'Failed to confirm order. Please check your connection and try again.';
            } finally {
                this.isSubmitting = false;
            }
        },
    };
};

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
    imagePreviewOpen: false,
    imagePreviewUrl: '',

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

        if (typeof document !== 'undefined' && document.body) {
            document.body.classList.remove('overflow-hidden');
        }
    },

    async openOrder(orderId) {
        this.modalOpen = true;

        if (typeof document !== 'undefined' && document.body) {
            document.body.classList.add('overflow-hidden');
        }

        await this.fetchOrderDetails(orderId);
    },

    async fetchOrderDetails(orderId) {
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
            const isJson = contentType.includes('application/json');
            const data = isJson ? await res.json().catch(() => ({})) : {};

            if (!res.ok) {
                this.errorMessage = data.message || 'Failed to load order details.';
                return;
            }

            if (!data || !data.order) {
                this.errorMessage = 'Unexpected response while loading order details. Please refresh the page and try again.';
                return;
            }

            const order = data.order;
            if (Array.isArray(data.items)) {
                order.items = data.items;
            }

            this.selectedOrder = order;
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
            this.orderStatuses[this.selectedOrder.id] = this.selectedOrder.status_label || this.selectedOrder.status;
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
            this.orderStatuses[this.selectedOrder.id] = this.selectedOrder.status_label || this.selectedOrder.status;
            this.cancelEdit();
        } catch (e) {
            this.errorMessage = 'Failed to delete item.';
        }
    },

    openImagePreview(imageUrl) {
        this.imagePreviewUrl = imageUrl;
        this.imagePreviewOpen = true;
    },

    closeImagePreview() {
        this.imagePreviewOpen = false;
        this.imagePreviewUrl = '';
    },

}));

Alpine.data('adminOrders', () => ({
    dailyModalOpen: false,
    dailyLoading: false,
    dailyError: '',
    dailyPayload: null,
    detailsJsonUrl: null,
    expandedOrderId: null,
    imagePreviewOpen: false,
    imagePreviewUrl: '',
    deletingToday: false,

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
        this.expandedOrderId = null;
    },

    toggleOrder(orderId) {
        if (this.expandedOrderId === orderId) {
            this.expandedOrderId = null;
        } else {
            this.expandedOrderId = orderId;
        }
    },

    openImagePreview(imageUrl) {
        this.imagePreviewUrl = imageUrl;
        this.imagePreviewOpen = true;
    },

    closeImagePreview() {
        this.imagePreviewOpen = false;
        this.imagePreviewUrl = '';
    },

    async deleteTodaySales(staffId) {
        if (!confirm('Are you sure you want to delete all orders from today? This action cannot be undone.')) return;
        this.deletingToday = true;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
            if (!token) {
                alert('Security token not found. Please refresh the page.');
                return;
            }
            const res = await fetch('/admin/orders/delete-today-sales', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ staff: staffId || '' }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                alert(data.message || 'Orders deleted successfully.');
                window.location.reload();
            } else {
                alert('Failed to delete: ' + (data.message || 'Unknown error'));
            }
        } catch (e) {
            alert('Error: ' + e.message);
        } finally {
            this.deletingToday = false;
        }
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

Alpine.data('adminProductsIndex', () => ({
    searchQuery: '',
    groups: [],
    activeTab: 'all',
    totalStock: 0,

    toastMessage: '',
    toastTimer: null,

    csrfToken: null,

    editModalOpen: false,
    editSaving: false,
    editError: '',
    editErrors: {},
    editCategories: [],

    addModalOpen: false,
    addSaving: false,
    addError: '',
    addErrors: {},
    addImageFile: null,
    addImagePreviewUrl: '',
    addForm: {
        name: '',
        category: '',
        new_category: '',
        is_active: true,
        sizes: [{ size: '', price: '' }],
    },

    categoriesModalOpen: false,
    categoriesLoading: false,
    categoriesError: '',
    categoriesRows: [],

    editForm: {
        id: null,
        key: '',
        name: '',
        category: '',
        new_category: '',
        image: '',
        is_active: false,
        sizes: [{ size: '', price: '' }],
    },
    editImageFile: null,
    editImagePreviewUrl: '',

    init() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        this.csrfToken = meta ? meta.getAttribute('content') : null;

        this.refreshGroups();
    },

    get categoryOptions() {
        const set = new Set();
        (this.groups || []).forEach(g => {
            const raw = String(g?.product?.category || '').trim();
            if (raw) set.add(raw);
        });
        return Array.from(set.values()).sort((a, b) => a.localeCompare(b));
    },

    showToast(message) {
        this.toastMessage = String(message || '').trim();
        if (this.toastTimer) {
            try { clearTimeout(this.toastTimer); } catch (e) {}
        }
        if (!this.toastMessage) return;
        this.toastTimer = setTimeout(() => {
            this.toastMessage = '';
            this.toastTimer = null;
        }, 2500);
    },

    setScrollLocked(locked) {
        if (typeof document === 'undefined' || !document.body) return;
        if (locked) {
            document.body.classList.add('overflow-hidden');
        } else {
            document.body.classList.remove('overflow-hidden');
        }
    },

    normalizeCategory(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/\s+/g, '_')
            .replace(/-+/g, '_');
    },

    displayCategory(value) {
        const v = String(value || '').trim();
        if (!v) return 'Uncategorized';
        return v
            .replace(/[_-]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/\b\w/g, c => c.toUpperCase());
    },

    get categories() {
        const map = new Map();
        (this.groups || []).forEach(item => {
            const raw = item?.product?.category;
            const key = this.normalizeCategory(raw);
            if (!key) return;
            if (!map.has(key)) {
                map.set(key, { key, label: this.displayCategory(raw) });
            }
        });
        return Array.from(map.values());
    },

    formatPrice(value) {
        const n = Number(value || 0);
        return n.toFixed(2);
    },

    filteredGroups() {
        const q = String(this.searchQuery || '').trim().toLowerCase();
        const base = (this.groups || []).filter(item => {
            if (this.activeTab === 'all') return true;
            const catKey = this.normalizeCategory(item?.product?.category);
            return catKey === this.activeTab;
        });

        if (!q || q.length < 2) return base;

        return base.filter(item => {
            const name = String(item.product?.name || '').toLowerCase();
            const category = String(item.product?.category || '').toLowerCase();
            return name.includes(q) || category.includes(q);
        });
    },

    confirmDelete(e) {
        const ok = confirm('Are you sure you want to delete this product?');
        if (!ok) return;
        e.target.submit();
    },

    fieldError(field) {
        const v = this.editErrors?.[field];
        if (!v) return '';
        if (Array.isArray(v)) return v.join(' ');
        return String(v);
    },

    fieldErrorFrom(errorsObj, field) {
        const v = errorsObj?.[field];
        if (!v) return '';
        if (Array.isArray(v)) return v.join(' ');
        return String(v);
    },

    editImagePreviewSrc() {
        if (this.editImagePreviewUrl) return this.editImagePreviewUrl;
        if (this.editForm.image) return (window.__assetBaseUrl || '/') + this.editForm.image;
        return (window.__assetBaseUrl || '/') + 'images/coffee-doodle.png';
    },

    onImageChange(e) {
        const file = e?.target?.files?.[0];
        this.editImageFile = file || null;
        if (this.editImagePreviewUrl) {
            try { URL.revokeObjectURL(this.editImagePreviewUrl); } catch (err) {}
        }
        this.editImagePreviewUrl = file ? URL.createObjectURL(file) : '';
    },

    closeEdit() {
        this.editModalOpen = false;
        this.editSaving = false;
        this.editError = '';
        this.editErrors = {};
        this.editCategories = [];
        this.editForm = {
            id: null,
            key: '',
            name: '',
            category: '',
            new_category: '',
            image: '',
            is_active: false,
            sizes: [{ size: '', price: '' }],
        };
        this.editImageFile = null;
        if (this.editImagePreviewUrl) {
            try { URL.revokeObjectURL(this.editImagePreviewUrl); } catch (err) {}
        }
        this.editImagePreviewUrl = '';
        if (this.$refs?.imageInput) {
            this.$refs.imageInput.value = '';
        }

        this.setScrollLocked(false);
    },

    addSizeRow() {
        this.editForm.sizes.push({ size: '', price: '' });
    },

    removeSizeRow(idx) {
        if (this.editForm.sizes.length <= 1) return;
        const ok = confirm('Are you sure you want to delete this size?');
        if (!ok) return;
        this.editForm.sizes.splice(idx, 1);
    },

    async openEdit(item) {
        this.editError = '';
        this.editErrors = {};
        if (!item?.product?.id) {
            this.editError = 'Unable to open editor.';
            return;
        }
        this.editModalOpen = true;
        this.setScrollLocked(true);

        try {
            const url = `/admin/products/${encodeURIComponent(item.product.id)}/edit-data`;
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!res.ok) {
                this.editError = 'Failed to load product data.';
                return;
            }

            const data = await res.json().catch(() => ({}));
            const group = data?.group;
            const product = group?.product;

            this.editCategories = Array.isArray(data?.categories) ? data.categories : [];
            this.editForm.id = product?.id ?? item.product.id;
            this.editForm.key = group?.key || item.key;
            this.editForm.name = product?.name || item.product.name || '';
            this.editForm.category = product?.category || item.product.category || '';
            this.editForm.new_category = '';
            this.editForm.image = product?.image || item.product.image || '';
            this.editForm.is_active = !!product?.is_active;
            this.editForm.sizes = Array.isArray(group?.sizes) && group.sizes.length > 0
                ? group.sizes.map(s => ({ size: s.size || 'Regular', price: String(s.price ?? '') }))
                : [{ size: '', price: '' }];
        } catch (err) {
            this.editError = 'Failed to load product data.';
        }
    },

    upsertGroup(oldKey, newGroup) {
        if (!newGroup) return;
        const newKey = newGroup.key;
        const next = (this.groups || []).filter(g => g.key !== oldKey);
        const existingIndex = next.findIndex(g => g.key === newKey);

        if (existingIndex >= 0) {
            next[existingIndex] = newGroup;
        } else {
            next.unshift(newGroup);
        }

        this.groups = next;
    },

    async saveEdit() {
        if (this.editSaving) return;
        this.editSaving = true;
        this.editError = '';
        this.editErrors = {};

        try {
            const id = this.editForm.id;
            const url = `/admin/products/${encodeURIComponent(id)}`;

            const fd = new FormData();
            fd.append('_method', 'PUT');
            if (this.csrfToken) {
                fd.append('_token', this.csrfToken);
            }
            fd.append('name', this.editForm.name || '');
            fd.append('category', this.editForm.category || '');
            fd.append('new_category', this.editForm.new_category || '');
            if (this.editForm.is_active) {
                fd.append('is_active', '1');
            }

            (this.editForm.sizes || []).forEach(row => {
                fd.append('sizes[]', row?.size ?? '');
                fd.append('prices[]', row?.price ?? '');
            });

            if (this.editImageFile) {
                fd.append('image', this.editImageFile);
            }

            const res = await fetch(url, {
                method: 'POST',
                body: fd,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (res.status === 422) {
                const data = await res.json().catch(() => ({}));
                this.editErrors = data?.errors || {};
                this.editSaving = false;
                return;
            }

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                this.editError = data?.message || 'Failed to save changes.';
                this.editSaving = false;
                return;
            }

            const data = await res.json().catch(() => ({}));
            const group = data?.group;
            const oldKey = data?.oldKey || this.editForm.key;

            if (group) {
                this.upsertGroup(oldKey, group);
            }

            this.editSaving = false;
            this.closeEdit();
            this.showToast(data?.message || 'Saved.');
        } catch (err) {
            this.editError = 'Failed to save changes.';
            this.editSaving = false;
        }
    },

    resetAddForm() {
        this.addSaving = false;
        this.addError = '';
        this.addErrors = {};
        this.addForm = {
            name: '',
            category: '',
            new_category: '',
            is_active: true,
            sizes: [{ size: '', price: '' }],
        };
        this.addImageFile = null;
        if (this.addImagePreviewUrl) {
            try { URL.revokeObjectURL(this.addImagePreviewUrl); } catch (err) {}
        }
        this.addImagePreviewUrl = '';
        if (this.$refs?.addImageInput) {
            this.$refs.addImageInput.value = '';
        }
    },

    openAddModal() {
        this.resetAddForm();
        this.addModalOpen = true;
        this.setScrollLocked(true);
    },

    closeAddModal() {
        this.addModalOpen = false;
        this.addSaving = false;
        this.setScrollLocked(false);
    },

    addImagePreviewSrc() {
        if (this.addImagePreviewUrl) return this.addImagePreviewUrl;
        return (window.__assetBaseUrl || '/') + 'images/coffee-doodle.png';
    },

    onAddImageChange(e) {
        const file = e?.target?.files?.[0];
        this.addImageFile = file || null;
        if (this.addImagePreviewUrl) {
            try { URL.revokeObjectURL(this.addImagePreviewUrl); } catch (err) {}
        }
        this.addImagePreviewUrl = file ? URL.createObjectURL(file) : '';
    },

    addAddSizeRow() {
        this.addForm.sizes.push({ size: '', price: '' });
    },

    removeAddSizeRow(idx) {
        if (this.addForm.sizes.length <= 1) return;
        const ok = confirm('Are you sure you want to delete this size?');
        if (!ok) return;
        this.addForm.sizes.splice(idx, 1);
    },

    async submitAddProduct() {
        if (this.addSaving) return;
        this.addSaving = true;
        this.addError = '';
        this.addErrors = {};

        try {
            const fd = new FormData();
            if (this.csrfToken) {
                fd.append('_token', this.csrfToken);
            }
            fd.append('name', this.addForm.name || '');
            fd.append('category', this.addForm.category || '');
            fd.append('new_category', this.addForm.new_category || '');
            if (this.addForm.is_active) {
                fd.append('is_active', '1');
            }

            (this.addForm.sizes || []).forEach(row => {
                fd.append('sizes[]', row?.size ?? '');
                fd.append('prices[]', row?.price ?? '');
            });

            if (this.addImageFile) {
                fd.append('image', this.addImageFile);
            }

            const res = await fetch('/admin/products', {
                method: 'POST',
                body: fd,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (res.status === 422) {
                const data = await res.json().catch(() => ({}));
                this.addErrors = data?.errors || {};
                this.addSaving = false;
                return;
            }

            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                this.addError = data?.message || 'Failed to create product.';
                this.addSaving = false;
                return;
            }

            const group = data?.group;
            if (group) {
                this.upsertGroup(null, group);
            }

            this.addSaving = false;
            this.closeAddModal();
            this.showToast(data?.message || 'Product created.');
        } catch (err) {
            this.addError = 'Failed to create product.';
            this.addSaving = false;
        }
    },

    async refreshGroups() {
        try {
            const res = await fetch('/admin/products/json', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json().catch(() => ({}));
            if (Array.isArray(data?.groups)) {
                this.groups = data.groups;
            }
            if (typeof data?.totalStock === 'number') {
                this.totalStock = data.totalStock;
            }
        } catch (e) {}
    },

    async openCategoriesModal() {
        this.categoriesModalOpen = true;
        this.categoriesLoading = true;
        this.categoriesError = '';
        this.categoriesRows = [];
        this.setScrollLocked(true);

        try {
            const res = await fetch('/admin/categories/json', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!res.ok) {
                this.categoriesError = 'Failed to load categories.';
                this.categoriesLoading = false;
                return;
            }

            const data = await res.json().catch(() => ({}));
            const cats = Array.isArray(data?.categories) ? data.categories : [];
            const counts = data?.counts || {};
            this.categoriesRows = cats.map(c => ({
                key: String(c),
                oldName: String(c),
                newName: String(c),
                count: Number(counts?.[c] || 0),
                saving: false,
                deleting: false,
                confirmingDelete: false,
                error: '',
            }));
            this.categoriesLoading = false;
        } catch (err) {
            this.categoriesError = 'Failed to load categories.';
            this.categoriesLoading = false;
        }
    },

    closeCategoriesModal() {
        this.categoriesModalOpen = false;
        this.categoriesLoading = false;
        this.categoriesError = '';
        this.categoriesRows = [];
        this.setScrollLocked(false);
    },

    async applyCategoriesPayload(payload) {
        const cats = Array.isArray(payload?.categories) ? payload.categories : [];
        const counts = payload?.counts || {};
        this.categoriesRows = cats.map(c => ({
            key: String(c),
            oldName: String(c),
            newName: String(c),
            count: Number(counts?.[c] || 0),
            saving: false,
            deleting: false,
            confirmingDelete: false,
            error: '',
        }));
        await this.refreshGroups();
    },

    async saveCategory(cat) {
        if (!cat || cat.saving) return;
        cat.error = '';
        const oldName = String(cat.oldName || '').trim();
        const newName = String(cat.newName || '').trim();
        if (!oldName || !newName) {
            cat.error = 'Category name is required.';
            return;
        }
        cat.saving = true;

        try {
            const res = await fetch('/admin/categories', {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(this.csrfToken ? { 'X-CSRF-TOKEN': this.csrfToken } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    old_category: oldName,
                    new_category: newName,
                }),
            });

            if (res.status === 422) {
                const data = await res.json().catch(() => ({}));
                const errs = data?.errors || {};
                cat.error = Array.isArray(errs?.new_category) ? errs.new_category.join(' ') : (errs?.new_category || 'Validation failed.');
                cat.saving = false;
                return;
            }

            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                cat.error = data?.message || 'Failed to update category.';
                cat.saving = false;
                return;
            }

            await this.applyCategoriesPayload(data);
            this.showToast('Category updated.');
        } catch (err) {
            cat.error = 'Failed to update category.';
        } finally {
            cat.saving = false;
        }
    },

    async deleteCategory(cat) {
        if (!cat || cat.deleting) return;
        const name = String(cat.oldName || '').trim();
        if (!name) return;
        cat.deleting = true;
        cat.error = '';

        try {
            const res = await fetch('/admin/categories', {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(this.csrfToken ? { 'X-CSRF-TOKEN': this.csrfToken } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ category: name }),
            });

            if (res.status === 422) {
                const data = await res.json().catch(() => ({}));
                const errs = data?.errors || {};
                cat.error = Array.isArray(errs?.category) ? errs.category.join(' ') : (errs?.category || 'Validation failed.');
                cat.deleting = false;
                return;
            }

            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                cat.error = data?.message || 'Failed to delete category.';
                cat.deleting = false;
                return;
            }

            await this.applyCategoriesPayload(data);
            this.showToast('Category deleted.');
        } catch (err) {
            cat.error = 'Failed to delete category.';
        } finally {
            cat.deleting = false;
        }
    },
}));

Alpine.data('moneyInventory', (payload) => {
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
        lowerTodaysTotalSales: Number(payload?.lowerTodaysTotalSales || payload?.todaysTotalSales || 0),

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

                    const prevTotal = this.todaysTotalSales;
                    this.todaysTotalSales = nextTotal;
                    this.todaysCashSales = nextCash;
                    this.todaysGcashSales = nextGcash;
                    this.lowerTodaysTotalSales = nextLower;
                    this.reconciled = nextReconciled;
                    this.reconciledAt = nextReconciledAt;
                    this.dateDisplay = nextDateDisplay;

                    if (nextTotal > prevTotal && prevTotal === 0) {
                        this.showToast(`New sales detected (${this.dateDisplay}). Please record the next transaction.`);
                    } else if (nextTotal > prevTotal && nextTotal > 0) {
                        this.showToast(`Sales updated (${this.dateDisplay}): ${this.formatCurrency(nextTotal)}`);
                    }
                } catch (e) {
                    // ignore polling errors
                }
            }, 10000);
        },

        isViewingToday() {
            const today = new Date().toISOString().split('T')[0];
            return this.date === today;
        },

        scheduleMidnightRollover() {
            if (!this.isViewingToday()) return;

            const now = new Date();
            const tomorrow = new Date(now);
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(0, 0, 0, 0);

            const msUntilMidnight = tomorrow - now;
            if (msUntilMidnight > 0) {
                this.__midnightTimer = window.setTimeout(() => {
                    window.location.reload();
                }, msUntilMidnight);
            }
        },

        refreshSalesData() {
            window.location.reload();
        },

        setPaymentType(type) {
            this.paymentType = type;
            this.resetPayment();
        },

        paymentQty(d) {
            return Number(this.paymentBreakdown?.[String(d)] || 0);
        },

        subtotal(d) {
            return Number(d) * this.paymentQty(d);
        },

        subtotalLabel(d) {
            return `Subtotal: ${this.formatCurrency(this.subtotal(d))}`;
        },

        total() {
            return (this.denominations || []).reduce((sum, d) => sum + this.subtotal(d), 0);
        },

        paymentTotal() {
            return (this.paymentDenominations || []).reduce((sum, d) => sum + this.subtotal(d), 0);
        },

        isCashVerified() {
            const cashSales = this.todaysCashSales || 0;
            const cashPayments = this.paymentsTotalByTypeAfterCutoff('cash');
            return cashPayments >= cashSales;
        },

        isGcashVerified() {
            const gcashSales = this.todaysGcashSales || 0;
            const gcashPayments = this.paymentsTotalByTypeAfterCutoff('gcash');
            return gcashPayments >= gcashSales;
        },

        paymentsTotalByTypeAfterCutoff(type) {
            const cutoff = this.reconciledAt ? new Date(this.reconciledAt) : null;
            return (this.paymentEntries || [])
                .filter(e => e.payment_type === type && (!cutoff || new Date(e.created_at) > cutoff))
                .reduce((sum, e) => sum + (Number(e.received_amount) || 0), 0);
        },

        calculateBalanceDifference() {
            const cashDiff = this.calculateDifference(this.todaysCashSales, this.paymentsTotalByTypeAfterCutoff('cash'));
            const gcashDiff = this.calculateDifference(this.todaysGcashSales, this.paymentsTotalByTypeAfterCutoff('gcash'));
            return cashDiff + gcashDiff;
        },

        calculateDifference(expected, actual) {
            return Number(expected || 0) - Number(actual || 0);
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

        getDifferenceClass(expected, actual) {
            const diff = this.calculateDifference(expected, actual);
            if (diff === 0) return 'text-emerald-400';
            if (diff > 0) return 'text-amber-400';
            return 'text-rose-400';
        },

        formatCurrency(value) {
            const n = Number(value || 0);
            return `₱${n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
        },

        formatEntryTime(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
        },

        formatDisplayDate(dateStr) {
            if (!dateStr) return '';
            return String(dateStr);
        },

        formatDenomination(d) {
            const denom = Number(d);
            return `₱${denom.toLocaleString()}`;
        },

        setPaymentQty(d, value) {
            const key = String(d);
            const n = Number(value);
            if (!Number.isFinite(n) || n < 0) return;
            this.paymentBreakdown[key] = n;
        },

        addPaymentDenomination(d) {
            const q = this.paymentQty(d);
            this.setPaymentQty(d, q + 1);
        },

        removePaymentDenomination(d) {
            const q = this.paymentQty(d);
            this.setPaymentQty(d, q - 1);
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

        setEditQty(d, value) {
            const key = String(d);
            const n = Number(value);
            if (!Number.isFinite(n) || n < 0) return;
            this.editPaymentBreakdown[key] = n;
        },

        editIncrement(d) {
            const q = this.editQty(d);
            this.setEditQty(d, q + 1);
        },

        editDecrement(d) {
            const q = this.editQty(d);
            this.setEditQty(d, q - 1);
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
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
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

                this.resetPayment();
                this.showToast(data?.message || 'Payment entry saved.');
            } catch (e) {
                this.errorMessage = 'Failed to save payment entry. Please check your connection and try again.';
            } finally {
                this.paymentSaving = false;
            }
        },

        async resetTodaysSales() {
            if (!this.resetTodaysSalesUrl) {
                this.errorMessage = 'Reset endpoint not configured.';
                return;
            }

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
            if (!token) {
                this.errorMessage = 'Security token not found. Please refresh the page and try again.';
                return;
            }

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
                        payment_type: this.paymentType,
                    }),
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.errorMessage = data?.message || 'Failed to reset today\'s sales.';
                    return;
                }

                await this.refreshSalesData();
                this.showToast(data?.message || 'Today\'s sales reset.');
            } catch (e) {
                this.errorMessage = 'Failed to reset today\'s sales. Please check your connection and try again.';
            }
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
                            breakdown: {},
                            order_id: order.id,
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

                this.verifiedGcashOrderIds = [];
                this.showToast('GCash verification saved successfully.');
            } catch (e) {
                this.errorMessage = 'Failed to save GCash verification.';
            } finally {
                this.paymentSaving = false;
            }
        },

        verifiedGcashTotal() {
            return this.gcashOrders
                .filter(o => this.verifiedGcashOrderIds.includes(o.id))
                .reduce((sum, o) => sum + (Number(o.total_amount) || 0), 0);
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

            const url = this.paymentUpdateUrlTemplate.replace('__ENTRY__', this.editEntry.id);
            const breakdown = this.editPaymentBreakdown || {};
            const total = this.editPaymentTotal();

            this.editSaving = true;
            try {
                const res = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        received_amount: total,
                        breakdown,
                    }),
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                    this.errorMessage = firstError || data?.message || 'Failed to update payment entry.';
                    return;
                }

                const updated = data?.payment_entry || data?.entry || null;
                if (updated) {
                    const idx = this.paymentEntries.findIndex(e => e.id === this.editEntry.id);
                    if (idx >= 0) {
                        this.paymentEntries[idx] = updated;
                    }
                }

                this.closeEditEntry();
                this.showToast(data?.message || 'Payment entry updated.');
            } catch (e) {
                this.errorMessage = 'Failed to update payment entry. Please check your connection and try again.';
            } finally {
                this.editSaving = false;
            }
        },

        editQty(d) {
            return Number(this.editPaymentBreakdown?.[String(d)] || 0);
        },

        openEditEntry(entry) {
            this.errorMessage = '';
            this.editEntry = entry;
            const base = this.paymentDenominations.reduce((acc, d) => {
                acc[String(d)] = 0;
                return acc;
            }, {});

            if (Array.isArray(entry?.items)) {
                entry.items.forEach(item => {
                    const denom = String(item.denomination);
                    const qty = Number(item.quantity || 0);
                    if (base.hasOwnProperty(denom)) {
                        base[denom] = qty;
                    }
                });
            }

            this.editPaymentBreakdown = base;
            this.editEntryOpen = true;
            this.showToast(`Today's Total Sales: ${this.formatCurrency(this.todaysTotalSales)}`);
        },

        closeEditEntry() {
            this.editEntryOpen = false;
            this.editEntry = null;
            this.editPaymentBreakdown = {};
        },

        async deleteEntry(entry) {
            if (!confirm('Delete this payment entry?')) return;

            const url = this.paymentDeleteUrlTemplate.replace('__ENTRY__', entry.id);
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
            if (!token) {
                this.errorMessage = 'Security token not found. Please refresh the page and try again.';
                return;
            }

            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                });

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    this.errorMessage = data?.message || 'Failed to delete payment entry.';
                    return;
                }

                this.paymentEntries = this.paymentEntries.filter(e => e.id !== entry.id);
                this.showToast('Payment entry deleted.');
            } catch (e) {
                this.errorMessage = 'Failed to delete payment entry. Please check your connection and try again.';
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

                // Reset today's sales after saving money inventory
                await this.resetTodaysSales();

                this.showToast(data?.message || 'Money inventory saved.');
            } catch (e) {
                this.errorMessage = 'Failed to save money inventory. Please check your connection and try again.';
            } finally {
                this.saving = false;
            }
        },
    };
});

try {
    Alpine.start();
    window.__alpineStarted = true;
    window.__alpineStartError = null;
} catch (e) {
    window.__alpineStarted = false;
    window.__alpineStartError = e;
    console.error('Alpine.start failed', e);
}
