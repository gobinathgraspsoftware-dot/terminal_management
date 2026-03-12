<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit_vendor_types');
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:100',
                Rule::unique('vendor_types')->ignore($this->route('vendor_type'))->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
