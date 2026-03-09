<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $request->user()->fill($data);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update only the POS layout preference for the authenticated staff user.
     */
    public function updatePosLayout(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isStaff() || ! Schema::hasColumn('users', 'pos_layout')) {
            abort(403);
        }

        $layout = $request->string('pos_layout')->toString();
        if (! in_array($layout, ['left', 'right'], true)) {
            $layout = 'right';
        }

        $user->pos_layout = $layout;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'pos_layout' => $layout,
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-pos-layout-updated');
    }

    /**
     * Update only the clocked_in status for the authenticated staff user.
     */
    public function updateClockedIn(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isStaff() || ! Schema::hasColumn('users', 'clocked_in')) {
            abort(403);
        }

        $clockedIn = (bool) $request->boolean('clocked_in');

        $user->clocked_in = $clockedIn;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'clocked_in' => $clockedIn,
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-clocked-in-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
