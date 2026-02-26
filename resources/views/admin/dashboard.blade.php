@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Overview</h2>
                <p class="mt-1 text-sm text-white/50">Track performance and manage the system.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 shadow-sm hover:bg-white/10">
                    Manage Users
                </a>
                <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                    Add Account
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-xs text-white/50">Total Sales</div>
                <div class="mt-2 text-2xl font-semibold">—</div>
                <div class="mt-1 text-xs text-white/35">Placeholder</div>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-xs text-white/50">Total Orders</div>
                <div class="mt-2 text-2xl font-semibold">—</div>
                <div class="mt-1 text-xs text-white/35">Placeholder</div>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-xs text-white/50">Total Products</div>
                <div class="mt-2 text-2xl font-semibold">—</div>
                <div class="mt-1 text-xs text-white/35">Placeholder</div>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-xs text-white/50">Total Staff Users</div>
                <div class="mt-2 text-2xl font-semibold">{{ \App\Models\User::query()->where('role', 'staff')->count() }}</div>
                <div class="mt-1 text-xs text-white/35">Active accounts</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold">Recent Users</div>
                        <div class="mt-1 text-xs text-white/50">Latest accounts created.</div>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-white/70 hover:text-white underline decoration-white/20">View all</a>
                </div>

                <div class="mt-4 overflow-hidden rounded-xl border border-white/10">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-white/5 text-white/70">
                            <tr>
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">Email</th>
                                <th class="px-4 py-3 font-medium">Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            @foreach (\App\Models\User::query()->latest()->limit(5)->get(['name','email','role']) as $user)
                                <tr>
                                    <td class="px-4 py-3">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-white/70">{{ $user->email }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/80">
                                            {{ $user->role }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-white/10 bg-white/5 p-5 shadow-sm">
                <div class="text-sm font-semibold">Quick Actions</div>
                <div class="mt-1 text-xs text-white/50">Shortcuts for admins.</div>

                <div class="mt-4 grid grid-cols-1 gap-3">
                    <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#efe9df] px-4 py-2 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95">
                        Add Account
                    </a>
                    <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">
                        View Orders
                    </a>
                    <a href="#" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 hover:bg-white/10">
                        View Products
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
