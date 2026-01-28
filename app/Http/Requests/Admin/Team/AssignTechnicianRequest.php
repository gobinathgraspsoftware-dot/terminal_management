<?php

namespace App\Http\Requests\Admin\Team;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * AssignTechnicianRequest
 * 
 * Validates single technician assignment. Admin only.
 * 
 * @package App\Http\Requests\Admin\Team
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
            'technician_id' => ['required', 'integer', 'exists:users,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id', 'different:technician_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'technician_id.required' => 'Please select a technician.',
            'technician_id.exists' => 'Technician not found.',
            'supervisor_id.exists' => 'Supervisor not found.',
            'supervisor_id.different' => 'Cannot assign to self.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->technician_id) {
                $tech = User::find($this->technician_id);
                if ($tech && !$tech->hasRole('technician')) {
                    $validator->errors()->add('technician_id', 'User is not a technician.');
                }
            }
            if ($this->supervisor_id) {
                $sup = User::find($this->supervisor_id);
                if ($sup && !$sup->hasRole('supervisor')) {
                    $validator->errors()->add('supervisor_id', 'User is not a supervisor.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->supervisor_id === '' || $this->supervisor_id === '0') {
            $this->merge(['supervisor_id' => null]);
        }
    }
}
