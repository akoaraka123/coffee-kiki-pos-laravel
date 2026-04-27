<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    private function isPrimaryAdmin(User $user): bool
    {
        $primaryAdminId = User::query()
            ->where('role', 'admin')
            ->orderBy('id')
            ->value('id');

        if (! $primaryAdminId) {
            return false;
        }

        return (int) $primaryAdminId === (int) $user->id;
    }

    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->orderByDesc('created_at')
                ->get(['id', 'name', 'email', 'role', 'created_at']),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->orderByDesc('created_at')
                ->get(['id', 'name', 'email', 'role', 'created_at']),
            'showCreateModal' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[A-Za-zÑñ .\'-]+$/',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:' . User::class,
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:64',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d!@#$%^&*_.?-]+$/',
            ],
            'role' => ['nullable', 'string', 'in:admin,staff'],
        ], [
            'name.regex' => 'Name may only contain letters, spaces, Ñ, ñ, hyphen, apostrophe, and period.',
            'name.min' => 'Name must be at least 2 characters.',
            'name.max' => 'Name must not exceed 100 characters.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email must not exceed 255 characters.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must not exceed 64 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.regex' => 'Password must be 8-64 characters and include uppercase, lowercase, and number. Only common symbols are allowed.',
        ]);

        User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'staff',
        ]);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'isPrimaryAdmin' => $this->isPrimaryAdmin($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[A-Za-zÑñ .\'-]+$/',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:' . User::class . ',email,' . $user->id,
            ],
            'role' => ['required', 'string', 'in:admin,staff'],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'max:64',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d!@#$%^&*_.?-]+$/',
            ],
        ], [
            'name.regex' => 'Name may only contain letters, spaces, Ñ, ñ, hyphen, apostrophe, and period.',
            'name.min' => 'Name must be at least 2 characters.',
            'name.max' => 'Name must not exceed 100 characters.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email must not exceed 255 characters.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must not exceed 64 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.regex' => 'Password must be 8-64 characters and include uppercase, lowercase, and number. Only common symbols are allowed.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('editUserId', $user->id);
        }

        $validated = $validator->validated();

        if ($this->isPrimaryAdmin($user) && isset($validated['role']) && $validated['role'] !== $user->role) {
            return back()
                ->withErrors([
                    'role' => 'The primary admin role cannot be changed.',
                ])
                ->withInput();
        }

        $user->name = trim($validated['name']);
        $user->email = strtolower(trim($validated['email']));
        $user->role = $validated['role'];

        if (isset($validated['password']) && is_string($validated['password']) && $validated['password'] !== '') {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if ($request->user() && (int) $request->user()->id === (int) $user->id) {
            $request->session()->put('auth_role', (string) ($user->role ?? ''));
        }

        if ($request->user() && (int) $request->user()->id === (int) $user->id && isset($validated['password']) && is_string($validated['password']) && $validated['password'] !== '') {
            $request->session()->put('auth_password_hash', (string) ($user->password ?? ''));
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('status', 'password-updated');
        }

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->withErrors([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        if (strtolower((string) ($user->role ?? '')) === 'admin') {
            return back()->withErrors([
                'user' => 'Admin accounts cannot be deleted.',
            ]);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }
}
