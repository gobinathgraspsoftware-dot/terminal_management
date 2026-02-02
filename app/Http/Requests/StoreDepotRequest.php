<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_depots');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'depot_name' => ['required', 'string', 'max:255'],
            'depot_type' => ['required', Rule::in(['main', 'regional', 'technician'])],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'pic_name' => ['nullable', 'string', 'max:100'],
            'pic_phone' => ['nullable', 'string', 'max:20'],
            'pic_email' => ['nullable', 'email', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'depot_name' => 'depot name',
            'depot_type' => 'depot type',
            'pic_name' => 'person in charge name',
            'pic_phone' => 'person in charge phone',
            'pic_email' => 'person in charge email',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'depot_name.required' => 'The depot name field is required.',
            'depot_type.required' => 'Please select a depot type.',
            'depot_type.in' => 'The selected depot type is invalid.',
            'pic_email.email' => 'Please provide a valid email address for the person in charge.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox to boolean
        if ($this->has('is_default')) {
            $this->merge([
                'is_default' => $this->boolean('is_default')
            ]);
        }
    }
}
