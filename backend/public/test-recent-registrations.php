<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the autoloader
require __DIR__ . '/../vendor/autoload.php';

// Load the .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Connect to the database
$host = $_ENV['DB_HOST'];
$database = $_ENV['DB_DATABASE'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully<br>";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get user ID (using admin user for testing)
$stmt = $pdo->prepare("SELECT id FROM users WHERE personal_id = 'ADMIN001'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Admin user not found");
}

$userId = $user['id'];
echo "Using user ID: $userId<br>";

// Get recent time registrations
$stmt = $pdo->prepare("
    SELECT * FROM time_registrations 
    WHERE user_id = :user_id 
    ORDER BY date DESC, clock_in DESC 
    LIMIT 5
");
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display results
echo "<h2>Recent Time Registrations</h2>";

if (empty($registrations)) {
    echo "No recent time registrations found";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Date</th><th>Clock In</th><th>Clock Out</th><th>Status</th></tr>";
    
    foreach ($registrations as $reg) {
        echo "<tr>";
        echo "<td>" . $reg['id'] . "</td>";
        echo "<td>" . $reg['date'] . "</td>";
        echo "<td>" . $reg['clock_in'] . "</td>";
        echo "<td>" . ($reg['clock_out'] ?? 'N/A') . "</td>";
        echo "<td>" . $reg['status'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Test the API endpoint directly
echo "<h2>Testing API Endpoint</h2>";

// Create a token for the admin user
$stmt = $pdo->prepare("DELETE FROM personal_access_tokens WHERE tokenable_id = :user_id");
$stmt->bindParam(':user_id', $userId);
$stmt->execute();

// Generate a random token
$token = bin2hex(random_bytes(20));
$tokenHash = hash('sha256', $token);

// Store the token in the database
$stmt = $pdo->prepare("
    INSERT INTO personal_access_tokens (tokenable_type, tokenable_id, name, token, abilities, created_at, updated_at) 
    VALUES ('App\\Models\\User', :user_id, 'test-token', :token, '[\"*\"]', NOW(), NOW())
");
$stmt->bindParam(':user_id', $userId);
$stmt->bindParam(':token', $tokenHash);
$stmt->execute();
$tokenId = $pdo->lastInsertId();

echo "Created token ID: $tokenId<br>";
echo "Token: $token<br>";

// Make a curl request to the API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/time-registrations/recent");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $userId . "|" . $token,
    "Accept: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "API Response (HTTP $httpCode):<br>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
