<?php

namespace App\Http\Requests\Admin\Team;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates single technician assignment.
 * Used by: Admin\TeamController::assign()
 */
class AssignTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'technician_id' => 'required|exists:users,id',
            'supervisor_id' => 'nullable|exists:users,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->supervisor_id === '' || $this->supervisor_id === '0') {
            $this->merge(['supervisor_id' => null]);
        }
    }

    public function messages(): array
    {
        return [
            'technician_id.required' => 'Please select a technician.',
            'technician_id.exists' => 'Selected technician does not exist.',
            'supervisor_id.exists' => 'Selected supervisor does not exist.',
        ];
    }
}
