<?php

namespace App\Http\Requests\Admin\TerminalCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTerminalCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\TerminalCategory::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category_code' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Z0-9]+$/',
                'unique:terminal_categories,category_code',
            ],
            'category_name' => [
                'required',
                'string',
                'max:100',
            ],
            'category_type' => [
                'required',
                Rule::in(['terminal', 'router', 'sim', 'accessory', 'other']),
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_serial_tracked' => [
                'required',
                'boolean',
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
            'category_code' => 'category code',
            'category_name' => 'category name',
            'category_type' => 'category type',
            'is_serial_tracked' => 'serial tracking',
            'sort_order' => 'sort order',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_code.regex' => 'The category code must contain only uppercase letters and numbers.',
            'category_code.unique' => 'This category code is already in use.',
            'category_type.in' => 'Please select a valid category type.',
            'status.in' => 'Please select a valid status.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert category_code to uppercase if provided
        if ($this->has('category_code') && $this->category_code) {
            $this->merge([
                'category_code' => strtoupper($this->category_code),
            ]);
        }

        // Convert checkbox value to boolean
        if ($this->has('is_serial_tracked')) {
            $this->merge([
                'is_serial_tracked' => filter_var($this->is_serial_tracked, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
