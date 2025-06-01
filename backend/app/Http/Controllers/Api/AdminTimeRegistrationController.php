<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminTimeRegistrationResource;
use Illuminate\Http\Request;
use App\Models\TimeRegistration;
use App\Models\User;
use Carbon\Carbon;

class AdminTimeRegistrationController extends Controller
{
    /**
     * Get all time registrations for admin view
     * This endpoint is specifically for the admin dashboard
     */
    public function index(Request $request)
    {
        // Parse date range or default to last 7 days
        $endDate = $request->input('end_date') ?? Carbon::today()->toDateString();
        $startDate = $request->input('start_date') ?? Carbon::parse($endDate)->subDays(7)->toDateString();
        
        // Log the date range for debugging
        \Log::info('AdminTimeRegistrationController: Date range request', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        // Query with Eloquent and eager load user
        $registrations = TimeRegistration::with('user')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderByDesc('date')
            ->orderByDesc('clock_in')
            ->get();
            
        // Use a resource for formatting
        return AdminTimeRegistrationResource::collection($registrations);
    }
}
