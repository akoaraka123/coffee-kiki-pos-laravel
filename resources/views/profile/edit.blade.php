@extends('layouts.dashboard')

@section('title', 'Settings')

@section('content')
    <div class="min-h-[calc(100vh-96px)] flex items-center justify-center px-4 sm:px-6 py-8">
        <div class="w-full max-w-2xl rounded-2xl border border-white/10 bg-[#111]/95 p-8 shadow-2xl space-y-8">
            
            <!-- Profile Section -->
            <div class="flex flex-col items-center text-center space-y-4">
                <div class="h-24 w-24 rounded-full bg-gradient-to-br from-amber-500 to-amber-700 flex items-center justify-center shadow-lg">
                    <span class="text-3xl font-bold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-white">{{ $user->name }}</h2>
                    <div class="mt-1 flex items-center justify-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-300">
                            {{ $user->role === 'admin' ? 'Admin' : 'Staff' }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-white/60">{{ $user->email }}</p>
                </div>
            </div>

            <div class="h-px w-full bg-white/10"></div>

            <!-- Profile Information Section -->
            <div class="space-y-4">
                <h3 class="text-base font-semibold text-white">Profile Information</h3>
                <form id="profile-form" method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="name" class="text-sm font-medium text-white/80">Name</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name', $user->name) }}"
                                required
                                autocomplete="name"
                                class="mt-2 h-12 w-full rounded-xl border border-white/15 bg-black/40 px-4 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all"
                                oninput="validateName(this)"
                            />
                            @if ($errors->has('name'))
                                <p class="mt-1 text-xs text-rose-300">{{ $errors->first('name') }}</p>
                            @endif
                            <p id="name-error" class="mt-1 text-xs text-rose-300 hidden"></p>
                        </div>

                        <div>
                            <label for="email" class="text-sm font-medium text-white/80">Email</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email', $user->email) }}"
                                required
                                autocomplete="username"
                                class="mt-2 h-12 w-full rounded-xl border border-white/15 bg-black/40 px-4 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all"
                                oninput="validateEmail(this)"
                            />
                            @if ($errors->has('email'))
                                <p class="mt-1 text-xs text-rose-300">{{ $errors->first('email') }}</p>
                            @endif
                            <p id="email-error" class="mt-1 text-xs text-rose-300 hidden"></p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Current Order Panel Setting -->
            @if ($user->role === 'staff')
            <div class="rounded-xl border border-white/10 bg-black/40 px-5 py-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-white">Current Order on left side</div>
                        <div class="mt-1 text-xs text-white/50">Toggle the position of the Current Order panel in POS.</div>
                    </div>

                    <div class="flex items-center gap-3">
                        @php
                            $layout = old('pos_layout', $user->pos_layout ?? 'right');
                        @endphp
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
                            <span class="flex h-8 w-14 items-center justify-start rounded-full border border-white/20 bg-black/40 px-1 transition-colors duration-200 peer-checked:bg-white/20 peer-checked:justify-end">
                                <span class="h-6 w-6 rounded-full bg-white shadow transition-transform duration-200"></span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            @endif

            <!-- Update Password Section -->
            <div class="rounded-xl border border-white/10 bg-black/40 px-5 py-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-white">Update Password</div>
                        <div class="mt-1 text-xs text-white/50">Keep your account secure by using a strong, unique password.</div>
                    </div>

                    <button
                        type="button"
                        onclick="document.getElementById('password-modal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 rounded-xl border border-amber-500/50 bg-amber-500/10 px-4 py-2.5 text-sm font-semibold text-amber-300 hover:bg-amber-500/20 transition-all"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Change password
                    </button>
                </div>
            </div>

            <!-- Save Changes Button -->
            <div class="pt-2">
                <button
                    type="submit"
                    form="profile-form"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-3.5 text-sm font-semibold text-white shadow-lg hover:from-amber-700 hover:to-amber-800 transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Save changes
                </button>
            </div>

            <!-- Success/Error Messages -->
            @if (session('status') === 'profile-updated')
                <div class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                    Settings updated successfully.
                </div>
            @endif
        </div>
    </div>

    <!-- Password Change Modal -->
    <div
        id="password-modal"
        class="hidden fixed inset-0 z-50 flex items-center justify-center"
    >
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('password-modal').classList.add('hidden')"></div>
        
        <div class="relative z-10 w-full max-w-md rounded-2xl border border-white/10 bg-[#111]/95 p-6 shadow-2xl mx-4">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-white">Change Password</h3>
                <button
                    type="button"
                    onclick="document.getElementById('password-modal').classList.add('hidden')"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-white/60 hover:bg-white/10 hover:text-white transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="modal_current_password" class="text-sm font-medium text-white/80">Current Password</label>
                    <input
                        id="modal_current_password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="mt-2 h-12 w-full rounded-xl border border-white/15 bg-black/40 px-4 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all"
                    />
                    @if ($errors->updatePassword->has('current_password'))
                        <p class="mt-1 text-xs text-rose-300">{{ $errors->updatePassword->first('current_password') }}</p>
                    @endif
                </div>

                <div>
                    <label for="modal_password" class="text-sm font-medium text-white/80">New Password</label>
                    <input
                        id="modal_password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="mt-2 h-12 w-full rounded-xl border border-white/15 bg-black/40 px-4 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all"
                    />
                    @if ($errors->updatePassword->has('password'))
                        <p class="mt-1 text-xs text-rose-300">{{ $errors->updatePassword->first('password') }}</p>
                    @endif
                </div>

                <div>
                    <label for="modal_password_confirmation" class="text-sm font-medium text-white/80">Confirm New Password</label>
                    <input
                        id="modal_password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="mt-2 h-12 w-full rounded-xl border border-white/15 bg-black/40 px-4 text-sm text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all"
                    />
                    @if ($errors->updatePassword->has('password_confirmation'))
                        <p class="mt-1 text-xs text-rose-300">{{ $errors->updatePassword->first('password_confirmation') }}</p>
                    @endif
                </div>

                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        onclick="document.getElementById('password-modal').classList.add('hidden')"
                        class="flex-1 inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white/80 hover:bg-white/10 transition-all"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="flex-1 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg hover:from-amber-700 hover:to-amber-800 transition-all"
                    >
                        Save Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const nameRegex = /^[A-Za-zÑñ .'-]+$/;

        function validateName(input) {
            const errorElement = document.getElementById('name-error');
            const value = input.value.trim();
            
            if (value.length === 0) {
                errorElement.textContent = '';
                errorElement.classList.add('hidden');
                input.classList.remove('border-rose-500');
                return true;
            }
            
            if (value.length < 2) {
                errorElement.textContent = 'Name must be at least 2 characters.';
                errorElement.classList.remove('hidden');
                input.classList.add('border-rose-500');
                return false;
            }
            
            if (!nameRegex.test(value)) {
                errorElement.textContent = 'Name may only contain letters, spaces, Ñ, ñ, hyphen, apostrophe, and period.';
                errorElement.classList.remove('hidden');
                input.classList.add('border-rose-500');
                return false;
            }
            
            errorElement.textContent = '';
            errorElement.classList.add('hidden');
            input.classList.remove('border-rose-500');
            return true;
        }

        function validateEmail(input) {
            const errorElement = document.getElementById('email-error');
            const value = input.value.trim().toLowerCase();
            
            if (value.length === 0) {
                errorElement.textContent = '';
                errorElement.classList.add('hidden');
                input.classList.remove('border-rose-500');
                return true;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                errorElement.textContent = 'Please enter a valid email address.';
                errorElement.classList.remove('hidden');
                input.classList.add('border-rose-500');
                return false;
            }
            
            errorElement.textContent = '';
            errorElement.classList.add('hidden');
            input.classList.remove('border-rose-500');
            return true;
        }

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
@endsection
