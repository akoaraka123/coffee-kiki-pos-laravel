@extends('layouts.app-shell')

@section('title', 'Manage Users')

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
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Manage Users</h1>
            <p class="mt-1 text-sm text-white/60">Create and remove staff/admin accounts.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-full bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] hover:opacity-95">
            Add Account
        </a>
    </div>

    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200">
            <p class="font-medium">Action failed</p>
            <p class="mt-1">{{ $errors->first() }}</p>
        </div>
    @endif

    <div class="mt-6 overflow-hidden rounded-[24px] border border-white/10 bg-[#232323]">
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
                                <span class="inline-flex items-center rounded-full bg-white/5 px-3 py-1 text-xs text-white/80 border border-white/10">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-white/70">{{ $user->created_at->format('Y-m-d') }}</td>
                            <td class="px-5 py-4 text-right">
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this account?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-rose-300 hover:text-rose-200 underline decoration-rose-300/30">Delete</button>
                                </form>
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
@endsection
