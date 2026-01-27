<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * AssignTechnicianRequest
 * 
 * Validates requests for assigning a technician to a supervisor
 * Supports both assignment to supervisor and making technician independent
 * 
 * @package App\Http\Requests
 */
class AssignTechnicianRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can assign technicians
        return $this->user() && $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'technician_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // Ensure the user is a technician
                    $user = \App\Models\User::find($value);
                    if ($user && !$user->hasRole('technician')) {
                        $fail('The selected user must be a technician.');
                    }
                },
            ],
            'supervisor_id' => [
                'nullable', // Null means independent technician
                'integer',
                'exists:users,id',
                'different:technician_id', // Can't be their own supervisor
                function ($attribute, $value, $fail) {
                    // If supervisor_id is provided, ensure it's a supervisor
                    if ($value) {
                        $user = \App\Models\User::find($value);
                        if ($user && !$user->hasRole('supervisor')) {
                            $fail('The selected user must be a supervisor.');
                        }
                    }
                },
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
            'technician_id.required' => 'Please select a technician to assign.',
            'technician_id.exists' => 'The selected technician does not exist.',
            'supervisor_id.exists' => 'The selected supervisor does not exist.',
            'supervisor_id.different' => 'A technician cannot be their own supervisor.',
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
            'technician_id' => 'technician',
            'supervisor_id' => 'supervisor',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty supervisor_id to null for independent assignment
        if ($this->supervisor_id === '' || $this->supervisor_id === 'independent') {
            $this->merge([
                'supervisor_id' => null,
            ]);
        }
    }
}
