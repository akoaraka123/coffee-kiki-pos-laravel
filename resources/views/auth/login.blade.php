<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-[#1c1c1c] px-4 py-10">

        {{-- Background blobs (like the sample image) --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-24 -left-24 h-[340px] w-[340px] rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-24 -right-24 h-[380px] w-[380px] rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute top-1/2 -translate-y-1/2 -left-40 h-[520px] w-[520px] rounded-full bg-white/5 blur-3xl"></div>
        </div>

        {{-- Main Card --}}
        <div class="relative w-full max-w-5xl rounded-[28px] bg-[#2a2a2a] shadow-2xl border border-white/10 overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2">

                {{-- LEFT (cream) --}}
                <div class="relative bg-[#efe9df] text-[#1c1c1c] px-8 sm:px-12 py-12 flex items-center justify-center">

                    {{-- Wavy divider --}}
                    <div class="hidden lg:block absolute top-0 right-[-90px] h-full w-[190px] bg-[#efe9df]"
                        style="clip-path: path('M 0 0 C 150 80 60 180 170 280 C 60 380 170 520 0 650 L 0 0 Z');">
                    </div>

                    <div class="max-w-sm text-center space-y-6">
                        {{-- Doodle (same style) --}}
                        <div class="flex justify-center">
                            <svg class="w-56 h-56 sm:w-64 sm:h-64" viewBox="0 0 260 260" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M57 197C93 227 167 227 203 197" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M86 214C112 236 148 236 174 214" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <rect x="75" y="88" width="110" height="100" rx="18" stroke="#1c1c1c" stroke-width="6"/>
                                <path d="M185 110H199C214 110 224 122 224 138C224 154 214 166 199 166H185" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M100 74C100 58 116 54 116 38" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M130 74C130 58 146 54 146 38" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M160 74C160 58 176 54 176 38" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M106 145C116 135 144 135 154 145" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M112 160C126 176 134 176 148 160" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M60 120L52 114" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M64 148L52 148" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M64 176L54 182" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M202 120L210 114" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M200 148L212 148" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M200 176L210 182" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M86 108L78 100" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                                <path d="M174 108L182 100" stroke="#1c1c1c" stroke-width="6" stroke-linecap="round"/>
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-3xl font-extrabold tracking-tight">find coffee</h2>
                            <p class="mt-2 text-sm text-black/60">
                                find the best coffee to accompany your days
                            </p>
                        </div>
                    </div>
                </div>

                {{-- RIGHT (dark) --}}
                <div class="relative bg-[#232323] px-8 sm:px-12 py-12">

                    {{-- subtle background wave --}}
                    <div class="absolute inset-0 pointer-events-none">
                        <div class="absolute -top-16 -right-24 h-[260px] w-[260px] rounded-full bg-white/5 blur-2xl"></div>
                        <div class="absolute -bottom-24 left-1/2 -translate-x-1/2 h-[300px] w-[300px] rounded-full bg-black/30 blur-2xl"></div>
                    </div>

                    <div class="relative max-w-md mx-auto">
                        {{-- top icon --}}
                        <div class="flex justify-center">
                            <div class="h-14 w-14 rounded-2xl bg-white/10 border border-white/10 grid place-items-center">
                                {{-- simple logo substitute (you can replace with your own SVG) --}}
                                <span class="text-2xl">☕</span>
                            </div>
                        </div>

                        <div class="mt-6 text-center">
                            <h1 class="text-2xl sm:text-3xl font-semibold leading-snug">
                                <span class="text-white">Welcome Back</span>, <span class="text-white/70 font-normal">Please login to your account</span>
                            </h1>
                        </div>

                        <x-auth-session-status class="mt-4" :status="session('status')" />

                        @if ($errors->any())
                            <div class="mt-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200">
                                <p class="font-medium">Login failed</p>
                                <p class="mt-1">{{ $errors->first() }}</p>
                            </div>
                        @endif

                        <form class="mt-8 space-y-5" method="POST" action="{{ route('login') }}">
                            @csrf

                            <div>
                                <label class="text-xs text-white/60">Email Address</label>
                                <input
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    type="email"
                                    required
                                    autofocus
                                    autocomplete="email"
                                    class="mt-2 w-full rounded-2xl bg-[#1b1b1b] border border-white/10 px-4 py-3 text-white placeholder:text-white/30
                                           focus:outline-none focus:ring-2 focus:ring-white/20"
                                    placeholder="you@example.com"
                                />
                            </div>

                            <div>
                                <label class="text-xs text-white/60">Password</label>
                                <div class="relative mt-2">
                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        required
                                        autocomplete="current-password"
                                        class="w-full rounded-2xl bg-[#1b1b1b] border border-white/10 px-4 py-3 text-white placeholder:text-white/30
                                               focus:outline-none focus:ring-2 focus:ring-white/20 pr-12"
                                        placeholder="••••••••"
                                    />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 text-sm select-none">👁</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs text-white/60">
                                <label class="inline-flex items-center gap-2">
                                    <input
                                        id="remember_me"
                                        name="remember"
                                        type="checkbox"
                                        class="rounded border-white/20 bg-[#1b1b1b] text-white focus:ring-white/20"
                                    >
                                    Remember me
                                </label>

                                @if (Route::has('password.request'))
                                    <a class="hover:text-white underline decoration-white/20" href="{{ route('password.request') }}">
                                        Forgot password?
                                    </a>
                                @endif
                            </div>

                            {{-- pill button like the sample --}}
                            <button
                                type="submit"
                                class="w-full rounded-full bg-[#e9e2d6] text-[#1c1c1c] font-semibold py-3 hover:opacity-95 active:opacity-90 shadow-lg"
                            >
                                Sign in
                            </button>

                            <div class="flex items-center gap-3 text-xs text-white/35">
                                <div class="h-px flex-1 bg-white/10"></div>
                                <span>or</span>
                                <div class="h-px flex-1 bg-white/10"></div>
                            </div>

                            <button
                                type="button"
                                class="w-full rounded-full border border-white/10 bg-white/5 py-3 font-medium hover:bg-white/10
                                       flex items-center justify-center gap-3"
                            >
                                <span class="text-lg">G</span>
                                <span>Sign in with Google</span>
                            </button>

                            @if (Route::has('register'))
                                <p class="text-xs text-white/50 text-center mt-3">
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
</x-guest-layout>
