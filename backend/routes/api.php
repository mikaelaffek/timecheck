<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\OvertimeRuleController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\TimeRegistrationController;
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

// Test endpoint for schedules
Route::get('/test-schedules', function() {
    // Get all schedules
    $schedules = \App\Models\Schedule::orderBy('date', 'desc')
        ->limit(5)
        ->get();
    
    return response()->json([
        'status' => 'success',
        'count' => $schedules->count(),
        'data' => $schedules
    ]);
});

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
    
    // Time registrations
    Route::apiResource('time-registrations', TimeRegistrationController::class);
    Route::post('/time-registrations/clock-in', [TimeRegistrationController::class, 'clockIn']);
    Route::post('/time-registrations/clock-out', [TimeRegistrationController::class, 'clockOut']);
    Route::get('/time-registrations/recent', [TimeRegistrationController::class, 'recent']);
    
    // Schedules
    Route::apiResource('schedules', ScheduleController::class);
    Route::get('/schedules/date/{date}', [ScheduleController::class, 'getByDate']);
    Route::get('/schedules/current-week', [ScheduleController::class, 'getCurrentWeek']);
    Route::post('/schedules/generate-recurring', [ScheduleController::class, 'generateRecurring']);
    
    // Reports
    Route::post('/reports/time', [ReportController::class, 'generateTimeReport']);
    Route::post('/reports/staff-registry', [ReportController::class, 'generateStaffRegistry']);
    Route::get('/reports/recent', [ReportController::class, 'getRecentReports']);
    Route::get('/reports/download/{report}', [ReportController::class, 'downloadReport']);
    
    // Overtime rules
    Route::apiResource('overtime-rules', OvertimeRuleController::class);
    Route::get('/overtime-rules/active', [OvertimeRuleController::class, 'getActiveRules']);
    
    // Locations
    Route::apiResource('locations', LocationController::class);
    
    // Departments
    Route::apiResource('departments', DepartmentController::class);
    Route::get('/locations/nearby', [LocationController::class, 'getNearby']);
    
    // Users (admin only)
    Route::apiResource('users', UserController::class);
});
