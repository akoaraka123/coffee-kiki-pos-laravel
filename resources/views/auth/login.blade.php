<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#1c1c1c] font-sans text-white">
<div class="relative min-h-screen flex items-center justify-center px-4 py-10">

    <!-- background blobs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-28 -left-28 h-[360px] w-[360px] rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-28 -right-28 h-[420px] w-[420px] rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute top-1/2 -translate-y-1/2 -left-44 h-[560px] w-[560px] rounded-full bg-white/5 blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-5xl overflow-hidden rounded-[30px] border border-white/10 bg-[#2a2a2a] shadow-2xl">
        <div class="grid grid-cols-1 lg:grid-cols-2">

            <!-- LEFT SIDE -->
            <div class="relative flex items-center justify-center bg-[#efe9df] px-8 py-14 text-[#1c1c1c] sm:px-12">

                <!-- curve -->
                <div class="pointer-events-none absolute inset-y-0 right-[-90px] z-0 hidden w-[190px] lg:block">
                    <svg viewBox="0 0 180 700" class="h-full w-full" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0,0 C130,70 70,170 150,270 C70,365 155,515 0,700 L0,0 Z" fill="#efe9df"/>
                    </svg>
                </div>

                <div class="relative z-10 w-full max-w-sm text-center">
                    <!-- Coffee doodle image (LEFT) -->
                    <div class="flex justify-center">
                        <img
                            src="{{ asset('images/coffee-doodle.png') }}"
                            alt="Coffee Illustration"
                            class="w-44 sm:w-56 h-auto object-contain"
                            draggable="false"
                        >
                    </div>

                    <div class="mt-6">
                        <h2 class="text-3xl font-extrabold tracking-tight">find coffee</h2>
                        <p class="mt-2 text-sm text-black/60">find the best coffee to accompany your days</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="relative bg-[#232323] px-8 py-12 sm:px-12">
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute -top-20 -right-28 h-[280px] w-[280px] rounded-full bg-white/5 blur-3xl"></div>
                    <div class="absolute -bottom-28 left-1/2 h-[340px] w-[340px] -translate-x-1/2 rounded-full bg-black/35 blur-3xl"></div>
                </div>

                <div class="relative mx-auto max-w-md">

                    <!-- LOGO (PALAKI) -->
                    <div class="flex justify-center mb-6">
                        <img
                            src="{{ asset('images/khopi-kiki-logo.png') }}"
                            alt="KHOPI KIKI Logo"
                            class="h-32 w-32 sm:h-36 sm:w-36 object-contain drop-shadow-2xl"
                            draggable="false"
                        >
                    </div>

                    <div class="mt-2 text-center">
                        <h1 class="text-2xl font-semibold leading-snug sm:text-3xl">
                            <span class="text-white">Welcome Back</span>,
                        </h1>
                    </div>

                    @if (session('status'))
                        <div class="mt-4 text-sm font-medium text-green-400">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mt-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200">
                            <p class="font-medium">Login failed</p>
                            <p class="mt-1">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <form class="mt-8 space-y-5" method="POST" action="{{ route('login') }}">
                        @csrf

                        <div>
                            <label for="email" class="text-xs text-white/60">Email Address</label>
                            <input
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                type="email"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="you@example.com"
                                class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                            />
                        </div>

                        <div>
                            <label for="password" class="text-xs text-white/60">Password</label>
                            <div class="relative mt-2">
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 pr-12 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-sm text-white/40 select-none">👁</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs text-white/60">
                            <label class="inline-flex items-center gap-2">
                                <input id="remember_me" name="remember" type="checkbox" class="rounded border-white/20 bg-[#1b1b1b] text-white focus:ring-white/20">
                                Remember me
                            </label>

                            @if (Route::has('password.request'))
                                <a class="underline decoration-white/20 hover:text-white" href="{{ route('password.request') }}">
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <button type="submit" class="w-full rounded-full bg-[#e9e2d6] py-3 font-semibold text-[#1c1c1c] shadow-lg hover:opacity-95 active:opacity-90">
                            Sign in
                        </button>

                        <div class="flex items-center gap-3 text-xs text-white/35">
                            <div class="h-px flex-1 bg-white/10"></div>
                            <span>or</span>
                            <div class="h-px flex-1 bg-white/10"></div>
                        </div>

                        <button type="button" class="flex w-full items-center justify-center gap-3 rounded-full border border-white/10 bg-white/5 py-3 font-medium hover:bg-white/10">
                            <span class="text-lg">G</span>
                            <span>Sign in with Google</span>
                        </button>

                        @php
                            $showRegister = false;
                            try {
                                $showRegister = !\App\Models\User::query()->exists();
                            } catch (\Throwable $e) {
                                $showRegister = false;
                            }
                        @endphp

                        @if ($showRegister && Route::has('register'))
                            <p class="mt-3 text-center text-xs text-white/50">
                                New Coffeelover?
                                <a class="text-white underline decoration-white/20 hover:decoration-white/40" href="{{ route('register') }}">
                                    Create Account
                                </a>
                            </p>
                        @endif
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html><!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#1c1c1c] font-sans text-white">
<div class="relative min-h-screen flex items-center justify-center px-4 py-10">

    <!-- background blobs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-28 -left-28 h-[360px] w-[360px] rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-28 -right-28 h-[420px] w-[420px] rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute top-1/2 -translate-y-1/2 -left-44 h-[560px] w-[560px] rounded-full bg-white/5 blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-5xl overflow-hidden rounded-[30px] border border-white/10 bg-[#2a2a2a] shadow-2xl">
        <div class="grid grid-cols-1 lg:grid-cols-2">

            <!-- LEFT SIDE -->
            <div class="relative flex items-center justify-center bg-[#efe9df] px-8 py-14 text-[#1c1c1c] sm:px-12">

                <!-- curve -->
                <div class="pointer-events-none absolute inset-y-0 right-[-90px] z-0 hidden w-[190px] lg:block">
                    <svg viewBox="0 0 180 700" class="h-full w-full" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0,0 C130,70 70,170 150,270 C70,365 155,515 0,700 L0,0 Z" fill="#efe9df"/>
                    </svg>
                </div>

                <div class="relative z-10 w-full max-w-sm text-center">
                    <!-- Coffee doodle image (LEFT) -->
                    <div class="flex justify-center">
                        <img
                            src="{{ asset('images/coffee-doodle.png') }}"
                            alt="Coffee Illustration"
                            class="w-44 sm:w-56 h-auto object-contain"
                            draggable="false"
                        >
                    </div>

                    <div class="mt-6">
                        <h2 class="text-3xl font-extrabold tracking-tight">find coffee</h2>
                        <p class="mt-2 text-sm text-black/60">find the best coffee to accompany your days</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="relative bg-[#232323] px-8 py-12 sm:px-12">
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute -top-20 -right-28 h-[280px] w-[280px] rounded-full bg-white/5 blur-3xl"></div>
                    <div class="absolute -bottom-28 left-1/2 h-[340px] w-[340px] -translate-x-1/2 rounded-full bg-black/35 blur-3xl"></div>
                </div>

                <div class="relative mx-auto max-w-md">

                    <!-- LOGO (PALAKI) -->
                    <div class="flex justify-center mb-6">
                        <img
                            src="{{ asset('images/khopi-kiki-logo.png') }}"
                            alt="KHOPI KIKI Logo"
                            class="h-32 w-32 sm:h-36 sm:w-36 object-contain drop-shadow-2xl"
                            draggable="false"
                        >
                    </div>

                    <div class="mt-2 text-center">
                        <h1 class="text-2xl font-semibold leading-snug sm:text-3xl">
                            <span class="text-white">Welcome Back</span>,
                        </h1>
                    </div>

                    @if (session('status'))
                        <div class="mt-4 text-sm font-medium text-green-400">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mt-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200">
                            <p class="font-medium">Login failed</p>
                            <p class="mt-1">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <form class="mt-8 space-y-5" method="POST" action="{{ route('login') }}">
                        @csrf

                        <div>
                            <label for="email" class="text-xs text-white/60">Email Address</label>
                            <input
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                type="email"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="you@example.com"
                                class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                            />
                        </div>

                        <div>
                            <label for="password" class="text-xs text-white/60">Password</label>
                            <div class="relative mt-2">
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 pr-12 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-sm text-white/40 select-none">👁</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs text-white/60">
                            <label class="inline-flex items-center gap-2">
                                <input id="remember_me" name="remember" type="checkbox" class="rounded border-white/20 bg-[#1b1b1b] text-white focus:ring-white/20">
                                Remember me
                            </label>

                            @if (Route::has('password.request'))
                                <a class="underline decoration-white/20 hover:text-white" href="{{ route('password.request') }}">
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <button type="submit" class="w-full rounded-full bg-[#e9e2d6] py-3 font-semibold text-[#1c1c1c] shadow-lg hover:opacity-95 active:opacity-90">
                            Sign in
                        </button>

                        <div class="flex items-center gap-3 text-xs text-white/35">
                            <div class="h-px flex-1 bg-white/10"></div>
                            <span>or</span>
                            <div class="h-px flex-1 bg-white/10"></div>
                        </div>

                        <button type="button" class="flex w-full items-center justify-center gap-3 rounded-full border border-white/10 bg-white/5 py-3 font-medium hover:bg-white/10">
                            <span class="text-lg">G</span>
                            <span>Sign in with Google</span>
                        </button>

                        @php
                            $showRegister = false;
                            try {
                                $showRegister = !\App\Models\User::query()->exists();
                            } catch (\Throwable $e) {
                                $showRegister = false;
                            }
                        @endphp

                        @if ($showRegister && Route::has('register'))
                            <p class="mt-3 text-center text-xs text-white/50">
                                New Coffeelover?
                                <a class="text-white underline decoration-white/20 hover:decoration-white/40" href="{{ route('register') }}">
                                    Create Account
                                </a>
                            </p>
                        @endif
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>