<?php

namespace App\Http\Requests\Admin\Team;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates bulk technician assignment.
 * Used by: Admin\TeamController::bulkAssign()
 *
 * NOTE: supervisor_id is REQUIRED — all technicians must have a supervisor.
 * NOTE: Only INTERNAL supervisors can have technicians assigned.
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

    /**
     * Additional validation after basic rules pass.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->supervisor_id) {
                $supervisor = User::find($this->supervisor_id);
                if ($supervisor) {
                    if (!$supervisor->hasRole('supervisor')) {
                        $validator->errors()->add('supervisor_id', 'Selected user is not a supervisor.');
                    } elseif ($supervisor->isExternalSupervisor()) {
                        $validator->errors()->add('supervisor_id', 'Cannot assign technicians to an external supervisor. Only internal supervisors can have teams.');
                    }
                }
            }
        });
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
