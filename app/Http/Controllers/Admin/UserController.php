<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of newsroom staff and administrative users.
     */
    public function index(Request $request): View
    {
        $query = User::with('roles')->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $roleSlug = $request->input('role');
            $query->whereHas('roles', fn ($q) => $q->where('slug', $roleSlug));
        }

        if ($request->filled('status')) {
            $isActive = $request->input('status') === 'active';
            $query->where('is_active', $isActive);
        }

        $users = $query->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'roles', 'stats'));
    }

    /**
     * Show the form for creating a new user or editorial journalist.
     */
    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'job_title' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:50',
            'bio' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'job_title' => $validated['job_title'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        $user->roles()->sync($validated['role_ids']);

        return redirect()->route('admin.users.index')->with('success', "Newsroom user '{$user->name}' was created successfully!");
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'job_title' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:50',
            'bio' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'job_title' => $validated['job_title'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        // Prevent self-deactivation
        if (auth()->id() !== $user->id) {
            $updateData['is_active'] = $request->boolean('is_active');
        }

        $user->update($updateData);
        $user->roles()->sync($validated['role_ids']);

        return redirect()->route('admin.users.index')->with('success', "User '{$user->name}' was updated successfully!");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'You cannot delete your own super administrator account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "User '{$name}' was removed from the system.");
    }

    /**
     * Toggle the active status of a user.
     */
    public function toggleActive(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "User account {$status}.");
    }
}
