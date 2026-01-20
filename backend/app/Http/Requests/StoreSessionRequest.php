<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'session_manager']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'location_lat' => 'required|numeric|between:-90,90',
            'location_lng' => 'required|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
            'radius_meters' => 'nullable|integer|min:10|max:10000',
            'session_type' => 'required|in:admin_approved,pre_registered,open',
            'requires_payment' => 'nullable|boolean',
            'payment_amount' => 'required_if:requires_payment,true|numeric|min:0',
            'max_attendees' => 'nullable|integer|min:1',
            'recurrence_type' => 'nullable|in:one_time,daily,weekly,monthly',
            'recurrence_end_date' => 'required_if:recurrence_type,daily,weekly,monthly|nullable|date|after:start_time',
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
            'title.required' => 'Session title is required',
            'start_time.after' => 'Session must start in the future',
            'end_time.after' => 'Session end time must be after start time',
            'location_lat.between' => 'Latitude must be between -90 and 90',
            'location_lng.between' => 'Longitude must be between -180 and 180',
            'recurrence_end_date.required_if' => 'Recurrence end date is required for recurring sessions',
            'payment_amount.required_if' => 'Payment amount is required when payment is enabled',
        ];
    }
}
