<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // All authenticated users can view locations
        $query = Location::query();
        
        // Search by name if specified
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('address', 'like', '%' . $request->search . '%');
        }
        
        return $query->orderBy('name')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Only admins and managers can create locations
        if (!$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        
        $location = Location::create($request->all());
        
        return response()->json($location, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Location $location)
    {
        // All authenticated users can view locations
        return response()->json($location);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Location $location)
    {
        $user = $request->user();
        
        // Only admins and managers can update locations
        if (!$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        
        $location->update($request->all());
        
        return response()->json($location);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Location $location)
    {
        $user = $request->user();
        
        // Only admins can delete locations
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Check if location is being used in schedules
        if ($location->schedules()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete location that is being used in schedules',
                'schedules_count' => $location->schedules()->count()
            ], 422);
        }
        
        $location->delete();
        
        return response()->json(['message' => 'Location deleted']);
    }
    
    /**
     * Get nearby locations based on coordinates.
     */
    public function getNearby(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'sometimes|numeric|min:0.1|max:50', // radius in kilometers
        ]);
        
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 5; // Default 5 km
        
        // In a real application, we would use a spatial query to find nearby locations
        // For this example, we'll just return all locations
        $locations = Location::all();
        
        return response()->json([
            'message' => 'Nearby locations found',
            'locations' => $locations,
            'search_parameters' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius' => $radius
            ]
        ]);
    }
}
