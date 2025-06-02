<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeRegistrationStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => [
                'clocked_in' => $this['clocked_in'] ?? false,
                'time_registration' => $this['time_registration'] ? new TimeRegistrationResource($this['time_registration']) : null,
                'clock_in_time' => $this['clock_in_time'] ?? null,
                'duration' => $this['duration'] ?? null,
            ]
        ];
    }
}
