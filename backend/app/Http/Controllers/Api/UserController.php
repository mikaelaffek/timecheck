<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Only admins and managers can view all users
        if (!$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $query = User::query();
        
        // Filter by role if specified
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }
        
        return $query->orderBy('name')->paginate($request->per_page ?? 15);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Only admins can create users
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'personal_id' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:employee,manager,admin',
        ]);
        
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'personal_id' => $request->personal_id,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);
        
        // Create default user settings
        UserSetting::create([
            'user_id' => $newUser->id,
        ]);
        
        return response()->json($newUser, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        // Users can view their own profile, admins and managers can view any profile
        if ($user->id !== $currentUser->id && !$currentUser->isAdmin() && !$currentUser->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        // Users can update their own profile, admins can update any profile
        if ($user->id !== $currentUser->id && !$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'personal_id' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'role' => 'sometimes|in:employee,manager,admin',
        ]);
        
        // Only admins can change roles
        if ($request->has('role') && !$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized to change role'], 403);
        }
        
        $user->update($request->all());
        
        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        // Only admins can delete users, and they cannot delete themselves
        if (!$currentUser->isAdmin() || $user->id === $currentUser->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $user->delete();
        
        return response()->json(['message' => 'User deleted']);
    }
    
    /**
     * Update the user's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);
        
        $user->update($request->only(['name', 'email']));
        
        return response()->json($user);
    }
    
    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);
        
        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }
        
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);
        
        return response()->json(['message' => 'Password updated successfully']);
    }
    
    /**
     * Get the user's settings.
     */
    public function getSettings(Request $request)
    {
        $user = $request->user();
        $settings = $user->settings;
        
        if (!$settings) {
            // Create default settings if they don't exist
            $settings = UserSetting::create([
                'user_id' => $user->id,
            ]);
        }
        
        return response()->json($settings);
    }
    
    /**
     * Update the user's settings.
     */
    public function updateSettings(Request $request)
    {
        $user = $request->user();
        $settings = $user->settings;
        
        if (!$settings) {
            // Create settings if they don't exist
            $settings = UserSetting::create([
                'user_id' => $user->id,
            ]);
        }
        
        $request->validate([
            'enable_notifications' => 'sometimes|boolean',
            'auto_clock_out' => 'sometimes|boolean',
            'default_view' => 'sometimes|string|in:dashboard,time-registrations,reports',
            'time_format' => 'sometimes|string|in:12h,24h',
        ]);
        
        $settings->update($request->all());
        
        return response()->json($settings);
    }
}
