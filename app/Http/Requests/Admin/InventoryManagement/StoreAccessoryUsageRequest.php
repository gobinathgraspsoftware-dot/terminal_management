<?php

namespace App\Http\Requests\Admin\InventoryManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccessoryUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('create_accessory_usage');
    }

    public function rules(): array
    {
        return [
            'ticket_id'       => 'required|exists:tickets,id',
            'model_id'        => 'required|exists:terminal_models,id',
            'accessory_type'  => ['required', Rule::in(['sim_card', 'antenna', 'cable', 'adapter', 'other'])],
            'serial_id'       => 'nullable|exists:inventory_serials,id',
            'serial_no'       => 'nullable|string|max:100',
            'quantity'         => 'required|numeric|min:1|max:99',
            'condition'        => ['nullable', Rule::in(['new', 'good', 'damaged', 'defective'])],
            'remarks'          => 'nullable|string|max:1000',
            'deducted_from_location_type' => ['nullable', Rule::in(['depot', 'technician'])],
            'deducted_from_location_id'   => 'nullable|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'ticket_id'      => 'ticket',
            'model_id'       => 'accessory model',
            'accessory_type' => 'accessory type',
            'quantity'        => 'quantity',
        ];
    }
}
