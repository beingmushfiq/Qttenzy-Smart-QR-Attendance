<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * RegisterRequest
 * 
 * Validates user registration data.
 * Ensures email uniqueness, password strength, and proper role assignment.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Registration is open to everyone
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'unique:users,phone',
                'regex:/^\+?[0-9]{10,15}$/'
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:6'
            ],
            'role' => [
                'nullable',
                'string',
                'in:student,employee,teacher,organization_admin,event_manager,coordinator'
            ],
            'organization_id' => [
                'nullable',
                'required_without:create_organization',
                'exists:organizations,id'
            ],
            'create_organization' => [
                'nullable',
                'boolean'
            ],
            'organization_name' => [
                'required_if:create_organization,true',
                'nullable',
                'string',
                'max:255',
                'unique:organizations,name'
            ],
            'organization_address' => [
                'nullable',
                'string',
                'max:500'
            ],
            'organization_phone' => [
                'nullable',
                'string',
                'max:20'
            ],
            'face_consent' => [
                'nullable',
                'boolean'
            ],
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
            'name.required' => 'Please provide your full name.',
            'name.min' => 'Name must be at least 2 characters long.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'email.regex' => 'Please provide a valid email format.',
            'phone.unique' => 'This phone number is already registered.',
            'phone.regex' => 'Please provide a valid phone number (10-15 digits).',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 6 characters.',
            'role.in' => 'Invalid role selected.',
            'organization_id.exists' => 'Selected organization does not exist.',
            'organization_id.required_without' => 'Please select an organization or create a new one.',
            'organization_name.required_if' => 'Organization name is required when creating a new organization.',
            'organization_name.unique' => 'This organization name is already registered.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'phone' => 'phone number',
            'password' => 'password',
            'password_confirmation' => 'password confirmation',
            'role' => 'user role',
            'organization_id' => 'organization',
            'face_consent' => 'face recognition consent',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default role to student if not provided
        if (!$this->has('role')) {
            $this->merge([
                'role' => 'student'
            ]);
        }

        // Normalize phone number
        if ($this->has('phone') && $this->phone) {
            $phone = preg_replace('/[^0-9+]/', '', $this->phone);
            $this->merge([
                'phone' => $phone
            ]);
        }

        // Set face_consent default
        if (!$this->has('face_consent')) {
            $this->merge([
                'face_consent' => false
            ]);
        }
    }
}
