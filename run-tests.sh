#!/bin/bash

# Run Laravel tests for the Timetjek API
echo "Running Timetjek API Tests..."
echo "=============================="

# Make sure the script is executable
chmod +x "$0"

# Run the tests in the Docker container
docker exec timetjek-backend-1 php artisan test --filter=TimeRegistrationTest
docker exec timetjek-backend-1 php artisan test --filter=AuthTest
docker exec timetjek-backend-1 php artisan test --filter=UserSettingsTest
docker exec timetjek-backend-1 php artisan test --filter=AdminTest
docker exec timetjek-backend-1 php artisan test --filter=LocationDepartmentTest

echo "=============================="
echo "Tests completed!"
