<?php

namespace App\Http\Requests\Admin\Team;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates single technician assignment.
 * Used by: Admin\TeamController::assign()
 *
 * NOTE: supervisor_id is REQUIRED — all technicians must have a supervisor.
 * NOTE: Only INTERNAL supervisors can have technicians assigned.
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

            if ($this->technician_id) {
                $technician = User::find($this->technician_id);
                if ($technician && !$technician->hasRole('technician')) {
                    $validator->errors()->add('technician_id', 'Selected user is not a technician.');
                }
            }
        });
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
