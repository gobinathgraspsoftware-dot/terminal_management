<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdatePartnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('edit_partners');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $partnerId = $this->route('partner')->id;

        return [
            // Partner Code (read-only on update, but validate if provided)
            'partner_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('partners', 'partner_code')->ignore($partnerId)
            ],

            // Partner Name
            'partner_name' => [
                'required',
                'string',
                'max:255'
            ],

            // PIC Details
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

            // Address Fields
            'address' => [
                'nullable',
                'string',
                'max:500'
            ],
            'city' => [
                'nullable',
                'string',
                'max:100'
            ],
            'state' => [
                'nullable',
                'string',
                'max:100'
            ],
            'postcode' => [
                'nullable',
                'string',
                'max:20'
            ],
            'country' => [
                'nullable',
                'string',
                'max:100'
            ],

            // SLA Rules (Array from form)
            'sla_rules' => [
                'nullable',
                'array'
            ],
            'sla_rules.*.sla_type' => [
                'nullable',
                'string',
                'max:50'
            ],
            'sla_rules.*.priority' => [
                'nullable',
                'string',
                'in:low,medium,high,critical'
            ],
            'sla_rules.*.response_hours' => [
                'nullable',
                'integer',
                'min:1',
                'max:720'
            ],
            'sla_rules.*.resolution_hours' => [
                'nullable',
                'integer',
                'min:1',
                'max:720'
            ],
            'sla_rules.*.escalation_enabled' => [
                'nullable',
            ],
            'sla_rules.*.escalation_hours' => [
                'nullable',
                'integer',
                'min:1',
                'max:720'
            ],

            // Job Intake Method
            'job_intake_method' => [
                'required',
                'string',
                'in:manual,import,api'
            ],

            // API Key (for API integration)
            'api_key' => [
                'nullable',
                'string',
                'max:255'
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
            'partner_name.required' => 'Partner name is required.',
            'partner_name.max' => 'Partner name cannot exceed 255 characters.',
            'partner_code.unique' => 'This partner code is already in use.',
            'pic_email.email' => 'Please enter a valid email address for PIC.',
            'pic_phone.regex' => 'Please enter a valid phone number (digits, spaces, dashes, and parentheses only).',
            'job_intake_method.required' => 'Please select a job intake method.',
            'job_intake_method.in' => 'Invalid job intake method selected.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Invalid status selected.',
            'sla_rules.*.priority.in' => 'SLA priority must be low, medium, high, or critical.',
            'sla_rules.*.response_hours.min' => 'Response hours must be at least 1.',
            'sla_rules.*.resolution_hours.min' => 'Resolution hours must be at least 1.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'partner_code' => 'partner code',
            'partner_name' => 'partner name',
            'pic_name' => 'PIC name',
            'pic_email' => 'PIC email',
            'pic_phone' => 'PIC phone',
            'job_intake_method' => 'job intake method',
            'sla_rules.*.sla_type' => 'SLA type',
            'sla_rules.*.priority' => 'SLA priority',
            'sla_rules.*.response_hours' => 'response hours',
            'sla_rules.*.resolution_hours' => 'resolution hours',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty string to null for nullable fields
        $nullableFields = ['pic_name', 'pic_email', 'pic_phone', 
                          'address', 'city', 'state', 'postcode', 'api_key', 'notes'];
        
        foreach ($nullableFields as $field) {
            if ($this->has($field) && $this->{$field} === '') {
                $this->merge([$field => null]);
            }
        }

        // Process SLA rules - convert escalation_enabled to boolean
        if ($this->has('sla_rules') && is_array($this->sla_rules)) {
            $processedRules = [];
            foreach ($this->sla_rules as $index => $rule) {
                // Skip completely empty rules
                if (empty($rule['sla_type'])) {
                    continue;
                }
                
                $processedRules[] = [
                    'sla_type' => $rule['sla_type'] ?? '',
                    'priority' => $rule['priority'] ?? 'medium',
                    'response_hours' => (int) ($rule['response_hours'] ?? 24),
                    'resolution_hours' => (int) ($rule['resolution_hours'] ?? 48),
                    'escalation_enabled' => filter_var($rule['escalation_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'escalation_hours' => !empty($rule['escalation_hours']) ? (int) $rule['escalation_hours'] : null,
                ];
            }
            $this->merge(['sla_rules' => $processedRules]);
        }
    }
}
