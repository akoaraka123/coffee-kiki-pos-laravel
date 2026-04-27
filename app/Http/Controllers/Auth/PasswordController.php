<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:64',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d!@#$%^&*_.?-]+$/',
            ],
        ], [
            'current_password.required' => 'Current password is required.',
            'current_password.current_password' => 'Current password is incorrect.',
            'password.required' => 'New password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must not exceed 64 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.regex' => 'Password must be 8-64 characters and include uppercase, lowercase, and number. Only common symbols are allowed.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $request->session()->put('auth_password_hash', (string) ($request->user()->password ?? ''));

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'password-updated');
    }
}
