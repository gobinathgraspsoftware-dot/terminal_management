<?php

namespace App\Http\Requests\Admin\Team;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * BulkAssignRequest
 * 
 * Validates bulk technician assignment. Admin only.
 * 
 * @package App\Http\Requests\Admin\Team
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
            'technician_ids' => ['required', 'array', 'min:1', 'max:100'],
            'technician_ids.*' => ['required', 'integer', 'exists:users,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'technician_ids.required' => 'Select at least one technician.',
            'technician_ids.min' => 'Select at least one technician.',
            'technician_ids.max' => 'Maximum 100 technicians per batch.',
            'supervisor_id.exists' => 'Supervisor not found.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->supervisor_id) {
                $sup = User::find($this->supervisor_id);
                if ($sup && !$sup->hasRole('supervisor')) {
                    $validator->errors()->add('supervisor_id', 'User is not a supervisor.');
                }
                if (in_array($this->supervisor_id, $this->technician_ids ?? [])) {
                    $validator->errors()->add('technician_ids', 'Supervisor cannot be in own team.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->supervisor_id === '' || $this->supervisor_id === '0') {
            $this->merge(['supervisor_id' => null]);
        }
        if ($this->technician_ids && !is_array($this->technician_ids)) {
            $this->merge(['technician_ids' => [$this->technician_ids]]);
        }
    }
}
