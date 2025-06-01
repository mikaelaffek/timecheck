<?php

namespace App\Services;

use App\Events\TimeRegistrationEvent;
use App\Models\TimeRegistration;
use App\Models\User;
use Carbon\Carbon;

class TimeRegistrationService
{
    /**
     * Generate random coordinates within Sweden's bounds.
     * 
     * @return array
     */
    public function generateRandomSwedishCoordinates(): array
    {
        // Sweden's approximate bounds
        $minLat = 55.0;
        $maxLat = 69.0;
        $minLng = 11.0;
        $maxLng = 24.0;
        
        // Generate random values within the bounds
        $latitude = $minLat + mt_rand() / mt_getrandmax() * ($maxLat - $minLat);
        $longitude = $minLng + mt_rand() / mt_getrandmax() * ($maxLng - $minLng);
        
        // Round to 6 decimal places
        return [
            'latitude' => round($latitude, 6),
            'longitude' => round($longitude, 6)
        ];
    }
    
    /**
     * Check if a user is already clocked in for today.
     * 
     * @param int $userId
     * @return TimeRegistration|null
     */
    public function getUserActiveTimeRegistration(int $userId): ?TimeRegistration
    {
        $today = Carbon::now()->toDateString();
        
        // Find any active registration for today (no clock-out time)
        return TimeRegistration::where('user_id', $userId)
            ->whereDate('date', $today)
            ->whereNull('clock_out')
            ->first();
    }
    
    /**
     * Clock in a user.
     * 
     * @param int $userId
     * @param array $data Additional data for the time registration
     * @return TimeRegistration
     */
    public function clockInUser(int $userId, array $data = []): TimeRegistration
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        
        // Get user for event dispatching
        $user = User::find($userId);
        
        // Generate random coordinates if not provided
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            $coordinates = $this->generateRandomSwedishCoordinates();
            $data['latitude'] = $coordinates['latitude'];
            $data['longitude'] = $coordinates['longitude'];
            
            // Dispatch event instead of direct logging
            event(new TimeRegistrationEvent('generated_coordinates', [
                'coordinates' => $coordinates
            ], $user));
        }
        
        // Create the time registration with only the necessary data
        $timeRegistration = TimeRegistration::create([
            'user_id' => $userId,
            'date' => $today,
            'clock_in' => $now->format('H:i:s'),
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'status' => 'pending',
        ]);
        
        // Dispatch event for the clock-in action
        event(new TimeRegistrationEvent('user_clocked_in_service', [
            'date' => $today,
            'time' => $now->format('H:i:s')
        ], $user, $timeRegistration));
        
        return $timeRegistration;
    }
    
    /**
     * Clock out a user.
     * 
     * @param TimeRegistration $timeRegistration
     * @param array $data Additional data for the time registration
     * @return TimeRegistration
     */
    public function clockOutUser(TimeRegistration $timeRegistration, array $data = []): TimeRegistration
    {
        $now = Carbon::now();
        
        // Get user for event dispatching
        $user = User::find($timeRegistration->user_id);
        
        // Generate random coordinates if not provided
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            $coordinates = $this->generateRandomSwedishCoordinates();
            $data['latitude'] = $coordinates['latitude'];
            $data['longitude'] = $coordinates['longitude'];
            
            // Dispatch event instead of direct logging
            event(new TimeRegistrationEvent('generated_coordinates_for_clockout', [
                'coordinates' => $coordinates
            ], $user, $timeRegistration));
        }
        
        // Update the time registration with clock-out time and coordinates
        $timeRegistration->update([
            'clock_out' => $now->format('H:i:s'),
            'latitude_end' => $data['latitude'],
            'longitude_end' => $data['longitude'],
        ]);
        
        // Calculate and store total hours
        $totalHours = $timeRegistration->calculateTotalHours();
        if ($totalHours !== null) {
            $timeRegistration->total_hours = $totalHours;
            
            // Dispatch event instead of direct logging
            event(new TimeRegistrationEvent('calculated_total_hours', [
                'time_registration_id' => $timeRegistration->id,
                'clock_in' => $timeRegistration->clock_in,
                'clock_out' => $timeRegistration->clock_out,
                'total_hours' => $totalHours
            ], $user, $timeRegistration));
        }
        
        $timeRegistration->save();
        
        // Dispatch event for the clock-out action
        event(new TimeRegistrationEvent('user_clocked_out_service', [
            'date' => $timeRegistration->date,
            'time' => $now->format('H:i:s')
        ], $user, $timeRegistration));
        
        return $timeRegistration;
    }
    
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
    public function getUserTimeRegistrationStatus(int $userId): array
    {
        $activeRegistration = $this->getUserActiveTimeRegistration($userId);
        
        // Format the clock-in time as a string for consistent frontend display
        $clockInTime = null;
        if ($activeRegistration && $activeRegistration->clock_in) {
            // Format as HH:MM:SS string
            $clockInTime = $activeRegistration->clock_in->format('H:i:s');
        }
        
        $duration = null;
        if ($activeRegistration && $activeRegistration->clock_in && $activeRegistration->clock_out) {
            $clockIn = Carbon::parse($activeRegistration->clock_in);
            $clockOut = Carbon::parse($activeRegistration->clock_out);
            $diffInMinutes = $clockOut->diffInMinutes($clockIn, true);
            $hours = floor($diffInMinutes / 60);
            $minutes = $diffInMinutes % 60;
            $duration = $hours . 'h ' . $minutes . 'm';
        }
        
        return [
            'clocked_in' => (bool)$activeRegistration,
            'clock_in_time' => $clockInTime,
            'duration' => $duration,
            'time_registration' => $activeRegistration
        ];
    }
}
