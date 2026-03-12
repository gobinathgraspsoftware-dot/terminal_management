<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_vendor_types');
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:100',
                Rule::unique('vendor_types')->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
