<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $session = $this->route('id') ? \App\Models\Session::find($this->route('id')) : null;
        
        if (!$session) {
            return false;
        }

        // User must be admin or the creator
        return $this->user()->hasRole('admin') || $session->created_by === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'location_lat' => 'sometimes|numeric|between:-90,90',
            'location_lng' => 'sometimes|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
            'radius_meters' => 'nullable|integer|min:10|max:10000',
            'session_type' => 'sometimes|in:admin_approved,pre_registered,open',
            'status' => 'sometimes|in:draft,scheduled,active,completed,cancelled',
            'requires_payment' => 'nullable|boolean',
            'payment_amount' => 'nullable|numeric|min:0',
            'max_attendees' => 'nullable|integer|min:1',
            'capacity' => 'nullable|integer|min:1',
            'allow_entry_exit' => 'nullable|boolean',
            'late_threshold_minutes' => 'nullable|integer|min:0|max:120',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'end_time.after' => 'End time must be after start time',
            'location_lat.between' => 'Latitude must be between -90 and 90',
            'location_lng.between' => 'Longitude must be between -180 and 180',
        ];
    }
}
