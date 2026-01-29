<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreClientContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('edit_clients');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Contact Name
            'contact_name' => [
                'required',
                'string',
                'max:100'
            ],

            // Contact Title
            'contact_title' => [
                'nullable',
                'string',
                'max:100'
            ],

            // Contact Email
            'contact_email' => [
                'nullable',
                'email',
                'max:255'
            ],

            // Contact Phone
            'contact_phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9\-\+\s\(\)]+$/'
            ],

            // Is Primary Contact
            'is_primary' => [
                'nullable',
                'boolean'
            ],

            // Notes
            'notes' => [
                'nullable',
                'string',
                'max:500'
            ],

            // Status
            'status' => [
                'required',
                'string',
                'in:active,inactive'
            ],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'contact_name.required' => 'Contact name is required.',
            'contact_name.max' => 'Contact name cannot exceed 100 characters.',
            
            'contact_title.max' => 'Contact title cannot exceed 100 characters.',
            
            'contact_email.email' => 'Please enter a valid email address.',
            'contact_email.max' => 'Email cannot exceed 255 characters.',
            
            'contact_phone.max' => 'Phone number cannot exceed 20 characters.',
            'contact_phone.regex' => 'Please enter a valid phone number (digits, spaces, dashes, and parentheses only).',
            
            'is_primary.boolean' => 'Primary contact flag must be true or false.',
            
            'notes.max' => 'Notes cannot exceed 500 characters.',
            
            'status.required' => 'Please select a status.',
            'status.in' => 'Invalid status selected.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'contact_name' => 'contact name',
            'contact_title' => 'contact title',
            'contact_email' => 'email',
            'contact_phone' => 'phone number',
            'is_primary' => 'primary contact',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty strings to null for nullable fields
        $nullableFields = ['contact_title', 'contact_email', 'contact_phone', 'notes'];
        
        foreach ($nullableFields as $field) {
            if ($this->has($field) && $this->{$field} === '') {
                $this->merge([$field => null]);
            }
        }

        // Convert is_primary to boolean
        if ($this->has('is_primary')) {
            $this->merge([
                'is_primary' => filter_var($this->is_primary, FILTER_VALIDATE_BOOLEAN)
            ]);
        } else {
            $this->merge(['is_primary' => false]);
        }

        // Set default status if not provided
        if (!$this->has('status') || empty($this->status)) {
            $this->merge(['status' => 'active']);
        }
    }
}
