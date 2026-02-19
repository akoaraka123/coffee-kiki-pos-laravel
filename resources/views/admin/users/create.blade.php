@extends('layouts.dashboard')

@section('title', 'Add Account')

@section('menu')
    <a href="{{ route('admin.dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
        Dashboard
    </a>
    <a href="{{ route('admin.users.index') }}" class="block rounded-2xl px-4 py-3 text-sm {{ request()->routeIs('admin.users.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
        Manage Accounts
    </a>
@endsection

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Add Account</h1>
            <p class="mt-1 text-sm text-white/60">Create a new staff/admin user.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-white/70 hover:text-white underline decoration-white/20">Back</a>
    </div>

    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200">
            <p class="font-medium">Please fix the errors</p>
            <p class="mt-1">{{ $errors->first() }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 space-y-5 rounded-[24px] border border-white/10 bg-white/5 p-6">
        @csrf

        <div>
            <label for="name" class="text-xs text-white/60">Name</label>
            <input id="name" name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
        </div>

        <div>
            <label for="email" class="text-xs text-white/60">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
        </div>

        <div>
            <label for="role" class="text-xs text-white/60">Role</label>
            <select id="role" name="role" class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20">
                <option value="staff" {{ old('role', 'staff') === 'staff' ? 'selected' : '' }}>staff</option>
                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>admin</option>
            </select>
        </div>

        <div>
            <label for="password" class="text-xs text-white/60">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
        </div>

        <div>
            <label for="password_confirmation" class="text-xs text-white/60">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-2xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white placeholder:text-white/30 focus:outline-none focus:ring-2 focus:ring-white/20" />
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('admin.users.index') }}" class="text-sm text-white/70 hover:text-white underline decoration-white/20">Cancel</a>
            <button type="submit" class="rounded-full bg-[#efe9df] px-5 py-3 text-sm font-semibold text-[#1c1c1c] shadow-lg hover:opacity-95 active:opacity-90">
                Create Account
            </button>
        </div>
    </form>
@endsection
