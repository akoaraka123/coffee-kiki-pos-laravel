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

            <div class="relative flex min-h-screen">
                <aside class="hidden w-72 shrink-0 border-r border-white/10 bg-gradient-to-b from-[#1b1b1b] to-[#111] lg:flex lg:flex-col">
                    <div class="p-6">
                        <div class="flex items-center gap-3">
                            <div class="grid h-11 w-11 place-items-center rounded-2xl border border-white/10 bg-white/10 shadow-sm">
                                <span class="text-xl leading-none">☕</span>
                            </div>
                            <div>
                                <div class="text-base font-semibold leading-tight">Khopi Kiki</div>
                                <div class="text-xs text-white/50">POS Dashboard</div>
                            </div>
                        </div>

                        <div class="mt-6 space-y-1">
                            @php
                                $isAdmin = auth()->check() && auth()->user()->role === 'admin';
                            @endphp

                            <a href="{{ $isAdmin ? route('admin.dashboard') : route('staff.dashboard') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.dashboard') || request()->routeIs('staff.dashboard') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                                <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">D</span>
                                <span class="font-medium">Dashboard</span>
                            </a>

                            <a href="{{ $isAdmin ? route('admin.orders.index') : route('pos') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('pos') || request()->routeIs('orders.create') || request()->routeIs('orders.store') || request()->routeIs('admin.orders.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                                <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">O</span>
                                <span class="font-medium">Orders</span>
                            </a>

                            <a href="{{ $isAdmin ? route('admin.products.index') : '#' }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.products.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                                <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">P</span>
                                <span class="font-medium">Products</span>
                            </a>

                            <a href="#" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm text-white/70 hover:bg-white/5 hover:text-white">
                                <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">R</span>
                                <span class="font-medium">Reports</span>
                            </a>

                            @if ($isAdmin)
                                <a href="{{ route('admin.users.index') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm {{ request()->routeIs('admin.users.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                                    <span class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-white/80 group-hover:bg-white/10">U</span>
                                    <span class="font-medium">Manage Users</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="mt-auto p-6">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center justify-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-white/80 shadow-sm hover:bg-white/10">
                                Logout
                            </button>
                        </form>
                    </div>
                </aside>

                <div class="flex min-w-0 flex-1 flex-col">
                    <header class="sticky top-0 z-10 border-b border-white/10 bg-[#111]/70 backdrop-blur">
                        <div class="mx-auto flex max-w-[1400px] items-center justify-between gap-4 px-4 py-4 sm:px-6">
                            <div class="min-w-0">
                                <h1 class="truncate text-lg font-semibold tracking-tight">@yield('title')</h1>
                                <p class="mt-0.5 text-xs text-white/50">Welcome back.</p>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="hidden text-right sm:block">
                                    <div class="text-sm font-medium">{{ Auth::user()->name }}</div>
                                    <div class="text-xs text-white/50">{{ Auth::user()->email }}</div>
                                </div>
                                <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/80">
                                    {{ Auth::user()->role === 'admin' ? 'Admin' : 'Staff' }}
                                </span>
                            </div>
                        </div>
                    </header>

                    <main class="mx-auto w-full max-w-[1400px] flex-1 px-4 py-6 sm:px-6">
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
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
