<?php

namespace App\Http\Requests\Admin\InventoryManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessReplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('create_replacements');
    }

    public function rules(): array
    {
        return [
            'replacement_date'       => 'required|date',
            'ticket_id'              => 'nullable|exists:tickets,id',
            'technician_id'          => 'nullable|exists:users,id',
            'site_id'                => 'nullable|exists:sites,id',
            'old_serial_id'          => 'nullable|exists:inventory_serials,id',
            'old_serial_no'          => 'nullable|string|max:100',
            'old_model_id'           => 'nullable|exists:terminal_models,id',
            'new_serial_id'          => 'required|exists:inventory_serials,id',
            'new_serial_no'          => 'nullable|string|max:100',
            'new_model_id'           => 'required|exists:terminal_models,id',
            'old_device_condition'   => ['required', Rule::in(['good', 'damaged', 'defective', 'wasted'])],
            'old_device_destination' => ['required', Rule::in(['return_to_depot', 'return_to_vendor', 'wastage'])],
            'return_depot_id'        => 'required_if:old_device_destination,return_to_depot|nullable|exists:depots,id',
            'reason'                 => 'required|string|max:1000',
            'remarks'                => 'nullable|string|max:2000',
        ];
    }

    public function attributes(): array
    {
        return [
            'replacement_date'       => 'replacement date',
            'new_serial_id'          => 'new device serial',
            'new_model_id'           => 'new device model',
            'old_device_condition'   => 'old device condition',
            'old_device_destination' => 'old device destination',
            'return_depot_id'        => 'return depot',
        ];
    }

    public function messages(): array
    {
        return [
            'return_depot_id.required_if' => 'Please select a return depot when destination is "Return to Depot".',
        ];
    }
}
