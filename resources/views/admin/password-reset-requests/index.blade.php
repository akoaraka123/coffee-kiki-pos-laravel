@extends('layouts.dashboard')

@section('title', 'Password Reset Requests')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Password Reset Requests</h2>
                <p class="mt-1 text-sm text-white/50">Staff requests to reset passwords. Resolve after you have helped them.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 shadow-sm hover:bg-white/10">
                Back to Dashboard
            </a>
        </div>

        <div class="space-y-6">
            <div>
                <h3 class="text-sm font-semibold text-white/90">Pending</h3>
                <p class="mt-1 text-xs text-white/45">Awaiting admin action.</p>
                <div class="mt-4 overflow-hidden rounded-xl border border-white/10">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-white/5 text-white/70">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Staff name</th>
                                    <th class="px-4 py-3 font-medium">Email</th>
                                    <th class="px-4 py-3 font-medium">Role</th>
                                    <th class="px-4 py-3 font-medium">Requested</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                @forelse ($pendingRequests as $req)
                                    <tr id="request-{{ $req->id }}">
                                        <td class="px-4 py-3 font-medium">{{ $req->name }}</td>
                                        <td class="px-4 py-3 text-white/70">{{ $req->email }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/80">{{ $req->role }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-white/60">{{ $req->requested_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-200">Pending</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                class="js-resolve-prr inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-white/85 hover:bg-white/10"
                                                data-url="{{ route('admin.password-reset-requests.resolve', $req) }}"
                                            >
                                                Mark as Resolved
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-sm text-white/45">No pending requests.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-white/90">Recently resolved</h3>
                <p class="mt-1 text-xs text-white/45">Last 100 resolved requests.</p>
                <div class="mt-4 overflow-hidden rounded-xl border border-white/10">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-white/5 text-white/70">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Staff name</th>
                                    <th class="px-4 py-3 font-medium">Email</th>
                                    <th class="px-4 py-3 font-medium">Role</th>
                                    <th class="px-4 py-3 font-medium">Requested</th>
                                    <th class="px-4 py-3 font-medium">Resolved</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                @forelse ($resolvedRequests as $req)
                                    <tr>
                                        <td class="px-4 py-3 font-medium">{{ $req->name }}</td>
                                        <td class="px-4 py-3 text-white/70">{{ $req->email }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/80">{{ $req->role }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-white/60">{{ $req->requested_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</td>
                                        <td class="px-4 py-3 text-white/60">{{ $req->resolved_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full border border-emerald-500/25 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-200">Resolved</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-sm text-white/45">No resolved requests yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var csrf = document.querySelector('meta[name="csrf-token"]');
            var token = csrf ? csrf.getAttribute('content') : '';

            document.querySelectorAll('.js-resolve-prr').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var url = btn.getAttribute('data-url');
                    if (!url || btn.disabled) return;
                    btn.disabled = true;
                    fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({}),
                    })
                        .then(function (r) {
                            return r.json().then(function (data) {
                                return { ok: r.ok, data: data };
                            });
                        })
                        .then(function (res) {
                            if (res.ok) {
                                window.location.reload();
                                return;
                            }
                            btn.disabled = false;
                            alert(res.data.message || 'Could not update request.');
                        })
                        .catch(function () {
                            btn.disabled = false;
                            alert('Network error. Please try again.');
                        });
                });
            });
        })();
    </script>
@endsection
