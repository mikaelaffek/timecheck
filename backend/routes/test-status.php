<?php
// Simple test endpoint to verify the status API is accessible
use Illuminate\Support\Facades\Route;

Route::get('/test-status', function() {
    return response()->json([
        'status' => 'success',
        'message' => 'Status API test endpoint is working!',
        'time' => now()->toDateTimeString()
    ]);
});
