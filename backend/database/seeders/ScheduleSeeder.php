<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\User;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get employees
        $employees = User::where('role', 'employee')->get();
        
        // Get locations
        $locations = Location::all();
        
        // Current date
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        
        // Create schedules for the current week
        foreach ($employees as $employee) {
            // Assign to a random location
            $location = $locations->random();
            
            // Create schedules for weekdays (Monday to Friday)
            for ($i = 0; $i < 5; $i++) {
                $date = $startOfWeek->copy()->addDays($i);
                
                Schedule::create([
                    'user_id' => $employee->id,
                    'date' => $date->toDateString(),
                    'start_time' => '08:00:00',
                    'end_time' => '16:00:00',
                    'total_hours' => 8.0,
                    'location_id' => $location->id,
                    'is_recurring' => true,
                    'recurrence_pattern' => 'weekly',
                ]);
            }
        }
        
        // Create some special schedules
        $specialSchedules = [
            [
                'start_time' => '12:00:00',
                'end_time' => '20:00:00',
                'day_offset' => 2, // Wednesday
            ],
            [
                'start_time' => '16:00:00',
                'end_time' => '00:00:00',
                'day_offset' => 4, // Friday
            ],
        ];
        
        // Assign special schedules to a random employee
        $randomEmployee = $employees->random();
        $randomLocation = $locations->random();
        
        foreach ($specialSchedules as $scheduleData) {
            $date = $startOfWeek->copy()->addDays($scheduleData['day_offset']);
            
            // Delete any existing schedule for this employee on this date
            Schedule::where('user_id', $randomEmployee->id)
                ->where('date', $date->toDateString())
                ->delete();
            
            // Create the special schedule
            Schedule::create([
                'user_id' => $randomEmployee->id,
                'date' => $date->toDateString(),
                'start_time' => $scheduleData['start_time'],
                'end_time' => $scheduleData['end_time'],
                'total_hours' => 8.0, // This will be recalculated on save
                'location_id' => $randomLocation->id,
                'is_recurring' => false,
                'recurrence_pattern' => null,
            ]);
        }
    }
}
