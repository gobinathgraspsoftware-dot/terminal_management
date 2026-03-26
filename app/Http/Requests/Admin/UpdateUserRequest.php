<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('edit_users');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id ?? $this->route('user');

        return [
            // Basic Information
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'employee_id' => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_id')->ignore($userId)],

            // Password (optional for update)
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],

            // Role
            'role' => ['required', 'string', 'exists:roles,name'],

            // Status
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'suspended'])],

            // Supervisor Type (only for supervisor role)
            'supervisor_type' => [
                'nullable',
                'string',
                Rule::in(['internal', 'external']),
                'required_if:role,supervisor',
            ],

            // FIX: supervisor_id is required when role is technician
            // (independent technician concept removed — all technicians must have a supervisor)
            'supervisor_id' => [
                'nullable',
                'exists:users,id',
                'required_if:role,technician',
                'different:id',
            ],

            'coverage_states' => ['nullable', 'array'],
            'coverage_states.*' => ['string', 'max:100'],
            'skill_tags' => ['nullable', 'array'],
            'skill_tags.*' => ['string', 'max:100'],

            // Bank Details
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],

            // Location
            'state_id' => ['nullable', 'exists:states,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'mileage_rate' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],

            // Address
            'address' => ['nullable', 'string', 'max:500'],

            // Avatar
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],

            // Job Pricing (for supervisor role)
            'job_pricing' => ['nullable', 'array'],
            'job_pricing.*' => ['nullable', 'array'],
            'job_pricing.*.*' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
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
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Please select a role for the user.',
            'role.exists' => 'The selected role is invalid.',
            'status.required' => 'Please select a status.',
            'supervisor_type.required_if' => 'Please select a supervisor type (Internal or External).',
            'supervisor_type.in' => 'Supervisor type must be Internal or External.',
            'supervisor_id.required_if' => 'Please select a supervisor for this technician.',
            'supervisor_id.exists' => 'The selected supervisor is invalid.',
            'supervisor_id.different' => 'A user cannot be their own supervisor.',
            'avatar.image' => 'Avatar must be an image file.',
            'avatar.mimes' => 'Avatar must be a JPG, JPEG, PNG, or GIF file.',
            'avatar.max' => 'Avatar file size must not exceed 2MB.',
        ];
    }

    /**
     * Prepare data for validation
     *
     * FIX: Removed has_supervisor logic entirely.
     * The "independent technician" concept was removed — all technicians
     * MUST have a supervisor. The old code checked for a has_supervisor
     * checkbox that no longer exists in the form, so it always evaluated
     * to false, which forced supervisor_id to null every time.
     */
    protected function prepareForValidation(): void
    {
        // FIX: Clear supervisor_id only if role is NOT technician
        // (technicians MUST have a supervisor — keep supervisor_id as-is)
        if ($this->role !== 'technician') {
            $this->merge(['supervisor_id' => null]);
        }

        // Clear supervisor_type if not supervisor role
        if ($this->role !== 'supervisor') {
            $this->merge(['supervisor_type' => null]);
        }

        // Convert remove_avatar to boolean
        if ($this->has('remove_avatar')) {
            $this->merge([
                'remove_avatar' => filter_var($this->remove_avatar, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }
}
