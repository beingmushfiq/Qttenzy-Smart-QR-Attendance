<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * OrganizationRequest
 * 
 * Validates organization creation and update requests.
 */
class OrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('organizations', 'code')->ignore($organizationId)
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'array'],
            'settings.timezone' => ['nullable', 'string', 'timezone'],
            'settings.late_threshold_minutes' => ['nullable', 'integer', 'min:0', 'max:60'],
            'settings.face_confidence_threshold' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'settings.gps_radius_meters' => ['nullable', 'integer', 'min:10', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Organization name is required',
            'code.required' => 'Organization code is required',
            'code.unique' => 'This organization code is already in use',
            'code.alpha_dash' => 'Organization code can only contain letters, numbers, dashes and underscores',
            'email.email' => 'Please provide a valid email address',
            'settings.timezone.timezone' => 'Please provide a valid timezone',
        ];
    }
}
