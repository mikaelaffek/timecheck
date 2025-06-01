<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdateSettingsRequest;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);
        
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
    public function store(StoreUserRequest $request)
    {
        $newUser = User::create([
            ...$request->only(['name', 'email', 'personal_id', 'role']),
            'password' => Hash::make($request->password),
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
        $this->authorize('view', $user);
        
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());
        
        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);
        
        $user->delete();
        
        return response()->json(['message' => 'User deleted']);
    }
    
    /**
     * Update the user's profile.
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        
        $user->update($request->validated());
        
        return response()->json($user);
    }
    
    /**
     * Update the user's password.
     */
    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = $request->user();
        
        // Validate current password
        $request->validateCurrentPassword();
        
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
    public function updateSettings(UpdateSettingsRequest $request)
    {
        $user = $request->user();
        $settings = $user->settings;
        
        if (!$settings) {
            // Create settings if they don't exist
            $settings = UserSetting::create([
                'user_id' => $user->id,
            ]);
        }
        
        $settings->update($request->validated());
        
        return response()->json($settings);
    }
}
