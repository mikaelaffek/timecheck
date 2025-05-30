<?php

// Connect to the database using the Laravel environment variables
$host = 'db';
$database = 'timetjek';
$username = 'timetjek';
$password = 'timetjek';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Count records before truncating
    $countStmt = $pdo->query("SELECT COUNT(*) as count FROM time_registrations");
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $beforeCount = $countResult['count'];
    
    // Truncate the time_registrations table
    // Using DELETE instead of TRUNCATE to maintain foreign key constraints
    $pdo->exec("DELETE FROM time_registrations");
    
    echo "Successfully deleted all $beforeCount time registrations from the database.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
