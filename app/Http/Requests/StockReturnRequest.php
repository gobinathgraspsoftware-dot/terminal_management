<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockReturnRequest extends FormRequest
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
            'item_condition' => 'required|in:good,faulty,damaged',
            'reason' => 'required|string|max:500',
            'remarks' => 'nullable|string|max:1000',
            'stockreturn_date' => 'required|date|before_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_item_id.required' => 'Please select an inventory item.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be at least 1.',
            'item_condition.required' => 'Please specify the item condition.',
            'reason.required' => 'Please provide a reason for the return.',
            'stockreturn_date.required' => 'Stock Return Date is required.',
            'stockreturn_date.before_or_equal' => 'Stock Return Date cannot be in the future.',
        ];
    }
}
