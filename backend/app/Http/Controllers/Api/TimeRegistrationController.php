<?php

namespace App\Http\Controllers\Api;

use App\Events\TimeRegistrationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\TimeRegistration\ClockInRequest;
use App\Http\Requests\TimeRegistration\ClockOutRequest;
use App\Http\Requests\TimeRegistration\IndexTimeRegistrationRequest;
use App\Http\Requests\TimeRegistration\RecentTimeRegistrationRequest;
use App\Http\Requests\TimeRegistration\StatusRequest;
use App\Http\Requests\TimeRegistration\StoreTimeRegistrationRequest;
use App\Http\Requests\TimeRegistration\UpdateTimeRegistrationRequest;
use App\Http\Resources\RecentTimeRegistrationResource;
use App\Http\Resources\TimeRegistrationResource;
use App\Models\TimeRegistration;
use App\Models\User;
use App\Services\TimeRegistrationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class TimeRegistrationController extends Controller
{
    /**
     * @var TimeRegistrationService
     */
    protected $timeRegistrationService;
    
    /**
     * Create a new controller instance.
     */
    public function __construct(TimeRegistrationService $timeRegistrationService)
    {
        $this->timeRegistrationService = $timeRegistrationService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(IndexTimeRegistrationRequest $request)
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
    public function store(StoreTimeRegistrationRequest $request)
    {
        
        $user = $request->user();
        $userId = $request->user_id ?? $user->id;
        
        // Check if user has permission to create for other users
        if ($userId !== $user->id && !$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Check for overlapping time registrations
        $overlapping = $this->timeRegistrationService->checkOverlappingRegistrations(
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
            ...$request->only(['date', 'clock_in', 'clock_out', 'description']),
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
    public function update(UpdateTimeRegistrationRequest $request, TimeRegistration $timeRegistration)
    {
        
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
            
            $overlapping = $this->timeRegistrationService->checkOverlappingRegistrations(
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
    public function clockIn(ClockInRequest $request)
    {
        $user = $request->user();
        $now = Carbon::now();
        $today = $now->toDateString();
        
        // Check if user is already clocked in
        $activeRegistration = $this->timeRegistrationService->getUserActiveTimeRegistration($user->id);
            
        event(new TimeRegistrationEvent('checking_already_clocked_in', [
            'date' => $today,
            'is_clocked_in' => (bool)$activeRegistration
        ], $user));
            
        if ($activeRegistration) {
            return response()->json([
                'message' => 'You are already clocked in',
                'time_registration' => $activeRegistration
            ], 422);
        }
        
        // Use the service to clock in the user
        $timeRegistration = $this->timeRegistrationService->clockInUser(
            $user->id,
            $request->only(['latitude', 'longitude'])
        );
        
        return response()->json($timeRegistration, 201);
    }
    
    /**
     * Check if the user is currently clocked in.
     */
    public function isClockIn(Request $request)
    {
        $user = $request->user();
        $today = Carbon::now()->toDateString();
        
        event(new TimeRegistrationEvent('checking_clock_in', [
            'today' => $today
        ], $user));
        
        // Get status from service
        $status = $this->timeRegistrationService->getUserTimeRegistrationStatus($user->id);
        $activeRegistration = $status['time_registration'];
        $clockInTime = $status['clock_in_time'];
            
        if ($activeRegistration) {
            event(new TimeRegistrationEvent('user_clocked_in', [], $user, $activeRegistration));
            
            return response()->json([
                'status' => [
                    'clocked_in' => true,
                    'time_registration' => $activeRegistration,
                    'clock_in_time' => $clockInTime
                ]
            ]);
        }
        
        event(new TimeRegistrationEvent('user_not_clocked_in', [], $user));
        
        return response()->json([
            'status' => [
                'clocked_in' => false,
                'clock_in_time' => null
            ]
        ]);
    }
    
    /**
     * Clock out the user.
     */
    public function clockOut(ClockOutRequest $request)
    {
        $user = $request->user();
        $now = Carbon::now();
        $today = $now->toDateString();
        
        // Find the active time registration using service
        $activeRegistration = $this->timeRegistrationService->getUserActiveTimeRegistration($user->id);
            
        if (!$activeRegistration) {
            return response()->json(['message' => 'No active clock-in found'], 404);
        }
        
        // Use the service to clock out the user
        $activeRegistration = $this->timeRegistrationService->clockOutUser(
            $activeRegistration,
            $request->only(['latitude', 'longitude'])
        );
        
        return response()->json($activeRegistration);
    }
    
    /**
     * Get the current clock-in status for the user.
     */
    public function status(StatusRequest $request)
    {
        $user = $request->user();
        
        // Get the user's time registration status from the service
        $status = $this->timeRegistrationService->getUserTimeRegistrationStatus($user->id);
        
        return response()->json([
            'status' => [
                'clocked_in' => $status['clocked_in'],
                'time_registration' => $status['time_registration'],
                'clock_in_time' => $status['clock_in_time'],
                'duration' => $status['duration'],
            ]
        ]);
    }
    
    /**
     * Get recent time registrations for the user.
     */
    public function recent(RecentTimeRegistrationRequest $request)
    {
        $user = $request->user();
        $limit = $request->limit ?? 5;
        
        // Get real time registrations for the logged-in user
        $registrations = TimeRegistration::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc')
            ->limit($limit)
            ->get();
            
        // Use the RecentTimeRegistrationResource to format the data
        $formattedRegistrations = RecentTimeRegistrationResource::collection($registrations)->resolve();
            
        event(new TimeRegistrationEvent('recent_time_registrations', [
            'count' => $registrations->count(),
            'user_personal_id' => $user->personal_id
        ], $user));
            
        return response()->json($formattedRegistrations);
    }
}
