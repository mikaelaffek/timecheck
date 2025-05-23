<?php

// Simple script to test database connection and authentication
// Run this script from the command line: php auth-test.php

require __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection parameters from .env
$dbConnection = $_ENV['DB_CONNECTION'] ?? 'mysql';
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbDatabase = $_ENV['DB_DATABASE'] ?? 'laravel';
$dbUsername = $_ENV['DB_USERNAME'] ?? 'root';
$dbPassword = $_ENV['DB_PASSWORD'] ?? '';

echo "Testing database connection...\n";
echo "Connection: $dbConnection\n";
echo "Host: $dbHost\n";
echo "Port: $dbPort\n";
echo "Database: $dbDatabase\n";
echo "Username: $dbUsername\n";
echo "Password: " . (empty($dbPassword) ? "(empty)" : "(set)") . "\n\n";

try {
    // Create PDO connection
    $dsn = "$dbConnection:host=$dbHost;port=$dbPort;dbname=$dbDatabase";
    $pdo = new PDO($dsn, $dbUsername, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection successful!\n\n";
    
    // Test query to get users
    echo "Fetching users from database...\n";
    $stmt = $pdo->query("SELECT id, name, email, personal_id, password, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) === 0) {
        echo "No users found in the database.\n";
    } else {
        echo "Found " . count($users) . " users:\n";
        
        foreach ($users as $user) {
            echo "ID: " . $user['id'] . "\n";
            echo "Name: " . $user['name'] . "\n";
            echo "Email: " . $user['email'] . "\n";
            echo "Personal ID: " . $user['personal_id'] . "\n";
            echo "Role: " . $user['role'] . "\n";
            echo "Password Hash: " . substr($user['password'], 0, 20) . "...\n\n";
        }
        
        // Test authentication with hardcoded credentials
        echo "Testing authentication with ADMIN001/password...\n";
        $personalId = 'ADMIN001';
        $password = 'password';
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE personal_id = ?");
        $stmt->execute([$personalId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "User with personal_id '$personalId' not found!\n";
        } else {
            // Verify password using PHP's password_verify
            if (password_verify($password, $user['password'])) {
                echo "Authentication successful for user: " . $user['name'] . "\n";
            } else {
                echo "Authentication failed: Invalid password for user: " . $user['name'] . "\n";
                echo "Expected password: 'password'\n";
                echo "Stored hash: " . $user['password'] . "\n";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
