<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'POS')</title>

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

            <div class="relative flex min-h-screen flex-col" @yield('x-data') @keydown.escape.window="if (!isDesktop) sidebarOpen = false">
                <header class="sticky top-0 z-50 border-b border-white/10 bg-[#111]/70 backdrop-blur">
                    <div class="relative flex w-full items-center px-4 py-4 sm:px-6">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 hover:bg-white/10" x-on:click="toggleSidebar()">
                                <span class="text-lg leading-none">☰</span>
                            </button>

                            <img src="{{ asset('images/khopi-kiki-logo.png') }}" alt="Khopi Kiki" class="h-10 w-auto" />
                            <div class="truncate text-xl font-bold tracking-wide leading-tight">KHOPI-KIKI</div>
                        </div>

                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <div class="text-2xl font-bold tracking-widest">POINT OF SALE</div>
                        </div>

                        <div class="flex flex-1 items-center justify-end gap-3">
                            <div class="hidden text-right sm:block">
                                <div class="text-sm font-medium">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-white/50">{{ Auth::user()->email }}</div>
                            </div>
                            <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/80">
                                {{ Auth::user()->role === 'admin' ? 'Admin' : 'Staff' }}
                            </span>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center rounded-full bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-lg hover:opacity-95 active:opacity-90">
                                    Logout
                                </button>
                            </form>
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
                                <div class="space-y-1">
                                    @yield('pos_sidebar')
                                </div>
                            </div>

                            <div class="mt-auto p-6" x-show="!(isDesktop && sidebarCollapsed)">
                                <a href="{{ route('orders.index') }}" class="flex w-full items-center justify-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-white/80 shadow-sm hover:bg-white/10">
                                    Order History
                                </a>
                            </div>
                        </div>
                    </aside>

                    <main
                        class="w-full px-4 py-6 transition-[padding-left] duration-200 ease-in-out sm:px-6"
                        :style="isDesktop ? (sidebarCollapsed ? 'padding-left: 72px;' : 'padding-left: 240px;') : ''"
                    >
                        <div class="mx-auto w-full max-w-[1400px]">
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
        </div>
    </body>
</html>
