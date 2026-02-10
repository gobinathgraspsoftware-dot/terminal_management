<?php

namespace App\Http\Requests\Admin\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_permissions') || $this->user()->hasRole('admin');
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
                'regex:/^[a-z0-9_]+$/', // Only lowercase letters, numbers, and underscores
                Rule::unique('permissions', 'name'),
            ],
            'group' => [
                'required',
                'string',
                'max:100',
            ],
            'action' => [
                'nullable',
                'string',
                'max:50',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
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
            'name.required' => 'Permission name is required.',
            'name.unique' => 'This permission already exists.',
            'name.regex' => 'Permission name must contain only lowercase letters, numbers, and underscores.',
            'group.required' => 'Permission group/module is required.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'permission name',
            'group' => 'module/group',
            'action' => 'action type',
            'description' => 'description',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate permission name from group and action if not provided directly
        if ($this->has('group') && $this->has('action') && !$this->filled('name')) {
            $this->merge([
                'name' => $this->action . '_' . $this->group,
            ]);
        }

        // Convert name to lowercase and replace spaces with underscores
        if ($this->filled('name')) {
            $this->merge([
                'name' => strtolower(str_replace([' ', '-'], '_', $this->name)),
            ]);
        }

        // Set default guard_name if not provided
        if (!$this->filled('guard_name')) {
            $this->merge([
                'guard_name' => 'web',
            ]);
        }
    }
}
