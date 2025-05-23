<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRule;
use Illuminate\Http\Request;

class OvertimeRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Only admins can manage overtime rules
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $query = OvertimeRule::query();
        
        // Filter by active status if specified
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Filter by type if specified
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        return $query->orderBy('name')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Only admins can manage overtime rules
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:weekday_evening,weekend,holiday,night_shift',
            'multiplier' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);
        
        $overtimeRule = OvertimeRule::create($request->all());
        
        return response()->json($overtimeRule, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, OvertimeRule $overtimeRule)
    {
        $user = $request->user();
        
        // Only admins can manage overtime rules
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($overtimeRule);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OvertimeRule $overtimeRule)
    {
        $user = $request->user();
        
        // Only admins can manage overtime rules
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:weekday_evening,weekend,holiday,night_shift',
            'multiplier' => 'sometimes|numeric|min:1',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);
        
        $overtimeRule->update($request->all());
        
        return response()->json($overtimeRule);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, OvertimeRule $overtimeRule)
    {
        $user = $request->user();
        
        // Only admins can manage overtime rules
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $overtimeRule->delete();
        
        return response()->json(['message' => 'Overtime rule deleted']);
    }
    
    /**
     * Get active overtime rules.
     */
    public function getActiveRules()
    {
        return OvertimeRule::where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
