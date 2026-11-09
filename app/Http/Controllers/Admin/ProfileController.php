<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the user profile settings and information form.
     */
    public function edit(): View
    {
        $user = auth()->user();

        return view('admin.profile.edit', compact('user'));
    }

    /**
     * Update the logged-in user profile information and settings.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'job_title' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:30',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:3072',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'job_title' => $validated['job_title'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ];

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $uploadDir = public_path('uploads/avatars');
            File::ensureDirectoryExists($uploadDir);

            $file = $request->file('avatar');
            $fileName = uniqid('avatar_', true).'.'.$file->getClientOriginalExtension();
            $file->move($uploadDir, $fileName);

            $updateData['avatar_path'] = '/uploads/avatars/'.$fileName;
        }

        // Handle password change if filled
        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('admin.profile.edit')->with('success', 'Your profile settings and account information have been updated.');
    }
}
