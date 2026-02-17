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
    <body class="min-h-screen bg-[#1c1c1c] font-sans text-white">
        <div class="relative min-h-screen">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -top-28 -left-28 h-[360px] w-[360px] rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute -bottom-28 -right-28 h-[420px] w-[420px] rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute top-1/2 -translate-y-1/2 -left-44 h-[560px] w-[560px] rounded-full bg-white/5 blur-3xl"></div>
            </div>

            <div class="relative mx-auto w-full max-w-6xl px-4 py-8">
                <div class="rounded-[28px] border border-white/10 bg-[#2a2a2a] shadow-2xl overflow-hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr]">
                        @hasSection('sidebar')
                            <aside class="bg-[#232323] border-b border-white/10 lg:border-b-0 lg:border-r border-white/10">
                                <div class="p-6">
                                    <div class="flex items-center gap-3">
                                        <div class="grid h-10 w-10 place-items-center rounded-2xl border border-white/10 bg-white/10">
                                            <span class="text-lg leading-none">☕</span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold">{{ config('app.name', 'Laravel') }}</div>
                                            <div class="text-xs text-white/50">{{ Auth::user()->role ?? '' }}</div>
                                        </div>
                                    </div>

                                    <div class="mt-6 space-y-2">
                                        @yield('sidebar')
                                    </div>
                                </div>
                            </aside>
                        @endif

                        <main class="p-6 sm:p-8">
                            @if (session('status'))
                                <div class="mb-6 rounded-2xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                                    {{ session('status') }}
                                </div>
                            @endif

                            @yield('content')
                        </main>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
