@extends('layouts.dashboard')

@section('title', 'Products')

@section('content')
    <div class="space-y-6" x-data="adminProductsIndex(@js($groups))">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Products</h2>
                <p class="mt-1 text-sm text-white/50">Create, edit, and remove products shown in the POS menu.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 shadow-sm hover:bg-white/10">
                    Manage Categories
                </a>
                <a href="{{ route('admin.products.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                    Add Product
                </a>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="rounded-full px-4 py-2 text-sm font-semibold transition"
                x-on:click="activeTab = 'all'"
                x-bind:class="activeTab === 'all' ? 'bg-[#efe9df] text-[#1c1c1c]' : 'border border-white/10 bg-white/5 text-white/70 hover:bg-white/10 hover:text-white'"
            >
                All
            </button>

            <template x-for="cat in categories" :key="cat.key">
                <button
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition"
                    x-on:click="activeTab = cat.key"
                    x-bind:class="activeTab === cat.key ? 'bg-[#efe9df] text-[#1c1c1c]' : 'border border-white/10 bg-white/5 text-white/70 hover:bg-white/10 hover:text-white'"
                    x-text="cat.label"
                ></button>
            </template>
        </div>

        <div class="relative">
            <input
                type="text"
                placeholder="Search products..."
                x-model="searchQuery"
                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 pl-12 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
            />
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-white/40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="1.8" />
                <path d="M16.3 16.3 21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="item in filteredGroups()" :key="item.key">
                <div class="group rounded-2xl border border-white/10 bg-white/5 shadow-lg hover:bg-white/10 transition p-5 flex flex-col h-full">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-xl font-semibold tracking-wide text-white" x-text="item.product.name"></h3>
                        <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/80" x-text="item.product.is_active ? 'active' : 'inactive'"></span>
                    </div>

                    <div class="mt-1 text-xs text-white/50" x-text="item.product.category"></div>

                    <div class="flex items-start justify-center pt-2">
                        <img
                            :src="item.product.image ? ((window.__assetBaseUrl || '/') + item.product.image) : ((window.__assetBaseUrl || '/') + 'images/coffee-doodle.png')"
                            :alt="item.product.name"
                            x-on:error="if (!$el.dataset.fallbackTried) { $el.dataset.fallbackTried = '1'; $el.src = (window.__assetBaseUrl || '/') + 'images/coffee-doodle.png'; }"
                            class="max-h-60 w-auto object-contain drop-shadow-xl"
                            loading="lazy"
                            style="image-rendering: -webkit-optimize-contrast;"
                        />
                    </div>

                    <div class="mt-3 space-y-2">
                        <template x-for="size in item.sizes" :key="String(size.size || '') + '-' + String(size.price)">
                            <div class="w-full flex items-center justify-between px-4 py-3 rounded-xl bg-black/40 border border-white/10 text-white text-lg font-medium">
                                <span x-text="size.size || 'Regular'"></span>
                                <span>₱<span x-text="formatPrice(size.price)"></span></span>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-4">
                        <button type="button" class="inline-flex items-center px-3 py-2 rounded-lg bg-white/10 text-white/70 hover:bg-white/20 hover:text-white font-medium text-sm transition" x-on:click="openEdit(item)">
                            Edit
                        </button>
                        <form method="POST" :action="item.deleteUrl" x-on:submit.prevent="confirmDelete($event)">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg bg-rose-500/10 text-rose-300 hover:bg-rose-500/20 hover:text-rose-200 font-medium text-sm transition">Delete</button>
                        </form>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="filteredGroups().length === 0" class="rounded-xl border border-white/10 bg-white/5 px-6 py-10 text-center text-white/60">
            No products found.
        </div>

        <div class="fixed inset-0 z-50" x-show="editModalOpen" x-cloak x-on:keydown.escape.window="closeEdit()">
            <div class="absolute inset-0 bg-black/70" x-transition.opacity x-on:click="closeEdit()"></div>
            <div class="absolute inset-0 grid place-items-center px-4">
                <div class="w-[min(92vw,44rem)] rounded-2xl border border-white/10 bg-[#111] shadow-2xl max-h-[85vh] overflow-hidden" x-transition x-on:click.stop>
                    <div class="flex items-start justify-between gap-4 border-b border-white/10 px-5 py-4">
                        <div class="min-w-0">
                            <div class="text-lg font-semibold truncate">Edit Product<span x-show="editForm.name" class="text-white/60"> — <span x-text="editForm.name"></span></span></div>
                            <div class="mt-1 text-sm text-white/60">Update product details and size/price options.</div>
                        </div>
                        <button
                            type="button"
                            class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 hover:bg-white/10"
                            x-on:click="closeEdit()"
                            aria-label="Close"
                            title="Close"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="px-5 py-4 overflow-y-auto overflow-x-hidden">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="space-y-4">
                            <div>
                                <label class="text-xs text-white/60">Product Name</label>
                                <input type="text" class="mt-2 h-11 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" x-model="editForm.name" />
                                <div class="mt-2 text-xs text-rose-200" x-text="fieldError('name')"></div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="text-xs text-white/60">Category</label>
                                    <select class="mt-2 h-11 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 text-white focus:outline-none focus:ring-2 focus:ring-white/20" x-model="editForm.category" x-bind:disabled="(editForm.new_category || '').trim().length > 0" x-on:change="if ((editForm.category || '').trim().length > 0) { editForm.new_category = '' }">
                                        <option value="">Select category</option>
                                        <template x-for="cat in editCategories" :key="cat">
                                            <option :value="cat" x-text="cat"></option>
                                        </template>
                                    </select>
                                    <div class="mt-2 text-xs text-white/40" x-show="(editForm.new_category || '').trim().length === 0">Or add a new category.</div>
                                </div>

                                <div>
                                    <label class="text-xs text-white/60">Add Category (optional)</label>
                                    <input type="text" placeholder="Type new category name" class="mt-2 h-11 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" x-model="editForm.new_category" x-on:input="if ((editForm.new_category || '').trim().length > 0) { editForm.category = '' }" />
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-rose-200" x-text="fieldError('category')"></div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="text-xs text-white/60">Replace Image</label>
                                    <input type="file" accept="image/*" class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white file:mr-4 file:rounded-xl file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-white/15" x-ref="imageInput" x-on:change="onImageChange($event)" />
                                    <div class="mt-2 text-xs text-white/40">Leave empty to keep current image.</div>
                                    <div class="mt-2 text-xs text-rose-200" x-text="fieldError('image')"></div>
                                </div>

                                <div>
                                    <label class="text-xs text-white/60">Current Image</label>
                                    <div class="mt-2 flex items-center gap-4">
                                        <div class="h-16 w-16 overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                                            <img :src="editImagePreviewSrc()" :alt="editForm.name || 'Product image'" class="h-full w-full object-contain" loading="lazy" />
                                        </div>
                                        <div class="min-w-0 text-xs text-white/50 truncate" x-text="editForm.image || '—'"></div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="inline-flex items-center gap-2 text-sm text-white/70">
                                    <input type="checkbox" class="rounded border-white/10 bg-[#1b1b1b] text-white focus:ring-white/20" x-model="editForm.is_active" />
                                    Active (visible in POS)
                                </label>
                            </div>
                            </div>

                            <div>
                            <div class="flex items-center justify-between gap-3">
                                <label class="text-xs text-white/60">Sizes & Prices</label>
                                <button type="button" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-white/80 hover:bg-white/10" x-on:click="addSizeRow()">
                                    Add Size
                                </button>
                            </div>

                            <div class="mt-2 space-y-3">
                                <template x-for="(row, idx) in editForm.sizes" :key="idx">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,160px)_auto] sm:items-center">
                                        <div class="min-w-0">
                                            <input type="text" placeholder="OZ label (e.g. 12oz)" class="h-11 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" x-model="row.size" />
                                        </div>
                                        <div class="min-w-0">
                                            <input type="text" placeholder="Price (e.g. 85.00)" inputmode="decimal" class="h-11 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" x-model="row.price" />
                                        </div>
                                        <div class="flex justify-end sm:justify-start">
                                            <button type="button" class="inline-flex h-11 items-center justify-center rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 text-sm font-semibold text-rose-200 hover:bg-rose-500/20" x-on:click="removeSizeRow(idx)" x-bind:disabled="editForm.sizes.length === 1" x-bind:class="editForm.sizes.length === 1 ? 'opacity-50 cursor-not-allowed' : ''">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="mt-2 text-xs text-rose-200" x-text="fieldError('sizes')"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-white/10 px-5 py-4">
                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 hover:bg-white/10" x-on:click="closeEdit()">
                            Cancel
                        </button>
                        <button type="button" class="rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95 active:opacity-90" x-on:click="saveEdit()" x-bind:disabled="editSaving" x-bind:class="editSaving ? 'opacity-50 cursor-not-allowed' : ''">
                            <span x-show="!editSaving">Save Changes</span>
                            <span x-show="editSaving">Saving...</span>
                        </button>
                    </div>

                    <div class="px-5 pb-5">
                        <div class="mt-4 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200" x-show="editError">
                            <div class="font-semibold">Action failed</div>
                            <div class="mt-1" x-text="editError"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.__assetBaseUrl = @js(rtrim(asset(''), '/') . '/');

        function adminProductsIndex(groups) {
            return {
                searchQuery: '',
                groups,
                activeTab: 'all',
                editModalOpen: false,
                editSaving: false,
                editError: '',
                editErrors: {},
                editCategories: [],
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

                        const data = await res.json();
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
                        fd.append('_token', @js(csrf_token()));
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
                            const data = await res.json();
                            this.editErrors = data?.errors || {};
                            this.editSaving = false;
                            return;
                        }

                        if (!res.ok) {
                            this.editError = 'Failed to save changes.';
                            this.editSaving = false;
                            return;
                        }

                        const data = await res.json();
                        const group = data?.group;
                        const oldKey = data?.oldKey || this.editForm.key;

                        if (group) {
                            this.upsertGroup(oldKey, group);
                        }

                        this.editSaving = false;
                        this.closeEdit();
                    } catch (err) {
                        this.editError = 'Failed to save changes.';
                        this.editSaving = false;
                    }
                },
            }
        }
    </script>
@endsection
