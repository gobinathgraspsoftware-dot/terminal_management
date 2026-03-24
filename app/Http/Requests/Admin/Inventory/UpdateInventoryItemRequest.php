<?php

namespace App\Http\Requests\Admin\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('inventory_item')?->id ?? $this->route('inventory_item');

        $rules = [
            'item_name'       => 'required|string|max:150',
            'job_category_id' => 'required|exists:job_categories,id',
            'item_type'       => 'required|in:router,accessory',
            'description'     => 'nullable|string|max:1000',
            'unit'            => 'required|string|max:30',
            'brand'           => 'nullable|string|max:100',
            'model'           => 'nullable|string|max:100',
            'reorder_level'   => 'required|integer|min:0',
            'status'          => 'required|in:active,inactive',
        ];

        if ($this->input('item_type') === 'router') {
            $rules['serial_number'] = 'required|string|max:100|unique:inventory_items,serial_number,' . $itemId;
        }

        return $rules;
    }
}
