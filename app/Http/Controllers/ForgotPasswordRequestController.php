<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreForgotPasswordRequest;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ForgotPasswordRequestController extends Controller
{
    public function store(StoreForgotPasswordRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->validated('email')));

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereIn('role', ['staff', 'admin'])
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Email not found.',
                'errors' => [
                    'email' => [
                        'No staff or admin account uses this email. Check spelling or try another.',
                    ],
                ],
            ], 422);
        }

        $alreadyPending = PasswordResetRequest::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('status', PasswordResetRequest::STATUS_PENDING)
            ->exists();

        if (! $alreadyPending) {
            PasswordResetRequest::query()->create([
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'status' => PasswordResetRequest::STATUS_PENDING,
                'requested_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Password reset request sent to admin.',
        ]);
    }
}
