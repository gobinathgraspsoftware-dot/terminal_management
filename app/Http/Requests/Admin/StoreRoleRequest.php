<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    /**
     * System roles that cannot be created.
     */
    protected array $systemRoles = ['admin', 'supervisor', 'technician'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:roles,name',
                'regex:/^[a-z][a-z0-9_-]*$/',
                Rule::notIn($this->systemRoles),
            ],
            'display_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'permissions' => [
                'nullable',
                'array',
            ],
            'permissions.*' => [
                'exists:permissions,id',
            ],
            'guard_name' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The role name is required.',
            'name.unique' => 'A role with this name already exists.',
            'name.regex' => 'The role name must start with a letter and contain only lowercase letters, numbers, underscores, and hyphens.',
            'name.not_in' => 'You cannot create a role with a system role name.',
            'name.max' => 'The role name cannot exceed 255 characters.',
            'display_name.max' => 'The display name cannot exceed 255 characters.',
            'description.max' => 'The description cannot exceed 500 characters.',
            'permissions.array' => 'Permissions must be provided as an array.',
            'permissions.*.exists' => 'One or more selected permissions are invalid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'role name',
            'display_name' => 'display name',
            'permissions' => 'permissions',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert name to lowercase and replace spaces with underscores
        if ($this->has('name')) {
            $this->merge([
                'name' => strtolower(str_replace(' ', '_', trim($this->name))),
            ]);
        }

        // Set default guard name if not provided
        if (!$this->has('guard_name') || empty($this->guard_name)) {
            $this->merge([
                'guard_name' => 'web',
            ]);
        }
    }
}
