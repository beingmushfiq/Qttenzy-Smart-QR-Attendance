<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'location' => [
                'lat' => $this->location_lat,
                'lng' => $this->location_lng,
                'name' => $this->location_name,
            ],
            'radius_meters' => $this->radius_meters,
            'session_type' => $this->session_type,
            'status' => $this->status,
            'requires_payment' => $this->requires_payment,
            'payment_amount' => $this->payment_amount,
            'max_attendees' => $this->max_attendees,
            'current_attendees' => $this->when(isset($this->current_attendees), $this->current_attendees),
            'created_by' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

