<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'user' => new UserResource($this->whenLoaded('user')),
            'session' => new SessionResource($this->whenLoaded('session')),
            'verified_at' => $this->verified_at,
            'status' => $this->status,
            'verification_method' => $this->verification_method,
            'face_match_score' => $this->face_match_score,
            'face_match' => $this->face_match,
            'gps_valid' => $this->gps_valid,
            'location' => [
                'lat' => $this->location_lat,
                'lng' => $this->location_lng,
            ],
            'distance_from_venue' => $this->distance_from_venue,
            'webauthn_used' => $this->webauthn_used,
            'created_at' => $this->created_at,
        ];
    }
}

