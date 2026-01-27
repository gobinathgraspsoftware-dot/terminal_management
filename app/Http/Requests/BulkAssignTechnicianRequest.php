<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BulkAssignTechnicianRequest
 * 
 * Validates requests for bulk assigning multiple technicians to a supervisor
 * Supports assigning multiple technicians to one supervisor or making them independent
 * 
 * @package App\Http\Requests
 */
class BulkAssignTechnicianRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can perform bulk assignments
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
            'technician_ids' => [
                'required',
                'array',
                'min:1',
                'max:50', // Limit bulk operations to 50 at a time
            ],
            'technician_ids.*' => [
                'required',
                'integer',
                'exists:users,id',
                'distinct', // No duplicate IDs
                function ($attribute, $value, $fail) {
                    // Ensure each user is a technician
                    $user = \App\Models\User::find($value);
                    if ($user && !$user->hasRole('technician')) {
                        $fail("User {$user->name} is not a technician.");
                    }
                },
            ],
            'supervisor_id' => [
                'nullable', // Null means make all independent
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // If supervisor_id is provided, ensure it's a supervisor
                    if ($value) {
                        $user = \App\Models\User::find($value);
                        if ($user && !$user->hasRole('supervisor')) {
                            $fail('The selected user must be a supervisor.');
                        }

                        // Ensure supervisor is not in the technician list
                        if (in_array($value, $this->technician_ids ?? [])) {
                            $fail('A supervisor cannot be assigned to themselves.');
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
            'technician_ids.required' => 'Please select at least one technician.',
            'technician_ids.array' => 'Invalid technician selection format.',
            'technician_ids.min' => 'Please select at least one technician.',
            'technician_ids.max' => 'You can assign a maximum of 50 technicians at once.',
            'technician_ids.*.required' => 'Each technician selection is required.',
            'technician_ids.*.exists' => 'One or more selected technicians do not exist.',
            'technician_ids.*.distinct' => 'Duplicate technicians detected in selection.',
            'supervisor_id.exists' => 'The selected supervisor does not exist.',
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
            'technician_ids' => 'technicians',
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

        // Ensure technician_ids is an array
        if ($this->has('technician_ids') && is_string($this->technician_ids)) {
            $this->merge([
                'technician_ids' => json_decode($this->technician_ids, true) ?? [],
            ]);
        }

        // Remove empty values from array
        if (is_array($this->technician_ids)) {
            $this->merge([
                'technician_ids' => array_filter($this->technician_ids, function ($id) {
                    return !empty($id);
                }),
            ]);
        }
    }

    /**
     * Get validated data with additional processing
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        // Ensure technician_ids are unique integers
        if (isset($validated['technician_ids'])) {
            $validated['technician_ids'] = array_unique(
                array_map('intval', $validated['technician_ids'])
            );
        }

        return $validated;
    }
}
