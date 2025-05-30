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
    
    // Get all users to create time registrations for
    $stmt = $pdo->query("SELECT id, personal_id, name FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare insert statement for time registrations
    $insertStmt = $pdo->prepare(
        "INSERT INTO time_registrations 
        (user_id, date, clock_in, clock_out, total_hours, status, latitude, longitude, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
    );
    
    $count = 0;
    $statuses = ['approved', 'pending', 'rejected'];
    
    // Create time registrations for the past 7 days
    foreach ($users as $user) {
        // Skip creating entries for admin users if needed
        // if (strpos($user['personal_id'], 'ADMIN') === 0) continue;
        
        // Create entries for the past 7 days
        for ($i = 7; $i >= 0; $i--) {
            // Skip weekends (6 = Saturday, 0 = Sunday)
            $dayOfWeek = date('w', strtotime("-$i days"));
            if ($dayOfWeek == 0 || $dayOfWeek == 6) continue;
            
            $date = date('Y-m-d', strtotime("-$i days"));
            
            // Random clock in time between 7:00 and 9:30
            $clockInHour = rand(7, 9);
            $clockInMinute = $clockInHour == 9 ? rand(0, 30) : rand(0, 59);
            $clockIn = sprintf("%02d:%02d:00", $clockInHour, $clockInMinute);
            
            // Random clock out time between 15:30 and 18:00
            $clockOutHour = rand(15, 17);
            $clockOutMinute = $clockOutHour == 15 ? rand(30, 59) : rand(0, 59);
            $clockOut = sprintf("%02d:%02d:00", $clockOutHour, $clockOutMinute);
            
            // Calculate total hours (difference between clock in and clock out)
            $clockInTime = strtotime("$date $clockIn");
            $clockOutTime = strtotime("$date $clockOut");
            $totalHours = round(($clockOutTime - $clockInTime) / 3600, 2);
            
            // Generate random coordinates
            // Latitude range: -90 to 90
            // Longitude range: -180 to 180
            $latitude = rand(-900, 900) / 10;  // Divide by 10 to get decimal places
            $longitude = rand(-1800, 1800) / 10;
            
            // Random status (mostly approved)
            $status = rand(1, 10) <= 8 ? 'approved' : $statuses[array_rand([$statuses[1], $statuses[2]])];
            
            // Insert the record
            $insertStmt->execute([
                $user['id'],
                $date,
                $clockIn,
                $clockOut,
                $totalHours,
                $status,
                $latitude,
                $longitude
            ]);
            
            $count++;
        }
    }
    
    echo "Successfully created $count new time registrations with random coordinates.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
