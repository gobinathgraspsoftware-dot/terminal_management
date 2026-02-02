<?php

namespace App\Http\Requests\Admin\ChargeCatalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Charge Catalog Request
 *
 * Validation rules for creating a new charge catalog entry
 */
class StoreChargeCatalogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_charges');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'charge_code' => [
                'nullable',
                'string',
                'max:50',
                'unique:charge_catalog,charge_code',
                'regex:/^CHG\d{6}$/',
            ],
            'charge_name' => [
                'required',
                'string',
                'max:255',
            ],
            'charge_type' => [
                'required',
                Rule::in([
                    'installation',
                    'service',
                    'hardware',
                    'accessory',
                    'labour',
                    'transport',
                    'other',
                ]),
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'default_price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],
            'tax_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'is_taxable' => [
                'nullable',
                'boolean',
            ],
            'unit' => [
                'nullable',
                'string',
                'max:50',
            ],
            'status' => [
                'nullable',
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
            'charge_code' => 'charge code',
            'charge_name' => 'charge name',
            'charge_type' => 'charge type',
            'default_price' => 'default price',
            'tax_rate' => 'tax rate',
            'is_taxable' => 'taxable',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'charge_code.regex' => 'The charge code must be in format CHG000001.',
            'charge_code.unique' => 'This charge code already exists.',
            'charge_type.in' => 'Invalid charge type selected.',
            'default_price.max' => 'The default price cannot exceed 9,999,999,999.99.',
            'tax_rate.max' => 'The tax rate cannot exceed 100%.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox to boolean
        if ($this->has('is_taxable')) {
            $this->merge([
                'is_taxable' => $this->boolean('is_taxable'),
            ]);
        } else {
            $this->merge([
                'is_taxable' => false,
            ]);
        }

        // If tax_rate is empty and not taxable, set to 0
        if (!$this->is_taxable && empty($this->tax_rate)) {
            $this->merge([
                'tax_rate' => 0,
            ]);
        }
    }
}
