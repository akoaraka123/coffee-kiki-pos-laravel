<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StaffShiftController extends Controller
{
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();

        $activeSalesSession = Shift::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->first();

        // Auto-create session if none exists
        if (!$activeSalesSession) {
            $businessDate = Carbon::now()->toDateString();
            $salesSessionId = 'SESSION-' . Carbon::now()->format('YmdHis') . '-' . strtoupper(substr($user->name, 0, 5));

            $activeSalesSession = Shift::create([
                'user_id' => $user->id,
                'shift_id' => $salesSessionId,
                'business_date' => $businessDate,
                'started_at' => Carbon::now(),
                'status' => 'ACTIVE',
                'opening_cash' => null,
            ]);
        }

        return response()->json([
            'active_sales_session' => $activeSalesSession,
        ]);
    }

    public function closeAndCreateNew(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'closing_cash' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Close current active session
        $activeSession = Shift::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->first();

        if ($activeSession) {
            $activeSession->update([
                'ended_at' => Carbon::now(),
                'status' => 'CLOSED',
                'closing_cash' => $validated['closing_cash'] ?? null,
            ]);
        }

        // Create new active session
        $businessDate = Carbon::now()->toDateString();
        $newSalesSessionId = 'SESSION-' . Carbon::now()->format('YmdHis') . '-' . strtoupper(substr($user->name, 0, 5));

        $newSession = Shift::create([
            'user_id' => $user->id,
            'shift_id' => $newSalesSessionId,
            'business_date' => $businessDate,
            'started_at' => Carbon::now(),
            'status' => 'ACTIVE',
            'opening_cash' => null,
        ]);

        return response()->json([
            'message' => 'Money inventory saved. New sales session started.',
            'closed_session' => $activeSession,
            'new_session' => $newSession,
        ]);
    }
}
