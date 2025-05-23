<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@timetjek.com',
            'personal_id' => 'ADMIN001',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        
        // Create admin user settings
        UserSetting::create([
            'user_id' => $admin->id,
            'enable_notifications' => true,
            'auto_clock_out' => false,
            'default_view' => 'dashboard',
            'time_format' => '24h',
        ]);
        
        // Create manager user
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@timetjek.com',
            'personal_id' => 'MGR001',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);
        
        // Create manager user settings
        UserSetting::create([
            'user_id' => $manager->id,
            'enable_notifications' => true,
            'auto_clock_out' => false,
            'default_view' => 'time-registrations',
            'time_format' => '24h',
        ]);
        
        // Create employee users
        $employees = [
            [
                'name' => 'John Doe',
                'email' => 'john@timetjek.com',
                'personal_id' => 'EMP001',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@timetjek.com',
                'personal_id' => 'EMP002',
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@timetjek.com',
                'personal_id' => 'EMP003',
            ],
        ];
        
        foreach ($employees as $employeeData) {
            $employee = User::create([
                'name' => $employeeData['name'],
                'email' => $employeeData['email'],
                'personal_id' => $employeeData['personal_id'],
                'password' => Hash::make('password'),
                'role' => 'employee',
            ]);
            
            // Create employee user settings
            UserSetting::create([
                'user_id' => $employee->id,
                'enable_notifications' => true,
                'auto_clock_out' => true,
                'default_view' => 'dashboard',
                'time_format' => '24h',
            ]);
        }
    }
}
