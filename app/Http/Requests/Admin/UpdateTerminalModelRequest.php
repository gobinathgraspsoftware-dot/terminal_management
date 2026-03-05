<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTerminalModelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit_models');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // FIX: Route parameter is 'terminalModel' (camelCase) not 'terminal_model'
        // Route::resource('', Controller)->parameters(['' => 'terminalModel'])
        // Using route('terminal_model') returned null, causing unique validation
        // to fail (not ignoring current record) → 302 redirect
        $terminalModel = $this->route('terminalModel');
        $terminalModelId = is_object($terminalModel) ? $terminalModel->id : $terminalModel;

        return [
            'model_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('terminal_models', 'model_code')->ignore($terminalModelId),
                'regex:/^[A-Z0-9\-]+$/',
            ],
            'model_name' => [
                'required',
                'string',
                'max:100',
            ],
            'category_id' => [
                'required',
                'exists:terminal_categories,id',
            ],
            'brand' => [
                'nullable',
                'string',
                'max:100',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'specifications' => [
                'nullable',
                'array',
            ],
            'specifications.*.key' => [
                'nullable',
                'string',
                'max:100',
            ],
            'specifications.*.value' => [
                'nullable',
                'string',
                'max:255',
            ],
            'is_serial_tracked' => [
                'nullable',
                'boolean',
            ],
            'warranty_months' => [
                'nullable',
                'integer',
                'min:0',
                'max:120',
            ],
            'default_accessories' => [
                'nullable',
                'array',
            ],
            'default_accessories.*' => [
                'nullable',
                'exists:terminal_models,id',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:2048',
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'model_code' => 'model code',
            'model_name' => 'model name',
            'category_id' => 'category',
            'brand' => 'brand',
            'description' => 'description',
            'specifications' => 'specifications',
            'is_serial_tracked' => 'serial tracking',
            'warranty_months' => 'warranty period',
            'default_accessories' => 'default accessories',
            'image' => 'model image',
            'sort_order' => 'sort order',
            'status' => 'status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'model_code.unique' => 'This model code is already in use.',
            'model_code.regex' => 'Model code must contain only uppercase letters, numbers, and hyphens.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif.',
            'image.max' => 'The image may not be greater than 2MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string '1'/'0' to boolean for is_serial_tracked
        if ($this->has('is_serial_tracked')) {
            $this->merge([
                'is_serial_tracked' => filter_var($this->is_serial_tracked, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
