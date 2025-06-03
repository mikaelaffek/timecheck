<?php

namespace App\Services;

use App\Events\TimeRegistrationEvent;
use App\Models\TimeRegistration;
use App\Models\User;
use App\Traits\TimeRegistrationTrait;
use Carbon\Carbon;

class TimeRegistrationService
{
    use TimeRegistrationTrait;
    // Methods moved to TimeRegistrationTrait
    
    /**
     * Check for overlapping time registrations.
     * 
     * @param int $userId
     * @param string $date
     * @param string $clockIn
     * @param string|null $clockOut
     * @param int|null $excludeId
     * @return TimeRegistration|null
     */
    public function checkOverlappingRegistrations(int $userId, string $date, string $clockIn, ?string $clockOut, ?int $excludeId = null): ?TimeRegistration
    {
        $query = TimeRegistration::where('user_id', $userId)
            ->whereDate('date', $date);
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        // If clock-out is not set, only check for registrations with no clock-out
        if (!$clockOut) {
            $overlapping = $query->whereNull('clock_out')->first();
            if ($overlapping) {
                return $overlapping;
            }
            
            // Also check if the clock-in time is between any existing registration's clock-in and clock-out
            return $query->where(function ($q) use ($clockIn) {
                $q->where('clock_in', '<=', $clockIn)
                  ->where('clock_out', '>=', $clockIn);
            })->first();
        }
        
        // Check for any overlapping registrations
        return $query->where(function ($q) use ($clockIn, $clockOut) {
            // New registration starts during an existing one
            $q->where('clock_in', '<=', $clockIn)
              ->where('clock_out', '>=', $clockIn);
        })->orWhere(function ($q) use ($clockIn, $clockOut) {
            // New registration ends during an existing one
            $q->where('clock_in', '<=', $clockOut)
              ->where('clock_out', '>=', $clockOut);
        })->orWhere(function ($q) use ($clockIn, $clockOut) {
            // New registration completely contains an existing one
            $q->where('clock_in', '>=', $clockIn)
              ->where('clock_out', '<=', $clockOut);
        })->first();
    }
    
    /**
     * Get the status of a user's time registration for today.
     * 
     * @param int $userId
     * @return array
     */
    // getUserTimeRegistrationStatus method moved to TimeRegistrationTrait
}
