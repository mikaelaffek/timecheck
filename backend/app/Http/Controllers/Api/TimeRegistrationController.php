<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeRegistration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class TimeRegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = TimeRegistration::query();
        
        // If not admin or manager, only show own registrations
        if (!$user->isAdmin() && !$user->isManager()) {
            $query->where('user_id', $user->id);
        } else if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        // Date filtering
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } else if ($request->has('date')) {
            $query->where('date', $request->date);
        }
        
        // Status filtering
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        return $query->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc')
            ->paginate($request->per_page ?? 15);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'date' => 'required|date',
            'clock_in' => 'required|date_format:H:i:s',
            'clock_out' => 'nullable|date_format:H:i:s|after:clock_in',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);
        
        $user = $request->user();
        $userId = $request->user_id ?? $user->id;
        
        // Check if user has permission to create for other users
        if ($userId !== $user->id && !$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Check for overlapping time registrations
        $overlapping = $this->checkOverlappingRegistrations(
            $userId, 
            $request->date, 
            $request->clock_in, 
            $request->clock_out
        );
        
        if ($overlapping) {
            return response()->json([
                'message' => 'Time registration overlaps with existing registration',
                'overlapping' => $overlapping
            ], 422);
        }
        
        $timeRegistration = TimeRegistration::create([
            'user_id' => $userId,
            'date' => $request->date,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);
        
        return response()->json($timeRegistration, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, TimeRegistration $timeRegistration)
    {
        $user = $request->user();
        
        // Check if user has permission to view this registration
        if ($timeRegistration->user_id !== $user->id && !$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($timeRegistration);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TimeRegistration $timeRegistration)
    {
        $request->validate([
            'date' => 'sometimes|date',
            'clock_in' => 'sometimes|date_format:H:i:s',
            'clock_out' => 'nullable|date_format:H:i:s|after:clock_in',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,approved,rejected',
        ]);
        
        $user = $request->user();
        
        // Check if user has permission to update this registration
        if ($timeRegistration->user_id !== $user->id && !$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Only admins and managers can change status
        if ($request->has('status') && !$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized to change status'], 403);
        }
        
        // Check for overlapping time registrations if changing date or times
        if ($request->has('date') || $request->has('clock_in') || $request->has('clock_out')) {
            $date = $request->date ?? $timeRegistration->date;
            $clockIn = $request->clock_in ?? $timeRegistration->clock_in;
            $clockOut = $request->clock_out ?? $timeRegistration->clock_out;
            
            $overlapping = $this->checkOverlappingRegistrations(
                $timeRegistration->user_id, 
                $date, 
                $clockIn, 
                $clockOut,
                $timeRegistration->id
            );
            
            if ($overlapping) {
                return response()->json([
                    'message' => 'Time registration overlaps with existing registration',
                    'overlapping' => $overlapping
                ], 422);
            }
        }
        
        $timeRegistration->update($request->all());
        
        return response()->json($timeRegistration);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, TimeRegistration $timeRegistration)
    {
        $user = $request->user();
        
        // Check if user has permission to delete this registration
        if ($timeRegistration->user_id !== $user->id && !$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $timeRegistration->delete();
        
        return response()->json(['message' => 'Time registration deleted']);
    }
    
    /**
     * Clock in the user.
     */
    public function clockIn(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();
        $today = $now->toDateString();
        
        // Check if user is already clocked in
        $activeRegistration = TimeRegistration::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->whereNull('clock_out')
            ->first();
            
        \Log::info('Checking if already clocked in', [
            'user_id' => $user->id,
            'today' => $today,
            'is_clocked_in' => (bool)$activeRegistration
        ]);
            
        if ($activeRegistration) {
            return response()->json([
                'message' => 'You are already clocked in',
                'time_registration' => $activeRegistration
            ], 422);
        }
        
        // Get location data if available
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        
        $timeRegistration = TimeRegistration::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in' => $now->format('H:i:s'),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => 'pending',
        ]);
        
        return response()->json($timeRegistration, 201);
    }
    
    /**
     * Check if the user is currently clocked in.
     */
    public function isClockIn(Request $request)
    {
        $user = $request->user();
        $today = Carbon::now()->toDateString();
        
        \Log::info('Checking if user is clocked in', [
            'user_id' => $user->id,
            'today' => $today
        ]);
        
        // Find any active registration for today (no clock-out time)
        // Use whereDate to compare just the date portion
        $activeRegistration = TimeRegistration::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->whereNull('clock_out')
            ->first();
            
        // Log the query for debugging
        \Log::info('Query details', [
            'sql' => TimeRegistration::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->whereNull('clock_out')
                ->toSql(),
            'bindings' => [
                'user_id' => $user->id,
                'date' => $today
            ]
        ]);
        
        if ($activeRegistration) {
            \Log::info('User is clocked in', [
                'user_id' => $user->id,
                'time_registration_id' => $activeRegistration->id,
                'clock_in' => $activeRegistration->clock_in
            ]);
            
            return response()->json([
                'clocked_in' => true,
                'time_registration' => $activeRegistration
            ]);
        }
        
        \Log::info('User is not clocked in', [
            'user_id' => $user->id
        ]);
        
        return response()->json([
            'clocked_in' => false
        ]);
    }
    
    /**
     * Clock out the user.
     */
    public function clockOut(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();
        $today = $now->toDateString();
        
        // Find the active time registration
        $activeRegistration = TimeRegistration::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNull('clock_out')
            ->first();
            
        if (!$activeRegistration) {
            return response()->json(['message' => 'No active clock-in found'], 404);
        }
        
        // Get location data if available
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        
        // Update with clock-out time
        $activeRegistration->update([
            'clock_out' => $now->format('H:i:s'),
            'latitude' => $latitude ?? $activeRegistration->latitude,
            'longitude' => $longitude ?? $activeRegistration->longitude,
        ]);
        
        return response()->json($activeRegistration);
    }
    
    /**
     * Get the current clock-in status for the user.
     */
    public function status(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();
        $today = $now->toDateString();
        
        // Find the active time registration
        $activeRegistration = TimeRegistration::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNull('clock_out')
            ->first();
            
        // Format the clock-in time as a string for consistent frontend display
        $clockInTime = null;
        if ($activeRegistration && $activeRegistration->clock_in) {
            // Format as HH:MM:SS string
            $clockInTime = $activeRegistration->clock_in->format('H:i:s');
        }
        
        return response()->json([
            'is_clocked_in' => $activeRegistration ? true : false,
            'clock_in_time' => $clockInTime,
            'date' => $today,
            'time_registration_id' => $activeRegistration ? $activeRegistration->id : null
        ]);
    }
    
    /**
     * Get recent time registrations for the user.
     */
    public function recent(Request $request)
    {
        $user = $request->user();
        $limit = $request->limit ?? 5;
        
        // Get real time registrations for the logged-in user
        $registrations = TimeRegistration::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc')
            ->limit($limit)
            ->get();
            
        // Format the time values to HH:MM format and calculate total hours
        $formattedRegistrations = $registrations->map(function ($registration) {
            $data = $registration->toArray();
            
            // Format clock_in time to HH:MM
            if (isset($data['clock_in'])) {
                $clockIn = Carbon::parse($data['clock_in']);
                $data['clock_in'] = $clockIn->format('H:i');
            }
            
            // Format clock_out time to HH:MM if it exists
            if (isset($data['clock_out']) && $data['clock_out']) {
                $clockOut = Carbon::parse($data['clock_out']);
                $data['clock_out'] = $clockOut->format('H:i');
                
                // Calculate total hours if both clock_in and clock_out exist
                if (isset($clockIn)) {
                    $data['total_hours'] = round($clockOut->diffInMinutes($clockIn, true) / 60, 1);
                }
            } else {
                // If no clock_out, set total_hours to null
                $data['total_hours'] = null;
            }
            
            return $data;
        });
            
        // Log for debugging purposes
        \Log::info('Recent time registrations for user ID: ' . $user->id, [
            'count' => $registrations->count(),
            'user' => $user->personal_id,
            'sample' => $formattedRegistrations->first()
        ]);
            
        return response()->json($formattedRegistrations);
    }
    
    /**
     * Check for overlapping time registrations.
     */
    private function checkOverlappingRegistrations($userId, $date, $clockIn, $clockOut, $excludeId = null)
    {
        $query = TimeRegistration::where('user_id', $userId)
            ->where('date', $date);
            
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
