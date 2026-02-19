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
                            <tr>
                                <td class="px-5 py-4">{{ $user->name }}</td>
                                <td class="px-5 py-4 text-white/70">{{ $user->email }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/80">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-white/70">{{ $user->created_at->format('Y-m-d') }}</td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-4">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="text-xs text-white/70 hover:text-white underline decoration-white/20">Edit</a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-300 hover:text-rose-200 underline decoration-rose-300/30">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
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
                        <input id="name" name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="email" class="text-xs text-white/60">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
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
                        <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="password_confirmation" class="text-xs text-white/60">Confirm Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
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
