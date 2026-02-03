<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\InventorySerial;

class StoreInventorySerialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_inventory');
    }

    public function rules(): array
    {
        return [
            'serial_no'             => 'required|string|max:100|unique:inventory_serials,serial_no',
            'model_id'              => 'required|exists:terminal_models,id',
            'hardware_type'         => 'nullable|string|max:100',
            'device_type'           => 'nullable|string|max:100',
            'telco'                 => 'nullable|string|max:100',
            'sim_quota'             => 'nullable|string|max:100',
            'current_status'        => 'required|in:' . implode(',', array_keys(InventorySerial::STATUS_OPTIONS)),
            'current_location_type' => 'required|in:' . implode(',', array_keys(InventorySerial::LOCATION_TYPE_OPTIONS)),
            'current_location_id'   => 'required|integer|min:1',
            'grn_id'                => 'nullable|exists:grns,id',
            'grn_date'              => 'nullable|date',
            'po_id'                 => 'nullable|exists:purchase_orders,id',
            'warranty_start'        => 'nullable|date',
            'warranty_end'          => 'nullable|date|after_or_equal:warranty_start',
            'purchase_price'        => 'nullable|numeric|min:0|max:9999999.99',
            'remarks'               => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'serial_no.unique'           => 'This serial number already exists in the system.',
            'serial_no.required'         => 'Serial number is required.',
            'model_id.required'          => 'Please select a terminal model.',
            'model_id.exists'            => 'Selected terminal model does not exist.',
            'current_location_id.required' => 'Please specify a location.',
            'warranty_end.after_or_equal'  => 'Warranty end date must be after or equal to warranty start date.',
        ];
    }

    public function attributes(): array
    {
        return [
            'serial_no'             => 'serial number',
            'model_id'              => 'terminal model',
            'current_status'        => 'status',
            'current_location_type' => 'location type',
            'current_location_id'   => 'location',
            'grn_id'                => 'GRN',
            'po_id'                 => 'purchase order',
            'warranty_start'        => 'warranty start date',
            'warranty_end'          => 'warranty end date',
            'purchase_price'        => 'purchase price',
        ];
    }
}
