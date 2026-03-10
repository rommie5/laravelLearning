<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request)
    {
        return Inertia::render('Profile/Edit', [
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information and avatar.
     */
    public function update(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('=== PROFILE UPDATE INITIATED ===', $request->all());
        $user = Auth::user();

        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
                'initials' => ['nullable', 'string', 'max:5'],
                'avatar_type' => ['required', 'string', 'in:image,initials'],
                'avatar' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Profile Validation Failed', $e->errors());
            throw $e;
        }

        \Illuminate\Support\Facades\Log::info('Profile Validation Passed');

        // Let Eloquent handle the mass assignment for everything seamlessly
        $user->fill($request->only(['name', 'email']));
        
        // Handle initials separately to uppercase them
        if ($request->has('initials')) {
            $user->initials = $request->initials ? strtoupper(substr($request->initials, 0, 5)) : null;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Handle explicit Avatar Preference routing
        if ($request->avatar_type === 'initials') {
            if ($user->avatar) {
                \Illuminate\Support\Facades\Storage::delete('public/avatars/' . $user->avatar);
                $user->avatar = null;
            }
        } else {
            // Handle Avatar Upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar if it exists
                if ($user->avatar) {
                    \Illuminate\Support\Facades\Storage::delete('public/avatars/' . $user->avatar);
                }
                
                // Store new avatar
                $file = $request->file('avatar');
                $filename = $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/avatars', $filename);
                
                $user->avatar = $filename;
            }
        }

        $user->save();

        return redirect()->back()->with('status', 'profile-updated');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('status', 'password-updated');
    }
}
