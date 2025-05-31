<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'enable_notifications' => $this->enable_notifications,
            'auto_clock_out' => $this->auto_clock_out,
            'default_view' => $this->default_view,
            'time_format' => $this->time_format,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
