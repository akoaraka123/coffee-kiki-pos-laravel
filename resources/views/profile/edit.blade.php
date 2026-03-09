@extends('layouts.dashboard')

@section('title', 'Settings')

@section('content')
    <div class="min-h-[calc(100vh-96px)] flex items-center justify-center px-4 sm:px-6">
        <div class="w-full max-w-xl rounded-2xl border border-white/10 bg-[#111]/95 p-6 shadow-2xl space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-white">Account Settings</h2>
                <p class="mt-1 text-xs text-white/60">Update your profile, password, and POS layout preference.</p>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="name" class="text-xs font-semibold text-white/70">Name</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $user->name) }}"
                            required
                            autocomplete="name"
                            class="mt-2 h-10 w-full rounded-xl border border-white/15 bg-black/40 px-3 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/25"
                        />
                        @if ($errors->has('name'))
                            <p class="mt-1 text-xs text-rose-300">{{ $errors->first('name') }}</p>
                        @endif
                    </div>

                    <div>
                        <label for="email" class="text-xs font-semibold text-white/70">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $user->email) }}"
                            required
                            autocomplete="username"
                            class="mt-2 h-10 w-full rounded-xl border border-white/15 bg-black/40 px-3 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/25"
                        />
                        @if ($errors->has('email'))
                            <p class="mt-1 text-xs text-rose-300">{{ $errors->first('email') }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-2 rounded-xl border border-white/10 bg-black/40 px-4 py-3" id="clocked-in-section">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-xs font-semibold text-white/80">Clock In / Clock Out</div>
                            <div class="mt-0.5 text-[11px] text-white/50">When off, you cannot add or checkout orders in POS.</div>
                        </div>

                        <div class="flex items-center gap-3">
                            @php
                                $clockedIn = old('clocked_in', ($user->clocked_in ?? true) ? 1 : 0);
                            @endphp
                            <span class="text-[11px] text-white/60">Off</span>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input
                                    id="clocked-in-toggle"
                                    type="checkbox"
                                    class="peer sr-only"
                                    data-update-url="{{ route('profile.clocked-in') }}"
                                    {{ (int) $clockedIn === 1 ? 'checked' : '' }}
                                >
                                <span class="flex h-7 w-12 items-center justify-start rounded-full border border-white/20 bg-black/40 px-0.5 transition-colors duration-150 peer-checked:bg-emerald-500 peer-checked:justify-end">
                                    <span class="h-5 w-5 rounded-full bg-white shadow transition-transform duration-150"></span>
                                </span>
                            </label>
                            <span class="text-[11px] text-white/80">On</span>
                        </div>
                    </div>
                </div>

                <div class="mt-2 rounded-xl border border-white/10 bg-black/40 px-4 py-3" id="pos-layout-section">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-xs font-semibold text-white/80">Current Order on left side</div>
                            <div class="mt-0.5 text-[11px] text-white/50">Toggle the position of the Current Order panel in POS.</div>
                        </div>

                        <div class="flex items-center gap-3">
                            @php
                                $layout = old('pos_layout', $user->pos_layout ?? 'right');
                            @endphp
                            <span class="text-[11px] text-white/60">Off</span>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input
                                    id="pos-layout-toggle"
                                    type="checkbox"
                                    name="pos_layout"
                                    value="left"
                                    class="peer sr-only"
                                    data-update-url="{{ route('profile.pos-layout') }}"
                                    {{ $layout === 'left' ? 'checked' : '' }}
                                >
                                <span class="flex h-7 w-12 items-center justify-start rounded-full border border-white/20 bg-black/40 px-0.5 transition-colors duration-150 peer-checked:bg-emerald-500 peer-checked:justify-end">
                                    <span class="h-5 w-5 rounded-full bg-white shadow transition-transform duration-150"></span>
                                </span>
                            </label>
                            <span class="text-[11px] text-white/80">On</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="text-[11px] text-white/50">
                        @if (session('status') === 'profile-updated')
                            <span>Changes saved.</span>
                        @else
                            <span>Profile & layout are shared across staff views.</span>
                        @endif
                    </div>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-xs font-semibold text-[#111] shadow-sm hover:opacity-95"
                    >
                        Save changes
                    </button>
                </div>
            </form>

            <div class="h-px w-full bg-white/10"></div>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="current_password" class="text-xs font-semibold text-white/70">Current Password</label>
                        <input
                            id="current_password"
                            name="current_password"
                            type="password"
                            autocomplete="current-password"
                            class="mt-2 h-10 w-full rounded-xl border border-white/15 bg-black/40 px-3 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/25"
                        />
                        @if ($errors->updatePassword->has('current_password'))
                            <p class="mt-1 text-xs text-rose-300">{{ $errors->updatePassword->first('current_password') }}</p>
                        @endif
                    </div>

                    <div>
                        <label for="password" class="text-xs font-semibold text-white/70">New Password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            class="mt-2 h-10 w-full rounded-xl border border-white/15 bg-black/40 px-3 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/25"
                        />
                        @if ($errors->updatePassword->has('password'))
                            <p class="mt-1 text-xs text-rose-300">{{ $errors->updatePassword->first('password') }}</p>
                        @endif
                    </div>

                    <div>
                        <label for="password_confirmation" class="text-xs font-semibold text-white/70">Confirm New Password</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="mt-2 h-10 w-full rounded-xl border border-white/15 bg-black/40 px-3 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/25"
                        />
                        @if ($errors->updatePassword->has('password_confirmation'))
                            <p class="mt-1 text-xs text-rose-300">{{ $errors->updatePassword->first('password_confirmation') }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <div class="text-[11px] text-white/50">
                        Use a strong, unique password for this account.
                    </div>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/5 px-4 py-2 text-xs font-semibold text-white hover:bg-white/10"
                    >
                        Update password
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.getElementById('pos-layout-toggle');
            if (!toggle) return;

            var url = toggle.dataset.updateUrl || '';
            var meta = document.querySelector('meta[name="csrf-token"]');
            var token = meta ? meta.getAttribute('content') : null;
            var saving = false;

            toggle.addEventListener('change', function () {
                if (!url || !token || saving) {
                    return;
                }

                saving = true;
                var desired = toggle.checked ? 'left' : 'right';

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ pos_layout: desired }),
                }).finally(function () {
                    saving = false;
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.getElementById('clocked-in-toggle');
            if (!toggle) return;

            var url = toggle.dataset.updateUrl || '';
            var meta = document.querySelector('meta[name="csrf-token"]');
            var token = meta ? meta.getAttribute('content') : null;
            var saving = false;

            toggle.addEventListener('change', function () {
                if (!url || !token || saving) {
                    return;
                }

                saving = true;
                var desired = !!toggle.checked;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ clocked_in: desired }),
                }).finally(function () {
                    saving = false;
                });
            });
        });
    </script>
@endsection
