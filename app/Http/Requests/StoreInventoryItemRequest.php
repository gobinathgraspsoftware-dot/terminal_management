<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_name' => 'required|string|max:150',
            'job_category_id' => 'required|exists:job_categories,id',
            'item_type' => 'required|in:router,accessory',
            'accessory_type' => 'required_if:item_type,accessory|nullable|in:sim_card,antenna',
            'description' => 'nullable|string|max:1000',
            'unit' => 'nullable|string|max:30',
            'serial_number' => 'required_if:item_type,router|nullable|string|max:100|unique:inventory_items,serial_number',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'reorder_level' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'serial_number.required_if' => 'Terminal ID is required for routers.',
            'serial_number.unique' => 'This Terminal ID already exists in inventory.',
            'accessory_type.required_if' => 'Please select the accessory type (SIM Card or Antenna).',
        ];
    }
}
