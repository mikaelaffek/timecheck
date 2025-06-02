<?php

namespace App\Http\Controllers\Api;

use App\Events\AdminTimeRegistrationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminTimeRegistration\IndexAdminTimeRegistrationRequest;
use App\Http\Resources\AdminTimeRegistrationResource;
use App\Models\TimeRegistration;
use Carbon\Carbon;
use Illuminate\Http\Response;

class AdminTimeRegistrationController extends Controller
{
    /**
     * Get all time registrations for admin view
     * This endpoint is specifically for the admin dashboard
     */
    public function index(IndexAdminTimeRegistrationRequest $request)
    {
        $user = $request->user();
        
        // Parse date range or default to last 7 days
        $endDate = $request->input('end_date') ?? Carbon::today()->toDateString();
        $startDate = $request->input('start_date') ?? Carbon::parse($endDate)->subDays(7)->toDateString();
        
        // Dispatch event for logging
        event(new AdminTimeRegistrationEvent('admin_time_registrations_request', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'user_personal_id' => $user->personal_id
        ], $user));
        
        // Query with Eloquent and eager load user
        $registrations = TimeRegistration::with('user')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderByDesc('date')
            ->orderByDesc('clock_in')
            ->get();
            
        // Dispatch event for successful retrieval
        event(new AdminTimeRegistrationEvent('admin_time_registrations_retrieved', [
            'count' => $registrations->count(),
            'date_range' => "$startDate to $endDate"
        ], $user, $registrations->toArray()));
        
        // Use a resource for formatting and resolve to remove data wrapper
        return AdminTimeRegistrationResource::collection($registrations)->resolve();
    }
}
