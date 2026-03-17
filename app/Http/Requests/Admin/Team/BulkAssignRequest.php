<?php

namespace App\Http\Requests\Admin\Team;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates bulk technician assignment.
 * Used by: Admin\TeamController::bulkAssign()
 *
 * NOTE: supervisor_id is REQUIRED — all technicians must have a supervisor.
 */
class BulkAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'technician_ids' => 'required|array|min:1',
            'technician_ids.*' => 'exists:users,id',
            'supervisor_id' => 'required|exists:users,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->technician_ids && !is_array($this->technician_ids)) {
            $this->merge(['technician_ids' => [$this->technician_ids]]);
        }
    }

    public function messages(): array
    {
        return [
            'technician_ids.required' => 'Please select at least one technician.',
            'technician_ids.min' => 'Please select at least one technician.',
            'supervisor_id.required' => 'Please select a supervisor. All technicians must be assigned to a supervisor.',
            'supervisor_id.exists' => 'Selected supervisor does not exist.',
        ];
    }
}
