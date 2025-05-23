<?php
// Simple test file to verify the backend is working
echo json_encode([
    'status' => 'success',
    'message' => 'Backend is running!',
    'time' => date('Y-m-d H:i:s')
]);
