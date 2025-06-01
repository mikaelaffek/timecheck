<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AdminTimeRegistrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $clockIn = $this->clock_in ? Carbon::parse($this->clock_in) : null;
        $clockOut = $this->clock_out ? Carbon::parse($this->clock_out) : null;
        
        // Format total hours as "Xh Ym" (e.g., "2h 30m")
        $formattedHours = null;
        if ($this->total_hours) {
            $hours = floor($this->total_hours);
            $minutes = round(($this->total_hours - $hours) * 60);
            $formattedHours = ($hours > 0 ? $hours . 'h ' : '') . ($minutes > 0 ? $minutes . 'm' : '');
            $formattedHours = trim($formattedHours);
        }
        
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'personal_id' => $this->user->personal_id,
                'role' => $this->user->role,
            ],
            'date' => $this->date,
            'clock_in' => $this->clock_in,
            'clock_in_formatted' => $clockIn ? $clockIn->format('H:i') : null,
            'clock_out' => $this->clock_out,
            'clock_out_formatted' => $clockOut ? $clockOut->format('H:i') : null,
            'total_hours' => $this->total_hours,
            'total_hours_formatted' => $formattedHours,
            'notes' => $this->notes,
            'status' => $this->status,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
