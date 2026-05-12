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
                            class="h-36 w-36 min-h-[9rem] min-w-[9rem] sm:h-40 sm:w-40 object-contain drop-shadow-2xl"
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
                                <button
                                    type="button"
                                    id="password-toggle"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-white/45 hover:bg-white/10 hover:text-white/85 focus:outline-none focus:ring-2 focus:ring-white/25"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                >
                                    <span data-password-toggle-icon="show" class="block">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </span>
                                    <span data-password-toggle-icon="hide" class="hidden">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 1 1 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs text-white/60">
                            <label class="inline-flex items-center gap-2">
                                <input id="remember_me" name="remember" type="checkbox" class="rounded border-white/20 bg-[#1b1b1b] text-white focus:ring-white/20">
                                Remember me
                            </label>

                            <button
                                type="button"
                                id="forgot-password-open"
                                class="text-left underline decoration-white/20 hover:text-white"
                            >
                                Forgot password?
                            </button>
                        </div>

                        <button type="submit" class="w-full rounded-full bg-[#e9e2d6] py-3 font-semibold text-[#1c1c1c] shadow-lg hover:opacity-95 active:opacity-90">
                            Sign in
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

<!-- Forgot password modal -->
<div
    id="forgot-password-modal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/65 px-4 py-8"
    role="dialog"
    aria-modal="true"
    aria-labelledby="forgot-modal-title"
>
    <div class="absolute inset-0" data-forgot-modal-backdrop></div>
    <div class="relative z-10 w-full max-w-md rounded-2xl border border-white/10 bg-[#232323] p-6 shadow-2xl sm:p-8">
        <h2 id="forgot-modal-title" class="text-lg font-semibold text-white sm:text-xl">Forgot Password</h2>
        <p class="mt-3 text-sm leading-relaxed text-white/55">
            Enter your registered email address. The admin will be notified to help reset your password.
        </p>

        <div id="forgot-modal-error" class="mt-4 hidden rounded-xl border border-rose-500/35 bg-rose-500/10 px-3 py-2 text-sm text-rose-100"></div>

        <form id="forgot-password-form" class="mt-5 space-y-4" novalidate>
            <div>
                <label for="forgot-email" class="text-xs text-white/60">Email Address</label>
                <input
                    id="forgot-email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    placeholder="your@example.com"
                    class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                />
            </div>
            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    id="forgot-modal-cancel"
                    class="w-full rounded-full border border-white/15 bg-white/5 py-3 text-sm font-semibold text-white/85 hover:bg-white/10 sm:w-auto sm:min-w-[7rem]"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    id="forgot-modal-submit"
                    class="w-full rounded-full bg-[#e9e2d6] py-3 text-sm font-semibold text-[#1c1c1c] shadow-lg hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto sm:min-w-[10rem]"
                >
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast -->
<div
    id="login-toast"
    class="pointer-events-none fixed bottom-6 left-1/2 z-[110] hidden max-w-[min(calc(100vw-2rem),24rem)] -translate-x-1/2 rounded-xl border border-emerald-500/30 bg-emerald-950/95 px-4 py-3 text-center text-sm font-medium text-emerald-100 shadow-lg"
    role="status"
></div>

<script>
(function () {
    var modal = document.getElementById('forgot-password-modal');
    var openBtn = document.getElementById('forgot-password-open');
    var form = document.getElementById('forgot-password-form');
    var emailInput = document.getElementById('forgot-email');
    var errBox = document.getElementById('forgot-modal-error');
    var cancelBtn = document.getElementById('forgot-modal-cancel');
    var submitBtn = document.getElementById('forgot-modal-submit');
    var backdrop = modal ? modal.querySelector('[data-forgot-modal-backdrop]') : null;
    var toast = document.getElementById('login-toast');
    var storeUrl = @json(route('forgot-password-request.store'));
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');

    function csrfToken() {
        return csrfMeta ? csrfMeta.getAttribute('content') : '';
    }

    function showModalError(msg) {
        if (!errBox) return;
        errBox.textContent = msg;
        errBox.classList.remove('hidden');
    }

    function clearModalError() {
        if (!errBox) return;
        errBox.textContent = '';
        errBox.classList.add('hidden');
    }

    function openModal() {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        clearModalError();
        if (emailInput) {
            emailInput.value = '';
            emailInput.focus();
        }
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        clearModalError();
        if (submitBtn) submitBtn.disabled = false;
    }

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('hidden');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(function () {
            toast.classList.add('hidden');
        }, 4500);
    }

    function validateClientEmail(val) {
        var v = String(val || '').trim();
        if (!v) return 'Enter your email.';
        // simple format check (server is authoritative)
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'Enter a valid email.';
        return '';
    }

    if (openBtn) openBtn.addEventListener('click', function (e) {
        e.preventDefault();
        openModal();
    });
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) closeModal();
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearModalError();
            var clientErr = validateClientEmail(emailInput && emailInput.value);
            if (clientErr) {
                showModalError(clientErr);
                return;
            }
            submitBtn.disabled = true;
            fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ email: String(emailInput.value).trim() }),
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, status: res.status, data: data };
                    });
                })
                .then(function (r) {
                    if (r.ok) {
                        closeModal();
                        showToast(r.data.message || 'Password reset request sent to admin.');
                        return;
                    }
                    if (r.status === 422 && r.data) {
                        var em = r.data.errors && r.data.errors.email && r.data.errors.email[0];
                        showModalError(em || r.data.message || 'Check your email and try again.');
                        return;
                    }
                    showModalError((r.data && r.data.message) || 'Something went wrong. Please try again.');
                })
                .catch(function () {
                    showModalError('Network error. Please try again.');
                })
                .finally(function () {
                    submitBtn.disabled = false;
                });
        });
    }
})();
</script>
<script>
(function () {
    var btn = document.getElementById('password-toggle');
    var input = document.getElementById('password');
    if (!btn || !input) return;
    var iconShow = btn.querySelector('[data-password-toggle-icon="show"]');
    var iconHide = btn.querySelector('[data-password-toggle-icon="hide"]');
    btn.addEventListener('click', function () {
        var visible = input.type === 'text';
        if (visible) {
            input.type = 'password';
            btn.setAttribute('aria-pressed', 'false');
            btn.setAttribute('aria-label', 'Show password');
            iconShow.classList.remove('hidden');
            iconHide.classList.add('hidden');
        } else {
            input.type = 'text';
            btn.setAttribute('aria-pressed', 'true');
            btn.setAttribute('aria-label', 'Hide password');
            iconShow.classList.add('hidden');
            iconHide.classList.remove('hidden');
        }
    });
})();
</script>
</body>
</html>