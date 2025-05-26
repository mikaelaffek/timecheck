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
        // Create admin user (first user is you with your personal ID)
        $admin = User::create([
            'name' => 'Admin Mikael Affek',
            'email' => 'admin@timetjek.com',
            'personal_id' => '870531-4139',
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
            'personal_id' => '790215-3391',
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
        
        // Create employee users with Swedish personal ID format (YYMMDD-XXXX)
        $employees = [
            [
                'name' => 'John Doe',
                'email' => 'john@timetjek.com',
                'personal_id' => '850612-5578',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@timetjek.com',
                'personal_id' => '890423-6644',
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@timetjek.com',
                'personal_id' => '920718-4433',
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
