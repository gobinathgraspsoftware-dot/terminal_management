<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('users.create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:users,employee_id'],
            
            // Role and Permissions
            'role' => ['required', 'string', 'exists:roles,name'],
            
            // Supervisor Assignment
            'supervisor_id' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $supervisor = \App\Models\User::find($value);
                        if (!$supervisor || !$supervisor->hasRole('supervisor')) {
                            $fail('The selected supervisor must have the supervisor role.');
                        }
                    }
                },
            ],
            
            // Coverage and Skills
            'coverage_states' => ['nullable', 'array'],
            'coverage_states.*' => ['string', 'max:100'],
            'skill_tags' => ['nullable', 'array'],
            'skill_tags.*' => ['string', 'max:100'],
            
            // Commission Rate (for technicians)
            'default_rate_card_id' => ['nullable', 'exists:rate_cards,id'],
            
            // Bank Details (for payouts)
            'bank_name' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf(function () {
                    return $this->input('role') === 'technician';
                }),
            ],
            'bank_account_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::requiredIf(function () {
                    return $this->input('role') === 'technician';
                }),
            ],
            'bank_account_name' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf(function () {
                    return $this->input('role') === 'technician';
                }),
            ],
            
            // Address
            'address' => ['nullable', 'string', 'max:500'],
            
            // Status
            'status' => ['required', 'in:active,inactive,suspended'],
            
            // Avatar
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'], // 2MB max
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'employee ID',
            'supervisor_id' => 'supervisor',
            'coverage_states' => 'coverage states',
            'skill_tags' => 'skill tags',
            'default_rate_card_id' => 'default rate card',
            'bank_name' => 'bank name',
            'bank_account_no' => 'bank account number',
            'bank_account_name' => 'bank account name',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already registered.',
            'employee_id.unique' => 'This employee ID is already in use.',
            'role.exists' => 'The selected role is invalid.',
            'supervisor_id.exists' => 'The selected supervisor does not exist.',
            'bank_name.required' => 'Bank name is required for technicians.',
            'bank_account_no.required' => 'Bank account number is required for technicians.',
            'bank_account_name.required' => 'Bank account name is required for technicians.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-assign supervisor_id to null for non-technician roles
        if ($this->has('role') && $this->role !== 'technician') {
            $this->merge([
                'supervisor_id' => null,
            ]);
        }
        
        // Convert JSON strings to arrays if needed
        if ($this->has('coverage_states') && is_string($this->coverage_states)) {
            $this->merge([
                'coverage_states' => json_decode($this->coverage_states, true) ?: [],
            ]);
        }
        
        if ($this->has('skill_tags') && is_string($this->skill_tags)) {
            $this->merge([
                'skill_tags' => json_decode($this->skill_tags, true) ?: [],
            ]);
        }
    }
}