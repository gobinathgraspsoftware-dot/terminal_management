<?php

namespace App\Http\Requests\Admin;

use App\Models\InventorySerial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkSerialUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit_inventory');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'serial_ids' => [
                'required',
                'array',
                'min:1'
            ],
            'serial_ids.*' => [
                'required',
                'integer',
                'exists:inventory_serials,id'
            ],
            'operation' => [
                'required',
                'string',
                Rule::in(['update_status', 'transfer', 'reserve', 'unreserve'])
            ],
            
            // Status update fields
            'new_status' => [
                'required_if:operation,update_status',
                Rule::in([
                    InventorySerial::STATUS_IN_STOCK,
                    InventorySerial::STATUS_ISSUED_TO_TECH,
                    InventorySerial::STATUS_INSTALLED,
                    InventorySerial::STATUS_UNDER_SERVICE,
                    InventorySerial::STATUS_RETURNED_TO_VENDOR,
                    InventorySerial::STATUS_WASTED,
                    InventorySerial::STATUS_RESERVED,
                ])
            ],
            
            // Transfer fields
            'to_location_type' => [
                'required_if:operation,transfer',
                Rule::in([
                    InventorySerial::LOCATION_TYPE_DEPOT,
                    InventorySerial::LOCATION_TYPE_TECHNICIAN,
                ])
            ],
            'to_location_id' => [
                'required_if:operation,transfer',
                'integer',
                'min:1'
            ],
            
            // Common fields
            'remarks' => [
                'nullable',
                'string',
                'max:500'
            ],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'serial_ids.required' => 'Please select at least one serial number.',
            'serial_ids.array' => 'Invalid serial selection format.',
            'serial_ids.min' => 'Please select at least one serial number.',
            'serial_ids.*.exists' => 'One or more selected serials do not exist.',
            
            'operation.required' => 'Please select an operation to perform.',
            'operation.in' => 'Invalid operation selected.',
            
            'new_status.required_if' => 'Please select a new status.',
            'new_status.in' => 'Invalid status selected.',
            
            'to_location_type.required_if' => 'Please select a destination location type.',
            'to_location_type.in' => 'Invalid location type selected.',
            'to_location_id.required_if' => 'Please select a destination location.',
            'to_location_id.integer' => 'Invalid location selected.',
            
            'remarks.max' => 'Remarks must not exceed 500 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'serial_ids' => 'serial numbers',
            'new_status' => 'new status',
            'to_location_type' => 'destination location type',
            'to_location_id' => 'destination location',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert comma-separated string to array if needed
        if ($this->has('serial_ids') && is_string($this->serial_ids)) {
            $this->merge([
                'serial_ids' => explode(',', $this->serial_ids)
            ]);
        }
    }
}
