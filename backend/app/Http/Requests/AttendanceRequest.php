<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:sessions,id'],
            'qr_code' => ['required', 'string', 'max:255'],
            'face_descriptor' => ['required', 'array', 'size:128'],
            'face_descriptor.*' => ['required', 'numeric', 'between:-1,1'],
            'location' => ['required', 'array'],
            'location.lat' => ['required', 'numeric', 'between:-90,90'],
            'location.lng' => ['required', 'numeric', 'between:-180,180'],
            'location.accuracy' => ['nullable', 'numeric', 'min:0'],
            'webauthn_credential_id' => ['nullable', 'string']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'session_id.required' => 'Session ID is required',
            'session_id.exists' => 'Session not found',
            'qr_code.required' => 'QR code is required',
            'face_descriptor.required' => 'Face descriptor is required',
            'face_descriptor.size' => 'Face descriptor must have 128 values',
            'location.required' => 'Location is required',
            'location.lat.required' => 'Latitude is required',
            'location.lat.between' => 'Latitude must be between -90 and 90',
            'location.lng.required' => 'Longitude is required',
            'location.lng.between' => 'Longitude must be between -180 and 180'
        ];
    }
}

