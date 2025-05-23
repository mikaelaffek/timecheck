<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\TimeRegistration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * Generate a time report.
     */
    public function generateTimeReport(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,excel',
        ]);
        
        // If user_id is provided, check if current user has permission to generate report for that user
        if ($request->has('user_id') && $request->user_id != $user->id && !$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $userId = $request->user_id ?? $user->id;
        $targetUser = User::find($userId);
        
        // Get time registrations for the specified period
        $timeRegistrations = TimeRegistration::where('user_id', $userId)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->orderBy('date')
            ->orderBy('clock_in')
            ->get();
            
        // Generate file name
        $fileName = 'time_report_' . $targetUser->personal_id . '_' . 
                    $request->start_date . '_' . $request->end_date . '.' . $request->format;
        
        // In a real application, we would generate the actual report file here
        // For this example, we'll just create a placeholder file
        $filePath = 'reports/' . $fileName;
        
        // Store report record in database
        $report = Report::create([
            'type' => 'time_report',
            'format' => $request->format,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'user_id' => $userId,
            'file_path' => $filePath,
            'created_by' => $user->id,
        ]);
        
        // In a real application, we would return the actual file
        // For this example, we'll just return the report record
        return response()->json([
            'message' => 'Time report generated successfully',
            'report' => $report,
            'time_registrations_count' => $timeRegistrations->count(),
        ]);
    }
    
    /**
     * Generate a staff registry report.
     */
    public function generateStaffRegistry(Request $request)
    {
        $user = $request->user();
        
        // Only admins and managers can generate staff registry reports
        if (!$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location_id' => 'required|exists:locations,id',
        ]);
        
        // Get all time registrations for the specified period and location
        $timeRegistrations = TimeRegistration::whereHas('user')
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->orderBy('date')
            ->orderBy('user_id')
            ->orderBy('clock_in')
            ->get();
            
        // Generate file name
        $fileName = 'staff_registry_' . $request->location_id . '_' . 
                    $request->start_date . '_' . $request->end_date . '.pdf';
        
        // In a real application, we would generate the actual report file here
        // For this example, we'll just create a placeholder file
        $filePath = 'reports/' . $fileName;
        
        // Store report record in database
        $report = Report::create([
            'type' => 'staff_registry',
            'format' => 'pdf',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location_id' => $request->location_id,
            'file_path' => $filePath,
            'created_by' => $user->id,
        ]);
        
        // In a real application, we would return the actual file
        // For this example, we'll just return the report record
        return response()->json([
            'message' => 'Staff registry generated successfully',
            'report' => $report,
            'time_registrations_count' => $timeRegistrations->count(),
        ]);
    }
    
    /**
     * Get recent reports.
     */
    public function getRecentReports(Request $request)
    {
        $user = $request->user();
        $limit = $request->limit ?? 10;
        
        $query = Report::query();
        
        // If not admin or manager, only show own reports
        if (!$user->isAdmin() && !$user->isManager()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }
        
        $reports = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
            
        return response()->json($reports);
    }
    
    /**
     * Download a report.
     */
    public function downloadReport(Request $request, Report $report)
    {
        $user = $request->user();
        
        // Check if user has permission to download this report
        if ($report->user_id !== $user->id && $report->created_by !== $user->id && 
            !$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // In a real application, we would return the actual file
        // For this example, we'll just return a message
        return response()->json([
            'message' => 'Report download initiated',
            'report' => $report,
        ]);
    }
}
