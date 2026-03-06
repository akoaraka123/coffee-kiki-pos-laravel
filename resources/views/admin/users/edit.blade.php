@extends('layouts.dashboard')

@section('title', 'Edit Account')

@section('menu')
    <a href="{{ route('admin.dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
        Dashboard
    </a>
    <a href="{{ route('admin.users.index') }}" class="block rounded-2xl px-4 py-3 text-sm {{ request()->routeIs('admin.users.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
        Manage Accounts
    </a>
@endsection

@section('content')
    <div class="mx-auto w-full max-w-2xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Edit Account</h1>
                <p class="mt-1 text-sm text-white/60">Update account details and role.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-white/70 hover:text-white underline decoration-white/20">Back</a>
        </div>

        <div class="mt-6 rounded-2xl border border-white/10 bg-[#1b1b1b] p-6 text-white shadow-2xl">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="text-xs text-white/60">Name</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="email" class="text-xs text-white/60">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="role" class="text-xs text-white/60">Role</label>
                        <select id="role" name="role" class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20">
                            <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>

                    <div>
                        <label for="password" class="text-xs text-white/60">New Password (optional)</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
                    </div>

                    <div>
                        <label for="password_confirmation" class="text-xs text-white/60">Confirm New Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-white/10 bg-[#111] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">Cancel</a>
                    <button type="submit" class="rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
