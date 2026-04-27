@extends('layouts.dashboard')

@section('title', 'Manage Users')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Manage Users</h2>
                <p class="mt-1 text-sm text-white/50">Create, edit, and remove staff/admin accounts.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', 'create-user')"
                    class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95"
                >
                    Add Account
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-white/10 bg-white/5 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-white/5 text-white/70">
                        <tr>
                            <th class="px-5 py-4 font-medium">Name</th>
                            <th class="px-5 py-4 font-medium">Email</th>
                            <th class="px-5 py-4 font-medium">Role</th>
                            <th class="px-5 py-4 font-medium">Created</th>
                            <th class="px-5 py-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($users as $user)
                            @php
                                $showEditModal = (int) session('editUserId') === (int) $user->id;
                                $primaryAdminId = \App\Models\User::query()->where('role', 'admin')->orderBy('id')->value('id');
                                $isPrimaryAdmin = $user->role === 'admin' && (int) $primaryAdminId === (int) $user->id;
                            @endphp
                            <tr>
                                <td class="px-5 py-4">{{ $user->name }}</td>
                                <td class="px-5 py-4 text-white/70">{{ $user->email }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/80">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-white/70">{{ $user->created_at->format('F j, Y (l)') }}</td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-4">
                                        <button
                                            type="button"
                                            x-data
                                            x-on:click="$dispatch('open-modal', 'edit-user-{{ $user->id }}')"
                                            class="text-xs text-white/70 hover:text-white underline decoration-white/20"
                                        >
                                            Edit
                                        </button>
                                        @if (strtolower((string) ($user->role ?? '')) !== 'admin')
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this account?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-rose-300 hover:text-rose-200 underline decoration-rose-300/30">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <x-modal name="edit-user-{{ $user->id }}" :show="$showEditModal" maxWidth="2xl">
                                <div class="rounded-2xl border border-white/10 bg-[#1b1b1b] p-6 text-white shadow-2xl">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold">Edit Account</h3>
                                            <p class="mt-1 text-sm text-white/50">Update account details and role.</p>
                                        </div>
                                        <button type="button" x-data x-on:click="$dispatch('close-modal', 'edit-user-{{ $user->id }}')" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white/70 hover:bg-white/10 hover:text-white">
                                            Close
                                        </button>
                                    </div>

                                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-6 space-y-4">
                                        @csrf
                                        @method('PUT')

                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div class="sm:col-span-2">
                                                <label for="edit-name-{{ $user->id }}" class="text-xs text-white/60">Name</label>
                                                <input
                                                    id="edit-name-{{ $user->id }}"
                                                    name="name"
                                                    value="{{ $showEditModal ? old('name', $user->name) : $user->name }}"
                                                    required
                                                    class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                                                    oninput="validateNameInput(this)"
                                                />
                                                @error('name')
                                                    <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                                                @enderror
                                                <p id="edit-name-error-{{ $user->id }}" class="mt-1 text-xs text-rose-300 hidden"></p>
                                            </div>

                                            <div class="sm:col-span-2">
                                                <label for="edit-email-{{ $user->id }}" class="text-xs text-white/60">Email</label>
                                                <input
                                                    id="edit-email-{{ $user->id }}"
                                                    name="email"
                                                    type="email"
                                                    value="{{ $showEditModal ? old('email', $user->email) : $user->email }}"
                                                    required
                                                    class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                                                    oninput="validateEmailInput(this)"
                                                />
                                                @error('email')
                                                    <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                                                @enderror
                                                <p id="edit-email-error-{{ $user->id }}" class="mt-1 text-xs text-rose-300 hidden"></p>
                                            </div>

                                            <div class="sm:col-span-2">
                                                <label for="edit-role-{{ $user->id }}" class="text-xs text-white/60">Role</label>
                                                <select id="edit-role-{{ $user->id }}" name="role" class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20" {{ $isPrimaryAdmin ? 'disabled' : '' }}>
                                                    <option value="staff" {{ ($showEditModal ? old('role', $user->role) : $user->role) === 'staff' ? 'selected' : '' }}>Staff</option>
                                                    <option value="admin" {{ ($showEditModal ? old('role', $user->role) : $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                                                </select>
                                                @if ($isPrimaryAdmin)
                                                    <input type="hidden" name="role" value="{{ $showEditModal ? old('role', $user->role) : $user->role }}" />
                                                @endif
                                            </div>

                                            <div>
                                                <label for="edit-password-{{ $user->id }}" class="text-xs text-white/60">New Password (optional)</label>
                                                <input id="edit-password-{{ $user->id }}" name="password" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" oninput="validatePasswordInput(this)" />
                                                @error('password')
                                                    <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                                                @enderror
                                                <p id="edit-password-error-{{ $user->id }}" class="mt-1 text-xs text-rose-300 hidden"></p>
                                            </div>

                                            <div>
                                                <label for="edit-password-confirmation-{{ $user->id }}" class="text-xs text-white/60">Confirm New Password</label>
                                                <input id="edit-password-confirmation-{{ $user->id }}" name="password_confirmation" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" oninput="validatePasswordConfirmation(this)" />
                                                @error('password_confirmation')
                                                    <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                                                @enderror
                                                <p id="edit-password-confirmation-error-{{ $user->id }}" class="mt-1 text-xs text-rose-300 hidden"></p>
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                                            <button type="button" x-data x-on:click="$dispatch('close-modal', 'edit-user-{{ $user->id }}')" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">
                                                Cancel
                                            </button>
                                            <button type="submit" class="rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                                                Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </x-modal>
                        @empty
                            <tr>
                                <td class="px-5 py-6 text-white/60" colspan="5">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-modal name="create-user" :show="isset($showCreateModal) && $showCreateModal" maxWidth="2xl">
        <div class="rounded-2xl border border-white/10 bg-[#1b1b1b] p-6 text-white shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold">Add Account</h3>
                    <p class="mt-1 text-sm text-white/50">Create a new staff/admin user.</p>
                </div>
                <button type="button" x-data x-on:click="$dispatch('close-modal', 'create-user')" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white/70 hover:bg-white/10 hover:text-white">
                    Close
                </button>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 space-y-4">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="text-xs text-white/60">Name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" oninput="validateNameInput(this)" />
                        @error('name')
                            <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                        @enderror
                        <p id="create-name-error" class="mt-1 text-xs text-rose-300 hidden"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="email" class="text-xs text-white/60">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" oninput="validateEmailInput(this)" />
                        @error('email')
                            <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                        @enderror
                        <p id="create-email-error" class="mt-1 text-xs text-rose-300 hidden"></p>
                    </div>

                    <div>
                        <label for="role" class="text-xs text-white/60">Role</label>
                        <select id="role" name="role" class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20">
                            <option value="staff" {{ old('role', 'staff') === 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>

                    <div>
                        <label for="password" class="text-xs text-white/60">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" oninput="validatePasswordInput(this)" />
                        @error('password')
                            <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                        @enderror
                        <p id="create-password-error" class="mt-1 text-xs text-rose-300 hidden"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="password_confirmation" class="text-xs text-white/60">Confirm Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" oninput="validatePasswordConfirmation(this)" />
                        @error('password_confirmation')
                            <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                        @enderror
                        <p id="create-password-confirmation-error" class="mt-1 text-xs text-rose-300 hidden"></p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" x-data x-on:click="$dispatch('close-modal', 'create-user')" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                        Create Account
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
@endsection

<script>
    const nameRegex = /^[A-Za-zÑñ .'-]+$/;
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d!@#$%^&*_.?-]+$/;

    function validateNameInput(input) {
        const errorElement = input.id.includes('edit-name') 
            ? document.getElementById(input.id.replace('edit-name', 'edit-name-error'))
            : document.getElementById('create-name-error');
        const value = input.value.trim();
        
        if (value.length === 0) {
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.classList.add('hidden');
            }
            input.classList.remove('border-rose-500');
            return true;
        }
        
        if (value.length < 2) {
            if (errorElement) {
                errorElement.textContent = 'Name must be at least 2 characters.';
                errorElement.classList.remove('hidden');
            }
            input.classList.add('border-rose-500');
            return false;
        }
        
        if (!nameRegex.test(value)) {
            if (errorElement) {
                errorElement.textContent = 'Name may only contain letters, spaces, Ñ, ñ, hyphen, apostrophe, and period.';
                errorElement.classList.remove('hidden');
            }
            input.classList.add('border-rose-500');
            return false;
        }
        
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.add('hidden');
        }
        input.classList.remove('border-rose-500');
        return true;
    }

    function validateEmailInput(input) {
        const errorElement = input.id.includes('edit-email')
            ? document.getElementById(input.id.replace('edit-email', 'edit-email-error'))
            : document.getElementById('create-email-error');
        const value = input.value.trim().toLowerCase();
        
        if (value.length === 0) {
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.classList.add('hidden');
            }
            input.classList.remove('border-rose-500');
            return true;
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            if (errorElement) {
                errorElement.textContent = 'Please enter a valid email address.';
                errorElement.classList.remove('hidden');
            }
            input.classList.add('border-rose-500');
            return false;
        }
        
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.add('hidden');
        }
        input.classList.remove('border-rose-500');
        return true;
    }

    function validatePasswordInput(input) {
        const errorElement = input.id.includes('edit-password')
            ? document.getElementById(input.id.replace('edit-password', 'edit-password-error'))
            : document.getElementById('create-password-error');
        const value = input.value;
        
        if (value.length === 0) {
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.classList.add('hidden');
            }
            input.classList.remove('border-rose-500');
            return true;
        }
        
        if (value.length < 8) {
            if (errorElement) {
                errorElement.textContent = 'Password must be at least 8 characters.';
                errorElement.classList.remove('hidden');
            }
            input.classList.add('border-rose-500');
            return false;
        }
        
        if (value.length > 64) {
            if (errorElement) {
                errorElement.textContent = 'Password must not exceed 64 characters.';
                errorElement.classList.remove('hidden');
            }
            input.classList.add('border-rose-500');
            return false;
        }
        
        if (!passwordRegex.test(value)) {
            if (errorElement) {
                errorElement.textContent = 'Password must include uppercase, lowercase, and number. Only common symbols are allowed.';
                errorElement.classList.remove('hidden');
            }
            input.classList.add('border-rose-500');
            return false;
        }
        
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.add('hidden');
        }
        input.classList.remove('border-rose-500');
        return true;
    }

    function validatePasswordConfirmation(input) {
        const errorElement = input.id.includes('edit-password-confirmation')
            ? document.getElementById(input.id.replace('edit-password-confirmation', 'edit-password-confirmation-error'))
            : document.getElementById('create-password-confirmation-error');
        const passwordInput = input.id.includes('edit-password-confirmation')
            ? document.getElementById(input.id.replace('-confirmation', ''))
            : document.getElementById('password');
        
        if (!passwordInput) return true;
        
        const password = passwordInput.value;
        const confirmation = input.value;
        
        if (confirmation.length === 0) {
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.classList.add('hidden');
            }
            input.classList.remove('border-rose-500');
            return true;
        }
        
        if (password !== confirmation) {
            if (errorElement) {
                errorElement.textContent = 'Password confirmation does not match.';
                errorElement.classList.remove('hidden');
            }
            input.classList.add('border-rose-500');
            return false;
        }
        
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.add('hidden');
        }
        input.classList.remove('border-rose-500');
        return true;
    }
</script>
