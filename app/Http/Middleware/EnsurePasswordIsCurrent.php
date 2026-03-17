<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsCurrent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $sessionHash = $request->session()->get('auth_password_hash');
        $currentHash = (string) ($user->password ?? '');
        $sessionRole = $request->session()->get('auth_role');
        $currentRole = (string) ($user->role ?? '');

        if ((!is_string($sessionHash) || $sessionHash === '') && $currentHash !== '') {
            $request->session()->put('auth_password_hash', $currentHash);
        }

        if ((!is_string($sessionRole) || $sessionRole === '') && $currentRole !== '') {
            $request->session()->put('auth_role', $currentRole);
        }

        if (is_string($sessionHash) && $sessionHash !== '' && $currentHash !== '' && $sessionHash !== $currentHash) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your password was changed. Please log in again.',
                ], 401);
            }

            return redirect()->route('login')->with('status', 'Your password was changed. Please log in again.');
        }

        if (is_string($sessionRole) && $sessionRole !== '' && $currentRole !== '' && $sessionRole !== $currentRole) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account role was changed. Please log in again.',
                ], 401);
            }

            return redirect()->route('login')->with('status', 'Your account role was changed. Please log in again.');
        }

        return $next($request);
    }
}
