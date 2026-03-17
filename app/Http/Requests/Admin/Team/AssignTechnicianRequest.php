<?php

namespace App\Http\Requests\Admin\Team;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates single technician assignment.
 * Used by: Admin\TeamController::assign()
 *
 * NOTE: supervisor_id is REQUIRED — all technicians must have a supervisor.
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
            'supervisor_id' => 'required|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'technician_id.required' => 'Please select a technician.',
            'technician_id.exists' => 'Selected technician does not exist.',
            'supervisor_id.required' => 'Please select a supervisor. All technicians must be assigned to a supervisor.',
            'supervisor_id.exists' => 'Selected supervisor does not exist.',
        ];
    }
}
