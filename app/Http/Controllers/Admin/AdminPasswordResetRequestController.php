<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPasswordResetRequestController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $status = $request->query('status', 'all');
        if (! in_array($status, ['pending', 'resolved', 'all'], true)) {
            $status = 'all';
        }

        $query = PasswordResetRequest::query()
            ->orderByDesc('requested_at');

        if ($status === 'pending') {
            $query->where('status', PasswordResetRequest::STATUS_PENDING);
        } elseif ($status === 'resolved') {
            $query->where('status', PasswordResetRequest::STATUS_RESOLVED);
        }

        if ($request->wantsJson()) {
            $rows = $query->get();
            $pendingCount = PasswordResetRequest::query()
                ->where('status', PasswordResetRequest::STATUS_PENDING)
                ->count();

            return response()->json([
                'data' => $rows->map(fn (PasswordResetRequest $r) => $this->serializeRequest($r))->values(),
                'pending_count' => $pendingCount,
            ]);
        }

        $pending = PasswordResetRequest::query()
            ->where('status', PasswordResetRequest::STATUS_PENDING)
            ->orderByDesc('requested_at')
            ->get();

        $resolved = PasswordResetRequest::query()
            ->where('status', PasswordResetRequest::STATUS_RESOLVED)
            ->orderByDesc('resolved_at')
            ->limit(100)
            ->get();

        return view('admin.password-reset-requests.index', [
            'pendingRequests' => $pending,
            'resolvedRequests' => $resolved,
        ]);
    }

    public function resolve(PasswordResetRequest $passwordResetRequest): JsonResponse
    {
        if ($passwordResetRequest->status === PasswordResetRequest::STATUS_RESOLVED) {
            return response()->json([
                'message' => 'Request already resolved.',
                'pending_count' => PasswordResetRequest::query()
                    ->where('status', PasswordResetRequest::STATUS_PENDING)
                    ->count(),
            ]);
        }

        $passwordResetRequest->update([
            'status' => PasswordResetRequest::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Marked as resolved.',
            'pending_count' => PasswordResetRequest::query()
                ->where('status', PasswordResetRequest::STATUS_PENDING)
                ->count(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRequest(PasswordResetRequest $r): array
    {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'email' => $r->email,
            'role' => $r->role,
            'status' => $r->status,
            'requested_at' => $r->requested_at?->toIso8601String(),
            'resolved_at' => $r->resolved_at?->toIso8601String(),
        ];
    }
}
