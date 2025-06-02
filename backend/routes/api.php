<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\TimeRegistrationController;
use App\Http\Controllers\Api\AdminTimeRegistrationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Simple test endpoint to verify API routing
Route::get('/test', function() {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working!',
        'time' => now()->toDateTimeString()
    ]);
});

// Test endpoint for time registrations
Route::get('/test-time-registrations', function() {
    // Get all time registrations
    $timeRegistrations = \App\Models\TimeRegistration::orderBy('date', 'desc')
        ->orderBy('clock_in', 'desc')
        ->limit(5)
        ->get();
    
    return response()->json([
        'status' => 'success',
        'count' => $timeRegistrations->count(),
        'data' => $timeRegistrations
    ]);
});

// Test endpoint for schedules has been removed

// Test login endpoint (direct DB access)
Route::post('/test-login', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'personal_id' => 'required|string',
        'password' => 'required|string',
    ]);

    $user = \App\Models\User::where('personal_id', $request->personal_id)->first();

    if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid credentials',
            'debug' => [
                'personal_id_exists' => (bool)$user,
                'personal_id_provided' => $request->personal_id,
                'password_provided' => $request->password,
                'password_hash' => $user ? $user->password : null
            ]
        ], 401);
    }

    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json([
        'status' => 'success',
        'user' => $user,
        'token' => $token,
    ]);
});

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // User profile and settings
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/password', [UserController::class, 'updatePassword']);
    Route::get('/user/settings', [UserController::class, 'getSettings']);
    Route::put('/user/settings', [UserController::class, 'updateSettings']);
    
    // Time registrations - IMPORTANT: special routes must be defined BEFORE the resource route
    // Status endpoint
    Route::get('/time-registrations/status', [TimeRegistrationController::class, 'status']);
    // Clock in/out endpoints
    Route::post('/time-registrations/clock-in', [TimeRegistrationController::class, 'clockIn']);
    Route::post('/time-registrations/clock-out', [TimeRegistrationController::class, 'clockOut']);
    // Check clock in status
    Route::get('/check-clock-in-status', [TimeRegistrationController::class, 'isClockIn']);
    // Recent time registrations
    Route::get('/recent-time-registrations', [TimeRegistrationController::class, 'recent']);
    // CRUD resource routes - must be AFTER all special routes
    Route::apiResource('time-registrations', TimeRegistrationController::class);
    
    // Schedule, Report, and OvertimeRule routes have been removed
    
    // Location routes removed - controller not used in the application
    // The Location model is still used in relationships
    
    // Users (admin only)
    Route::apiResource('users', UserController::class);
    
    // Admin time registrations - specific endpoint that includes user data
    Route::get('/admin/time-registrations', [AdminTimeRegistrationController::class, 'index']);
});
