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
        return $this->user()->can('create_permissions');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z_]+$/',
            ],
            'module' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'The action field is required.',
            'action.regex' => 'The action must contain only lowercase letters and underscores.',
            'module.required' => 'The module field is required.',
            'module.regex' => 'The module must contain only lowercase letters, numbers, and underscores.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'action' => 'action',
            'module' => 'module',
            'description' => 'description',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure module is lowercase and trim spaces
        if ($this->has('module')) {
            $this->merge([
                'module' => strtolower(trim($this->module)),
            ]);
        }

        // Ensure action is lowercase and trim spaces
        if ($this->has('action')) {
            $this->merge([
                'action' => strtolower(trim($this->action)),
            ]);
        }
    }

    /**
     * Get validated data with computed permission name.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Add computed permission name
        if (isset($validated['action']) && isset($validated['module'])) {
            $validated['name'] = $validated['action'] . '_' . $validated['module'];
            $validated['guard_name'] = 'web';
        }

        return $validated;
    }
}
