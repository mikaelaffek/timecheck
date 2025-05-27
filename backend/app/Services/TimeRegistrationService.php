<?php

namespace App\Services;

use App\Models\TimeRegistration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
        
        // Generate random coordinates if not provided
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            $coordinates = $this->Recent Time Registrations();
            $data['latitude'] = $coordinates['latitude'];
            $data['longitude'] = $coordinates['longitude'];
            
            Log::info('Generated random coordinates for clock-in', [
                'user_id' => $userId,
                'coordinates' => $coordinates
            ]);
        }
        
        // Create the time registration
        return TimeRegistration::create([
            'user_id' => $userId,
            'date' => $today,
            'clock_in' => $now->format('H:i:s'),
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'status' => 'pending',
        ]);
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
        
        // Generate random coordinates if not provided
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            $coordinates = $this->generateRandomSwedishCoordinates();
            $data['latitude'] = $coordinates['latitude'];
            $data['longitude'] = $coordinates['longitude'];
            
            Log::info('Generated random coordinates for clock-out', [
                'user_id' => $timeRegistration->user_id,
                'time_registration_id' => $timeRegistration->id,
                'coordinates' => $coordinates
            ]);
        }
        
        // Update the time registration with clock-out time and coordinates
        $timeRegistration->clock_out = $now->format('H:i:s');
        $timeRegistration->latitude = $data['latitude'] ?? $timeRegistration->latitude;
        $timeRegistration->longitude = $data['longitude'] ?? $timeRegistration->longitude;
        
        // Calculate and store total hours
        $totalHours = $timeRegistration->calculateTotalHours();
        if ($totalHours !== null) {
            $timeRegistration->total_hours = $totalHours;
            
            Log::info('Calculated total hours for time registration', [
                'time_registration_id' => $timeRegistration->id,
                'user_id' => $timeRegistration->user_id,
                'clock_in' => $timeRegistration->clock_in,
                'clock_out' => $timeRegistration->clock_out,
                'total_hours' => $totalHours
            ]);
        }
        
        $timeRegistration->save();
        
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
}
