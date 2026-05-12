<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Laravel'))</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#111] font-sans text-white">
        <div class="relative min-h-screen">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -top-28 -left-28 h-[360px] w-[360px] rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute -bottom-28 -right-28 h-[420px] w-[420px] rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute top-1/2 -translate-y-1/2 -left-44 h-[560px] w-[560px] rounded-full bg-white/5 blur-3xl"></div>
            </div>

            <div
                class="relative flex min-h-screen flex-col"
                x-data="dashboardShell()"
                x-init="init()"
                @keydown.escape.window="if (!isDesktop) sidebarOpen = false"
            >
                <header class="sticky top-0 z-50 border-b border-white/10 bg-[#111]/70 backdrop-blur">
                    <div class="relative flex w-full items-center justify-between gap-4 px-4 py-4 sm:px-6">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 hover:bg-white/10" x-on:click="toggleSidebar()">
                                <span class="text-lg leading-none">☰</span>
                            </button>
                            <div class="min-w-0">
                                <h1 class="truncate text-lg font-semibold tracking-tight">@yield('title')</h1>
                                <p class="mt-0.5 text-xs text-white/50">Welcome back.</p>
                            </div>
                        </div>

                        <div class="flex flex-1 items-center justify-end gap-3">
                            @if (auth()->check() && auth()->user()->role === 'admin')
                                <div
                                    class="relative"
                                    x-data="passwordResetNotifications({
                                        fetchUrl: @js(route('admin.password-reset-requests.index', ['status' => 'pending'])),
                                        indexPageUrl: @js(route('admin.password-reset-requests.index')),
                                        resolveUrlTemplate: @js(preg_replace('#/\d+/resolve$#', '/__PRR__/resolve', route('admin.password-reset-requests.resolve', ['passwordResetRequest' => 1]))),
                                    })"
                                    x-init="load()"
                                    @click.outside="open = false"
                                >
                                    <button
                                        type="button"
                                        class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white/80 hover:bg-white/10"
                                        x-on:click="toggleOpen()"
                                        aria-label="Password reset notifications"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                        </svg>
                                        <span
                                            x-show="pendingCount > 0"
                                            x-cloak
                                            class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold leading-none text-white shadow"
                                            x-text="pendingCount > 99 ? '99+' : pendingCount"
                                        ></span>
                                    </button>

                                    <div
                                        x-show="open"
                                        x-cloak
                                        x-transition.opacity.duration.150ms
                                        class="absolute right-0 top-full z-[60] mt-2 w-[min(calc(100vw-2rem),22rem)] max-h-[min(70vh,24rem)] overflow-y-auto rounded-xl border border-white/10 bg-[#1b1b1b] py-2 shadow-2xl"
                                    >
                                        <div class="border-b border-white/10 px-3 pb-2">
                                            <p class="text-xs font-semibold text-white/90">Pending password resets</p>
                                            <p class="mt-0.5 text-[11px] text-white/45">Help staff reset their password, then mark resolved.</p>
                                        </div>
                                        <template x-if="loading">
                                            <div class="px-3 py-6 text-center text-xs text-white/50">Loading…</div>
                                        </template>
                                        <template x-if="!loading && items.length === 0">
                                            <div class="px-3 py-6 text-center text-xs text-white/50">No pending requests.</div>
                                        </template>
                                        <ul class="divide-y divide-white/10">
                                            <template x-for="item in items" :key="item.id">
                                                <li class="px-3 py-3">
                                                    <div class="text-sm font-medium text-white" x-text="item.name"></div>
                                                    <div class="mt-0.5 text-xs text-white/55" x-text="item.email"></div>
                                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-white/45">
                                                        <span class="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 capitalize text-white/75" x-text="item.role"></span>
                                                        <span x-text="formatDate(item.requested_at)"></span>
                                                        <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-amber-200/90">Pending</span>
                                                    </div>
                                                    <div class="mt-2 flex gap-2">
                                                        <a
                                                            :href="indexPageUrl + '#request-' + item.id"
                                                            class="inline-flex flex-1 items-center justify-center rounded-lg border border-white/10 bg-white/5 px-2 py-1.5 text-xs font-semibold text-white/85 hover:bg-white/10"
                                                        >View</a>
                                                        <button
                                                            type="button"
                                                            class="inline-flex flex-1 items-center justify-center rounded-lg bg-[#efe9df] px-2 py-1.5 text-xs font-semibold text-[#1c1c1c] hover:opacity-95 disabled:opacity-50"
                                                            :disabled="resolvingId === item.id"
                                                            x-on:click="resolve(item.id)"
                                                        >
                                                            <span x-show="resolvingId !== item.id">Mark as Resolved</span>
                                                            <span x-show="resolvingId === item.id">…</span>
                                                        </button>
                                                    </div>
                                                </li>
                                            </template>
                                        </ul>
                                        <div class="border-t border-white/10 px-3 pt-2">
                                            <a href="{{ route('admin.password-reset-requests.index') }}" class="block text-center text-xs font-medium text-white/70 underline decoration-white/25 hover:text-white">Open full page</a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="hidden text-right sm:block">
                                <div class="text-sm font-medium">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-white/50">{{ Auth::user()->email }}</div>
                            </div>
                            <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/80">
                                {{ Auth::user()->role === 'admin' ? 'Admin' : 'Staff' }}
                            </span>
                            <div class="h-9 w-9 rounded-full bg-gradient-to-br from-amber-500 to-amber-700 flex items-center justify-center shadow-lg">
                                <span class="text-sm font-bold text-white">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="relative w-full flex-1">
                    <template x-if="sidebarOpen && !isDesktop">
                        <div class="fixed inset-0 z-20 bg-black/60 lg:hidden" x-transition.opacity x-on:click="sidebarOpen = false"></div>
                    </template>

                    <aside
                        class="fixed bottom-0 left-0 top-20 z-30 w-72 shrink-0 border-r border-white/10 bg-gradient-to-b from-[#1b1b1b] to-[#111] transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:transition-[width] lg:duration-200 lg:ease-in-out"
                        :class="(sidebarOpen || isDesktop) ? 'translate-x-0' : '-translate-x-full'"
                        :style="isDesktop ? (sidebarCollapsed ? 'width: 72px;' : 'width: 240px;') : ''"
                        x-on:mouseenter="if (isDesktop && sidebarCollapsed) { sidebarCollapsed = false; hoverOpened = true }"
                        x-on:mouseleave="if (isDesktop && hoverOpened) { sidebarCollapsed = true; hoverOpened = false }"
                    >
                        <div class="flex h-full w-full flex-col">
                            <div class="p-6" :class="isDesktop && sidebarCollapsed ? 'px-3' : ''">
                                <div class="flex items-center gap-3" :class="isDesktop && sidebarCollapsed ? 'justify-center' : ''">
                                    <img src="{{ asset('images/khopi-kiki-logo.png') }}" alt="Khopi Kiki" class="h-10 w-auto" />
                                    <div x-show="!(isDesktop && sidebarCollapsed)">
                                        <div class="text-base font-semibold leading-tight">Khopi Kiki</div>
                                        <div class="text-xs text-white/50">POS Dashboard</div>
                                    </div>
                                </div>

                                <div class="mt-6 space-y-1">
                                    @php
                                        $isAdmin = auth()->check() && auth()->user()->role === 'admin';
                                    @endphp

                                    <a href="{{ $isAdmin ? route('admin.dashboard') : route('staff.dashboard') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.dashboard') || request()->routeIs('staff.dashboard') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}" :title="(isDesktop && sidebarCollapsed) ? 'Dashboard' : ''">
                                        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">D</span>
                                        <span class="font-medium" x-show="!(isDesktop && sidebarCollapsed)">Dashboard</span>
                                    </a>

                                    <a href="{{ $isAdmin ? route('admin.orders.index') : route('pos') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('pos') || request()->routeIs('orders.create') || request()->routeIs('orders.store') || request()->routeIs('admin.orders.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}" :title="(isDesktop && sidebarCollapsed) ? 'Orders' : ''">
                                        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">O</span>
                                        <span class="font-medium" x-show="!(isDesktop && sidebarCollapsed)">Orders</span>
                                    </a>

                                    <a href="{{ $isAdmin ? route('admin.products.index') : route('staff.money-inventory.index') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.products.*') || request()->routeIs('staff.money-inventory.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}" :title="(isDesktop && sidebarCollapsed) ? '{{ $isAdmin ? 'Products' : 'Money Inventory' }}' : ''">
                                        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">{{ $isAdmin ? 'P' : 'M' }}</span>
                                        <span class="font-medium" x-show="!(isDesktop && sidebarCollapsed)">{{ $isAdmin ? 'Products' : 'Money Inventory' }}</span>
                                    </a>

                                    
                                    <a href="{{ $isAdmin ? route('admin.inventory.index') : route('staff.inventory.index') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.inventory.*') || request()->routeIs('staff.inventory.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}" :title="(isDesktop && sidebarCollapsed) ? 'Inventory' : ''">
                                        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">I</span>
                                        <span class="font-medium" x-show="!(isDesktop && sidebarCollapsed)">Inventory</span>
                                    </a>

                                    <a href="{{ route('profile.edit') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('profile.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}" :title="(isDesktop && sidebarCollapsed) ? 'Settings' : ''">
                                        <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">S</span>
                                        <span class="font-medium" x-show="!(isDesktop && sidebarCollapsed)">Settings</span>
                                    </a>

                                    @if ($isAdmin)
                                        <a href="{{ route('admin.users.index') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.users.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}" :title="(isDesktop && sidebarCollapsed) ? 'Manage Users' : ''">
                                            <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">U</span>
                                            <span class="font-medium" x-show="!(isDesktop && sidebarCollapsed)">Manage Users</span>
                                        </a>
                                        <a href="{{ route('admin.password-reset-requests.index') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.password-reset-requests.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}" :title="(isDesktop && sidebarCollapsed) ? 'Password resets' : ''">
                                            <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">R</span>
                                            <span class="font-medium" x-show="!(isDesktop && sidebarCollapsed)">Password Resets</span>
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-auto p-6" x-show="!(isDesktop && sidebarCollapsed)">
                                @if ($isAdmin || request()->routeIs('profile.edit'))
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center justify-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-white/80 shadow-sm hover:bg-white/10">
                                            Logout
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </aside>

                    <main
                        class="w-full px-4 py-6 transition-[padding-left] duration-200 ease-in-out sm:px-6"
                        :style="isDesktop ? (sidebarCollapsed ? 'padding-left: 72px;' : 'padding-left: 240px;') : ''"
                    >
                        <div class="mx-auto w-full max-w-[1400px]">
                            @if (session('status'))
                                <div class="mb-6 rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                                    {{ session('status') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="mb-6 rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200">
                                    <p class="font-medium">Action failed</p>
                                    <p class="mt-1">{{ $errors->first() }}</p>
                                </div>
                            @endif

                            @yield('content')
                        </div>
                    </main>
                </div>
            </div>

            <script>
                function dashboardShell() {
                    return {
                        isDesktop: window.innerWidth >= 1024,
                        sidebarOpen: window.innerWidth >= 1024,
                        sidebarCollapsed: false,
                        hoverOpened: false,
                        init() {
                            window.addEventListener('resize', () => {
                                this.isDesktop = window.innerWidth >= 1024;
                                if (this.isDesktop) {
                                    this.sidebarOpen = true;
                                } else {
                                    this.sidebarCollapsed = false;
                                    this.sidebarOpen = false;
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
                    }
                }

                function passwordResetNotifications(config) {
                    return {
                        open: false,
                        loading: false,
                        items: [],
                        pendingCount: 0,
                        resolvingId: null,
                        fetchUrl: config.fetchUrl,
                        indexPageUrl: config.indexPageUrl,
                        resolveUrlTemplate: config.resolveUrlTemplate,
                        toggleOpen() {
                            this.open = !this.open;
                            if (this.open) {
                                this.load();
                            }
                        },
                        formatDate(iso) {
                            if (!iso) return '';
                            try {
                                var d = new Date(iso);
                                return d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
                            } catch (e) {
                                return '';
                            }
                        },
                        async load() {
                            this.loading = true;
                            try {
                                var token = document.querySelector('meta[name="csrf-token"]');
                                var res = await fetch(this.fetchUrl, {
                                    headers: {
                                        Accept: 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                                    },
                                    credentials: 'same-origin',
                                });
                                var data = await res.json();
                                if (res.ok) {
                                    this.items = Array.isArray(data.data) ? data.data : [];
                                    this.pendingCount = typeof data.pending_count === 'number' ? data.pending_count : this.items.length;
                                }
                            } catch (e) {
                                this.items = [];
                            } finally {
                                this.loading = false;
                            }
                        },
                        async resolve(id) {
                            if (this.resolvingId) return;
                            this.resolvingId = id;
                            var token = document.querySelector('meta[name="csrf-token"]');
                            var url = this.resolveUrlTemplate.replace('__PRR__', String(id));
                            try {
                                var res = await fetch(url, {
                                    method: 'PATCH',
                                    headers: {
                                        Accept: 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                                    },
                                    credentials: 'same-origin',
                                    body: JSON.stringify({}),
                                });
                                var data = await res.json().catch(function () { return {}; });
                                if (res.ok) {
                                    this.items = this.items.filter(function (i) { return i.id !== id; });
                                    if (typeof data.pending_count === 'number') {
                                        this.pendingCount = data.pending_count;
                                    } else {
                                        this.pendingCount = Math.max(0, this.pendingCount - 1);
                                    }
                                }
                            } finally {
                                this.resolvingId = null;
                            }
                        },
                    };
                }
            </script>
        </div>
    </body>
</html>
