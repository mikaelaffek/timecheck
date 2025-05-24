<?php
// API login test script with detailed error reporting

// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
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

try {
    // Include the Laravel bootstrap file to access the application
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    
    // Create a request to the API login endpoint
    $request = \Illuminate\Http\Request::create('/api/login', 'POST', [
        'personal_id' => $data['personal_id'],
        'password' => $data['password']
    ]);
    
    // Set JSON headers
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('Accept', 'application/json');
    
    // Capture the response
    try {
        $response = $kernel->handle($request);
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();
        
        // Add debug information
        $debug = [
            'status_code' => $statusCode,
            'personal_id_provided' => $data['personal_id'],
            'password_provided' => $data['password'],
            'response_content' => $content
        ];
        
        // Get any output from the buffer
        $output = ob_get_clean();
        
        echo json_encode([
            'status' => $statusCode === 200 ? 'success' : 'error',
            'message' => $statusCode === 200 ? 'API login successful' : 'API login failed',
            'api_response' => json_decode($content, true),
            'debug' => $debug,
            'php_output' => $output ?: null
        ]);
        
    } catch (\Exception $e) {
        // Get any output from the buffer
        $output = ob_get_clean();
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Exception while handling the request',
            'exception' => [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ],
            'php_output' => $output ?: null
        ]);
    }
    
} catch (\Exception $e) {
    // Get any output from the buffer
    $output = ob_get_clean();
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception during bootstrap',
        'exception' => [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ],
        'php_output' => $output ?: null
    ]);
}
