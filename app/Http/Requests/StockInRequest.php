<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|integer|min:1',
            'router_ids' => 'nullable|array',
            'router_ids.*' => 'nullable|string|max:100',
            'reason' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:1000',
            'stockin_date' => 'required|date|before_or_equal:today',
            'stock_type' => 'required|in:router,accessory',
            // Router-specific fields (for new router stock in)
            'item_name' => 'nullable|string|max:150',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'accessory_type' => 'nullable|in:sim_card,antenna',
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_item_id.required' => 'Please select an inventory item.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be at least 1.',
            'stockin_date.required' => 'Stock In Date is required.',
            'stockin_date.before_or_equal' => 'Stock In Date cannot be in the future.',
            'router_ids.array' => 'Router IDs must be provided as a list.',
        ];
    }
}
