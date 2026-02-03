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
            'qr_code' => ['nullable', 'string', 'max:255'],
            'face_descriptor' => ['nullable', 'array', 'size:128'],
            'face_descriptor.*' => ['nullable', 'numeric', 'between:-1,1'],
            'location' => ['nullable', 'array'],
            'location.lat' => ['required_with:location', 'numeric', 'between:-90,90'],
            'location.lng' => ['required_with:location', 'numeric', 'between:-180,180'],
            'location.accuracy' => ['nullable', 'numeric', 'min:0'],
            'webauthn_credential_id' => ['nullable', 'string']
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Ensure at least one authentication method is provided
            if (!$this->qr_code && !$this->face_descriptor) {
                $validator->errors()->add(
                    'authentication',
                    'At least one authentication method (QR code or face descriptor) is required'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'session_id.required' => 'Session ID is required',
            'session_id.exists' => 'Session not found',
            'qr_code.string' => 'QR code must be a valid string',
            'face_descriptor.array' => 'Face descriptor must be an array',
            'face_descriptor.size' => 'Face descriptor must have 128 values',
            'location.required' => 'Location is required',
            'location.lat.required' => 'Latitude is required',
            'location.lat.between' => 'Latitude must be between -90 and 90',
            'location.lng.required' => 'Longitude is required',
            'location.lng.between' => 'Longitude must be between -180 and 180'
        ];
    }
}

