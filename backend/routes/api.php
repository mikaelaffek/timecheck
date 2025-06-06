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
