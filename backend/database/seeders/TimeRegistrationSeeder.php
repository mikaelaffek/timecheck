<?php

namespace Database\Seeders;

use App\Models\TimeRegistration;
use App\Models\User;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TimeRegistrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing time registrations
        TimeRegistration::truncate();
        
        // Create sample employee data if it doesn't exist
        $this->createSampleEmployees();
        
        // Get all employees
        $employees = User::where('role', 'employee')->get();
        
        // Current date
        $now = Carbon::now();
        
        // Create time registrations for the past month
        $startDate = $now->copy()->subMonth();
        $endDate = $now->copy();
        
        $currentDate = $startDate->copy();
        
        // Loop through each day of the past month
        while ($currentDate <= $endDate) {
            // Skip weekends
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                continue;
            }
            
            // For each employee, create time registrations
            foreach ($employees as $employee) {
                // 90% chance of having a time registration on a given day
                if (rand(1, 10) <= 9) {
                    // Random start time between 8:00 and 9:30
                    $startHour = rand(8, 9);
                    $startMinute = $startHour == 9 ? rand(0, 30) : rand(0, 59);
                    $clockIn = $currentDate->copy()->setTime($startHour, $startMinute);
                    
                    // Random end time between 16:00 and 18:00
                    $endHour = rand(16, 18);
                    $endMinute = rand(0, 59);
                    $clockOut = $currentDate->copy()->setTime($endHour, $endMinute);
                    
                    // Calculate total hours
                    $totalHours = $clockIn->floatDiffInHours($clockOut);
                    
                    // Random status (mostly approved, some pending, few rejected)
                    $statusOptions = ['approved', 'approved', 'approved', 'approved', 'pending', 'rejected'];
                    $status = $statusOptions[array_rand($statusOptions)];
                    
                    // Create time registration
                    TimeRegistration::create([
                        'user_id' => $employee->id,
                        'date' => $currentDate->format('Y-m-d'),
                        'clock_in' => $clockIn->format('H:i:s'),
                        'clock_out' => $clockOut->format('H:i:s'),
                        'total_hours' => round($totalHours, 2),
                        'status' => $status,
                    ]);
                }
            }
            
            // Move to next day
            $currentDate->addDay();
        }
        
        // Create a few active (clocked in, not clocked out) registrations for today
        $today = Carbon::now()->format('Y-m-d');
        
        // Select a few random employees to be currently clocked in
        $activeEmployees = $employees->random(min(3, count($employees)));
        
        foreach ($activeEmployees as $employee) {
            // Clock in between 8:00 and 10:00
            $startHour = rand(8, 10);
            $startMinute = rand(0, 59);
            $clockIn = Carbon::today()->setTime($startHour, $startMinute);
            
            TimeRegistration::create([
                'user_id' => $employee->id,
                'date' => $today,
                'clock_in' => $clockIn->format('H:i:s'),
                'clock_out' => null,
                'total_hours' => null,
                'status' => 'pending',
            ]);
        }
    }
    
    /**
     * Create sample employees if they don't exist
     */
    private function createSampleEmployees(): void
    {
        // Check if we already have enough employees
        if (User::where('role', 'employee')->count() >= 5) {
            return;
        }
        
        // Sample employee data
        $employees = [
            [
                'name' => 'Anders Olsson',
                'personal_id' => 'EMP001',
                'email' => 'anders@example.com',
                'role' => 'employee'
            ],
            [
                'name' => 'Emma Viklund',
                'personal_id' => 'EMP002',
                'email' => 'emma@example.com',
                'role' => 'employee'
            ],
            [
                'name' => 'Erika Eliasson',
                'personal_id' => 'EMP003',
                'email' => 'erika@example.com',
                'role' => 'employee'
            ],
            [
                'name' => 'Hans Bergman',
                'personal_id' => 'EMP004',
                'email' => 'hans@example.com',
                'role' => 'employee'
            ],
            [
                'name' => 'Lisa Larsson',
                'personal_id' => 'EMP005',
                'email' => 'lisa@example.com',
                'role' => 'employee'
            ],
            [
                'name' => 'Ludwig Lindström',
                'personal_id' => 'EMP006',
                'email' => 'ludwig@example.com',
                'role' => 'employee'
            ],
            [
                'name' => 'Sarah Nyberg',
                'personal_id' => 'EMP007',
                'email' => 'sarah@example.com',
                'role' => 'employee'
            ],
        ];
        
        // Create each employee
        foreach ($employees as $employeeData) {
            // Skip if employee already exists
            if (User::where('personal_id', $employeeData['personal_id'])->exists()) {
                continue;
            }
            
            User::create([
                'name' => $employeeData['name'],
                'personal_id' => $employeeData['personal_id'],
                'email' => $employeeData['email'],
                'password' => bcrypt('password'),
                'role' => $employeeData['role'],
            ]);
        }
    }
}
