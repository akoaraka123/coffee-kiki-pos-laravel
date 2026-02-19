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

            <div class="relative flex min-h-screen" @yield('x-data')>
                <aside class="hidden w-72 shrink-0 border-r border-white/10 bg-gradient-to-b from-[#1b1b1b] to-[#111] lg:flex lg:flex-col">
                    <div class="p-6">
                        <div class="flex items-center gap-3">
                            <div class="grid h-11 w-11 place-items-center rounded-2xl border border-white/10 bg-white/10 shadow-sm">
                                <span class="text-xl leading-none">☕</span>
                            </div>
                            <div>
                                <div class="text-base font-semibold leading-tight">Khopi Kiki</div>
                                <div class="text-xs text-white/50">POS</div>
                            </div>
                        </div>

                        <div class="mt-6 space-y-1">
                            @yield('pos_sidebar')
                        </div>
                    </div>

                    <div class="mt-auto p-6">
                        <a href="{{ route('orders.index') }}" class="flex w-full items-center justify-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-white/80 shadow-sm hover:bg-white/10">
                            Order History
                        </a>
                    </div>
                </aside>

                <div class="flex min-w-0 flex-1 flex-col">
                    <header class="sticky top-0 z-10 border-b border-white/10 bg-[#111]/70 backdrop-blur">
                        <div class="mx-auto flex max-w-[1400px] items-center justify-between gap-4 px-4 py-4 sm:px-6">
                            <div class="min-w-0">
                                <h1 class="truncate text-lg font-semibold tracking-tight">@yield('title')</h1>
                                <p class="mt-0.5 text-xs text-white/50">Point of sale</p>
                            </div>

                            <div class="flex items-center gap-3">
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

                    <main class="mx-auto w-full max-w-[1400px] flex-1 px-4 py-6 sm:px-6">
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
