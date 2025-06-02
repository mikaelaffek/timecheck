<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Update the user's profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
        ]);
        
        $user->update($validated);
        
        return (new UserResource($user))->resolve();
    }
    
    /**
     * Update the user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }
        
        $user->password = Hash::make($validated['password']);
        $user->save();
        
        Log::info('User ' . $user->personal_id . ' successfully changed password');
        
        return response()->json([
            'message' => 'Password updated successfully'
        ], Response::HTTP_OK);
    }
    
    /**
     * Get the user's settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSettings()
    {
        $user = Auth::user();
        // Get user settings using the relationship
        $userSettings = $user->settings()->first();
        
        if (!$userSettings) {
            // Create default settings if none exist
            $userSettings = new UserSetting([
                'user_id' => $user->id,
                'enable_notifications' => true,
                'auto_clock_out' => false,
                'default_view' => 'day',
                'time_format' => '24h',
            ]);
            $userSettings->save();
        }
        
        return response()->json($userSettings, Response::HTTP_OK);
    }
    
    /**
     * Update the user's settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'enable_notifications' => 'sometimes|boolean',
            'auto_clock_out' => 'sometimes|boolean',
            'default_view' => 'sometimes|string|in:day,week,month',
            'time_format' => 'sometimes|string|in:12h,24h',
        ]);
        
        // Get user settings using the relationship
        $userSettings = $user->settings()->first();
        
        if (!$userSettings) {
            $userSettings = new UserSetting(['user_id' => $user->id]);
            $user->settings()->save($userSettings);
        }
        
        $userSettings->fill($validated);
        $userSettings->save();
        
        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $userSettings
        ], Response::HTTP_OK);
    }
}