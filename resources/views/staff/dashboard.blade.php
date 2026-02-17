@extends('layouts.app-shell')

@section('title', 'Staff Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="rounded-[24px] border border-white/10 bg-[#232323] p-6">
            <h1 class="text-2xl font-semibold">Welcome</h1>
            <p class="mt-2 text-sm text-white/60">Signed in as <span class="text-white">{{ Auth::user()->email }}</span>.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-[24px] border border-white/10 bg-[#232323] p-6">
                <div class="text-sm text-white/60">Quick Actions</div>
                <div class="mt-2 text-sm text-white/80">Coming soon.</div>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-[#232323] p-6">
                <div class="text-sm text-white/60">Notes</div>
                <div class="mt-2 text-sm text-white/80">This area can be customized for staff workflows.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-full bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] hover:opacity-95">
                Logout
            </button>
        </form>
    </div>
@endsection
