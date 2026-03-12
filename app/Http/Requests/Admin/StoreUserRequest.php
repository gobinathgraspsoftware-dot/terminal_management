<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('create_users');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:users,employee_id'],

            // Password
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Role
            'role' => ['required', 'string', 'exists:roles,name'],

            // Status
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'suspended'])],

            // State & City — required for supervisor and technician
            'state_id' => ['nullable', 'exists:states,id', 'required_if:role,supervisor', 'required_if:role,technician'],
            'city_id' => ['nullable', 'exists:cities,id', 'required_if:role,supervisor', 'required_if:role,technician'],

            // Mileage Rate — supervisor sets own rate
            'mileage_rate' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],

            // Technician-specific fields
            'has_supervisor' => ['nullable', 'boolean'],

            'supervisor_id' => [
                'nullable',
                'exists:users,id',
                'required_if:has_supervisor,1',
                'required_if:has_supervisor,true',
            ],

            'coverage_states' => ['nullable', 'array'],
            'coverage_states.*' => ['string', 'max:100'],
            'skill_tags' => ['nullable', 'array'],
            'skill_tags.*' => ['string', 'max:100'],

            // Bank Details
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],

            // Address
            'address' => ['nullable', 'string', 'max:500'],

            // Avatar
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the user\'s full name.',
            'name.min' => 'Name must be at least 3 characters long.',
            'email.required' => 'Please enter an email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Please enter a password.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Please select a role for the user.',
            'role.exists' => 'The selected role is invalid.',
            'status.required' => 'Please select a status.',
            'state_id.required_if' => 'Please select a state for this role.',
            'city_id.required_if' => 'Please select a city/district for this role.',
            'mileage_rate.numeric' => 'Mileage rate must be a valid number.',
            'mileage_rate.min' => 'Mileage rate cannot be negative.',
            'supervisor_id.required_if' => 'Please select a supervisor when "Assign to Supervisor" is enabled.',
            'supervisor_id.exists' => 'The selected supervisor is invalid.',
            'avatar.image' => 'Avatar must be an image file.',
            'avatar.mimes' => 'Avatar must be a JPG, JPEG, PNG, or GIF file.',
            'avatar.max' => 'Avatar file size must not exceed 2MB.',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Convert has_supervisor checkbox to boolean
        if ($this->has('has_supervisor')) {
            $this->merge([
                'has_supervisor' => filter_var($this->has_supervisor, FILTER_VALIDATE_BOOLEAN)
            ]);
        } else {
            $this->merge(['has_supervisor' => false]);
        }

        // If has_supervisor is false OR role is not technician, clear supervisor_id
        if (!$this->has_supervisor || $this->role !== 'technician') {
            $this->merge(['supervisor_id' => null]);
        }

        // Convert remove_avatar to boolean
        if ($this->has('remove_avatar')) {
            $this->merge([
                'remove_avatar' => filter_var($this->remove_avatar, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        // For technician with supervisor, state_id/city_id will be overridden in UserService
        // so we don't need to require them from the form; but they are still sent

        // Clean mileage_rate — empty string to null
        if ($this->has('mileage_rate') && $this->mileage_rate === '') {
            $this->merge(['mileage_rate' => null]);
        }
    }
}
