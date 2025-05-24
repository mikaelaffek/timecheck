<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to Timetjek API',
        'status' => 'running',
        'timestamp' => now()->toDateTimeString()
    ]);
});

// Test route
Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Backend is running!',
        'time' => now()->toDateTimeString()
    ]);
});

// Direct login endpoint (bypassing API routes)
Route::post('/direct-login', function (Request $request) {
    $request->validate([
        'personal_id' => 'required|string',
        'password' => 'required|string',
    ]);

    // Make personal_id case-insensitive by using whereRaw with UPPER function
    $user = User::whereRaw('UPPER(personal_id) = ?', [strtoupper($request->personal_id)])->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid credentials',
            'debug' => [
                'personal_id_exists' => (bool)$user,
                'personal_id_provided' => $request->personal_id,
                'personal_id_uppercase' => strtoupper($request->personal_id),
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
})->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

// List all users (for debugging)
Route::get('/users', function () {
    $users = User::all(['id', 'name', 'email', 'personal_id', 'role']);
    return response()->json($users);
});
