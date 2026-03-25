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
            'quantity' => 'required_if:stock_type,accessory|nullable|integer|min:1',
            'reason' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:1000',
            'item_condition' => 'nullable|in:good,faulty,damaged',
            'movement_date' => 'nullable|date|before_or_equal:today',
            // Router-specific fields (for new router stock in)
            'item_name' => 'nullable|string|max:150',
            'serial_number' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'stock_type' => 'required|in:router,accessory',
            'job_category_id' => 'nullable|exists:job_categories,id',
            'accessory_type' => 'nullable|in:sim_card,antenna',
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_item_id.required' => 'Please select an inventory item.',
            'quantity.required_if' => 'Quantity is required for accessories.',
            'quantity.min' => 'Quantity must be at least 1.',
            'movement_date.before_or_equal' => 'Movement date cannot be in the future.',
        ];
    }
}
