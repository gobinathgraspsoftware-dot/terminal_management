<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('create_clients');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Client Code (optional - auto-generated if empty)
            'client_code' => [
                'nullable',
                'string',
                'max:50',
                'unique:clients,client_code'
            ],

            // Client Name
            'client_name' => [
                'required',
                'string',
                'max:255'
            ],

            // Company Information
            'company_name' => [
                'nullable',
                'string',
                'max:255'
            ],
            'registration_no' => [
                'nullable',
                'string',
                'max:100'
            ],
            'tax_id' => [
                'nullable',
                'string',
                'max:100'
            ],

            // Billing Address
            'billing_address' => [
                'nullable',
                'string',
                'max:500'
            ],
            'billing_city' => [
                'nullable',
                'string',
                'max:100'
            ],
            'billing_state' => [
                'nullable',
                'string',
                'max:100'
            ],
            'billing_postcode' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9]{5}$/'
            ],
            'billing_country' => [
                'nullable',
                'string',
                'max:100'
            ],

            // Payment Terms
            'payment_terms' => [
                'required',
                'integer',
                'min:0',
                'max:365'
            ],
            'credit_limit' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99'
            ],

            // Primary Contact (PIC)
            'pic_name' => [
                'nullable',
                'string',
                'max:100'
            ],
            'pic_email' => [
                'nullable',
                'email',
                'max:255'
            ],
            'pic_phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9\-\+\s\(\)]+$/'
            ],

            // Partner Link
            'partner_id' => [
                'nullable',
                'exists:partners,id'
            ],

            // Notes
            'notes' => [
                'nullable',
                'string',
                'max:1000'
            ],

            // Status
            'status' => [
                'required',
                'string',
                'in:active,inactive,suspended'
            ],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'client_name.required' => 'Client name is required.',
            'client_name.max' => 'Client name cannot exceed 255 characters.',
            'client_code.unique' => 'This client code is already in use.',
            'company_name.max' => 'Company name cannot exceed 255 characters.',
            'registration_no.max' => 'Registration number cannot exceed 100 characters.',
            'tax_id.max' => 'Tax ID cannot exceed 100 characters.',
            
            'billing_address.max' => 'Billing address cannot exceed 500 characters.',
            'billing_city.max' => 'City cannot exceed 100 characters.',
            'billing_state.max' => 'State cannot exceed 100 characters.',
            'billing_postcode.regex' => 'Postcode must be exactly 5 digits.',
            'billing_country.max' => 'Country cannot exceed 100 characters.',
            
            'payment_terms.required' => 'Payment terms are required.',
            'payment_terms.integer' => 'Payment terms must be a number.',
            'payment_terms.min' => 'Payment terms must be at least 0 days.',
            'payment_terms.max' => 'Payment terms cannot exceed 365 days.',
            
            'credit_limit.numeric' => 'Credit limit must be a valid number.',
            'credit_limit.min' => 'Credit limit cannot be negative.',
            'credit_limit.max' => 'Credit limit is too large.',
            
            'pic_email.email' => 'Please enter a valid email address for PIC.',
            'pic_phone.regex' => 'Please enter a valid phone number (digits, spaces, dashes, and parentheses only).',
            
            'partner_id.exists' => 'Selected partner does not exist.',
            
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            
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
            'client_code' => 'client code',
            'client_name' => 'client name',
            'company_name' => 'company name',
            'registration_no' => 'registration number',
            'tax_id' => 'tax ID',
            'billing_address' => 'billing address',
            'billing_city' => 'city',
            'billing_state' => 'state',
            'billing_postcode' => 'postcode',
            'billing_country' => 'country',
            'payment_terms' => 'payment terms',
            'credit_limit' => 'credit limit',
            'pic_name' => 'PIC name',
            'pic_email' => 'PIC email',
            'pic_phone' => 'PIC phone',
            'partner_id' => 'partner',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty strings to null for nullable fields
        $nullableFields = [
            'company_name', 'registration_no', 'tax_id',
            'billing_address', 'billing_city', 'billing_state', 'billing_postcode',
            'pic_name', 'pic_email', 'pic_phone', 'partner_id', 'notes', 'credit_limit'
        ];
        
        foreach ($nullableFields as $field) {
            if ($this->has($field) && $this->{$field} === '') {
                $this->merge([$field => null]);
            }
        }

        // Set default country if not provided
        if (!$this->has('billing_country') || empty($this->billing_country)) {
            $this->merge(['billing_country' => 'Malaysia']);
        }

        // Ensure payment_terms is an integer
        if ($this->has('payment_terms') && !empty($this->payment_terms)) {
            $this->merge(['payment_terms' => (int) $this->payment_terms]);
        }

        // Convert credit_limit to decimal
        if ($this->has('credit_limit') && !empty($this->credit_limit)) {
            $this->merge(['credit_limit' => (float) str_replace(',', '', $this->credit_limit)]);
        }
    }
}
