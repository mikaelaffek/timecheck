<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TimeRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminTimeRegistrationController extends Controller
{
    /**
     * Get all time registrations for admin view
     * This endpoint is specifically for the admin dashboard
     */
    public function index(Request $request)
    {
        // Parse date range if provided
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Log the date range for debugging
        \Log::info('AdminTimeRegistrationController: Date range request', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        // Build query for time registrations with user data
        $query = DB::table('time_registrations as tr')
            ->join('users as u', 'tr.user_id', '=', 'u.id')
            ->select(
                'tr.id', 
                'tr.user_id', 
                'tr.date', 
                'tr.clock_in', 
                'tr.clock_out', 
                'tr.total_hours',
                'tr.latitude',
                'tr.longitude',
                'tr.notes',
                'tr.status',
                'u.name as user_name',
                'u.email as user_email',
                'u.role as user_role'
            );
        
        // If no date range provided, default to last 7 days
        if (!$startDate || !$endDate) {
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime('-7 days'));
            \Log::info('AdminTimeRegistrationController: Using default date range', [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
        }
        
        // Apply date range filter
        $query->whereBetween('tr.date', [$startDate, $endDate]);
        
        // Log the SQL query for debugging
        \Log::info('AdminTimeRegistrationController: SQL query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);
        
        // Get the results
        $timeRegistrations = $query
            ->orderBy('tr.date', 'desc')
            ->orderBy('tr.clock_in', 'desc')
            ->get();

        // Format the data for the frontend
        $formattedRegistrations = $timeRegistrations->map(function($registration) {
            return [
                'id' => $registration->id,
                'user_id' => $registration->user_id,
                'date' => $registration->date,
                'clock_in' => $registration->clock_in,
                'clock_out' => $registration->clock_out,
                'total_hours' => $registration->total_hours,
                'latitude' => $registration->latitude,
                'longitude' => $registration->longitude,
                'notes' => $registration->notes,
                'status' => $registration->status,
                'user' => [
                    'id' => $registration->user_id,
                    'name' => $registration->user_name,
                    'email' => $registration->user_email,
                    'role' => $registration->user_role
                ]
            ];
        });

        return response()->json($formattedRegistrations);
    }
}
