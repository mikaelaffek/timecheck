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
        // Get employees
        $employees = User::where('role', 'employee')->get();
        
        // Current date
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        
        // Make sure we're using the current date for seeding
        $currentDate = Carbon::now()->toDateString();
        
        // Create time registrations based on schedules
        foreach ($employees as $employee) {
            // Get schedules for this employee
            $schedules = Schedule::where('user_id', $employee->id)
                ->where('date', '<', $now->toDateString())
                ->orderBy('date')
                ->get();
            
            foreach ($schedules as $schedule) {
                try {
                    // Random variation in clock-in time (0-15 minutes early or late)
                    $clockInVariation = rand(-15, 15);
                    $clockIn = Carbon::parse($schedule->date . ' ' . $schedule->start_time)
                        ->addMinutes($clockInVariation);
                    
                    // Random variation in clock-out time (0-15 minutes early or late)
                    $clockOutVariation = rand(-15, 15);
                    $clockOut = Carbon::parse($schedule->date . ' ' . $schedule->end_time)
                        ->addMinutes($clockOutVariation);
                    
                    // Create time registration
                    TimeRegistration::create([
                    'user_id' => $employee->id,
                    'date' => $schedule->date,
                    'clock_in' => $clockIn->format('H:i:s'),
                    'clock_out' => $clockOut->format('H:i:s'),
                    'latitude' => $schedule->location ? $schedule->location->latitude : null,
                    'longitude' => $schedule->location ? $schedule->location->longitude : null,
                    'status' => 'approved',
                    ]);
                } catch (\Exception $e) {
                    // Skip this record if there's a date parsing error
                    continue;
                }
            }
            
            // Create a time registration for today if there's a schedule
            $todaySchedule = Schedule::where('user_id', $employee->id)
                ->where('date', $now->toDateString())
                ->first();
            
            if ($todaySchedule) {
                try {
                    // Random variation in clock-in time (0-15 minutes early or late)
                    $clockInVariation = rand(-15, 15);
                    $clockIn = Carbon::parse($todaySchedule->date . ' ' . $todaySchedule->start_time)
                        ->addMinutes($clockInVariation);
                    
                    // Only create clock-out if the scheduled end time has passed
                    $clockOut = null;
                    if (Carbon::now()->gt(Carbon::parse($todaySchedule->date . ' ' . $todaySchedule->end_time))) {
                        // Random variation in clock-out time (0-15 minutes early or late)
                        $clockOutVariation = rand(-15, 15);
                        $clockOut = Carbon::parse($todaySchedule->date . ' ' . $todaySchedule->end_time)
                            ->addMinutes($clockOutVariation)
                            ->format('H:i:s');
                    }
                
                    // Create time registration for today
                    TimeRegistration::create([
                        'user_id' => $employee->id,
                        'date' => $todaySchedule->date,
                        'clock_in' => $clockIn->format('H:i:s'),
                        'clock_out' => $clockOut,
                        'latitude' => $todaySchedule->location ? $todaySchedule->location->latitude : null,
                        'longitude' => $todaySchedule->location ? $todaySchedule->location->longitude : null,
                        'status' => 'pending',
                    ]);
                } catch (\Exception $e) {
                    // Skip this record if there's a date parsing error
                    continue;
                }
            }
        }
    }
}
