<?php
// Simple login test script that bypasses Laravel's middleware

// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Start output buffering to capture any errors
ob_start();

// If it's an OPTIONS request, just return headers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST requests are allowed'
    ]);
    exit;
}

// Get the JSON data from the request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!isset($data['personal_id']) || !isset($data['password'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Personal ID and password are required'
    ]);
    exit;
}

// Include the Laravel bootstrap file to access the database
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the user from the database (case-insensitive)
$user = \App\Models\User::whereRaw('UPPER(personal_id) = ?', [strtoupper($data['personal_id'])])->first();

// Debug information
$debug = [
    'personal_id_provided' => $data['personal_id'],
    'password_provided' => $data['password'],
    'user_found' => $user ? true : false,
    'password_hash' => $user ? $user->password : null,
];

// Check credentials
if (!$user || !\Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid credentials',
        'debug' => $debug
    ]);
    exit;
}

// Generate a token
$token = $user->createToken('auth-token')->plainTextToken;

// Return success response
$response = [
    'status' => 'success',
    'message' => 'Login successful',
    'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'personal_id' => $user->personal_id,
        'role' => $user->role
    ],
    'token' => $token,
    'debug' => $debug
];

// Get any output from the buffer
$output = ob_get_clean();

// If there was any output, include it in the response
if (!empty($output)) {
    $response['php_output'] = $output;
}

echo json_encode($response);
