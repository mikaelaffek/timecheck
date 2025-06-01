<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecentTimeRegistrationResource extends JsonResource
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

        $totalMinutes = ($clockIn && $clockOut) ? $clockOut->diffInMinutes($clockIn, true) : null;
        $hours = $totalMinutes ? floor($totalMinutes / 60) : null;
        $minutes = $totalMinutes ? $totalMinutes % 60 : null;

        return [
            'id' => $this->id,
            'date' => $this->date,
            'clock_in' => $clockIn ? $clockIn->format('H:i') : null,
            'clock_out' => $clockOut ? $clockOut->format('H:i') : null,
            'total_hours' => $totalMinutes !== null ? "{$hours}h {$minutes}m" : null,
            'total_hours_decimal' => $totalMinutes !== null ? round($totalMinutes / 60, 2) : null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }
}
