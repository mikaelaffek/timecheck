<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle a login request to the application.
     */
    public function login(LoginRequest $request)
    {
        $user = $request->authenticate();
        $token = $user->createToken('auth-token')->plainTextToken;

        // Using resolve() to remove the data wrapper
        return response()->json([
            'user' => (new UserResource($user))->resolve(),
            'token' => $token
        ]);
    }

    /**
     * Handle a logout request from the application.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ], Response::HTTP_OK);
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request)
    {
        // Using resolve() to remove the data wrapper and return just the user object
        return response()->json((new UserResource($request->user()->load('settings')))->resolve());
    }

    /**
     * Register a new user.
     */
    public function register(RegisterUserRequest $request)
    {
        $user = User::create([
            ...$request->only(['name', 'email', 'personal_id']),
            'password' => Hash::make($request->password),
            'role' => 'employee', // Default role
        ]);

        // Create default user settings
        UserSetting::create([
            'user_id' => $user->id,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;
        
        // Load the settings relationship for the resource
        $user->load('settings');

        // Using resolve() to remove the data wrapper
        return response()->json([
            'user' => (new UserResource($user))->resolve(),
            'token' => $token
        ], Response::HTTP_CREATED);
    }
}
