@extends('layouts.app-shell')

@section('title', 'Admin Dashboard')

@section('sidebar')
    <a href="{{ route('admin.dashboard') }}" class="block rounded-2xl px-4 py-3 text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
        Dashboard
    </a>
    <a href="{{ route('admin.users.index') }}" class="block rounded-2xl px-4 py-3 text-sm {{ request()->routeIs('admin.users.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
        Manage Users
    </a>
    <form method="POST" action="{{ route('logout') }}" class="pt-2">
        @csrf
        <button type="submit" class="w-full rounded-2xl px-4 py-3 text-left text-sm text-white/70 hover:bg-white/5 hover:text-white">
            Logout
        </button>
    </form>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="rounded-[24px] border border-white/10 bg-[#232323] p-6">
            <h1 class="text-2xl font-semibold">Welcome, Admin</h1>
            <p class="mt-2 text-sm text-white/60">You are signed in as <span class="text-white">{{ Auth::user()->email }}</span>.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-[24px] border border-white/10 bg-[#232323] p-6">
                <div class="text-sm text-white/60">Quick Action</div>
                <a href="{{ route('admin.users.create') }}" class="mt-3 inline-flex items-center justify-center rounded-full bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] hover:opacity-95">
                    Add Staff Account
                </a>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-[#232323] p-6">
                <div class="text-sm text-white/60">System</div>
                <div class="mt-2 text-sm text-white/80">Manage staff accounts and access control.</div>
            </div>
        </div>
    </div>
@endsection
