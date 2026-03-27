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
            'stock_type'         => 'required|in:router,accessory',
            'inventory_item_id'  => 'nullable|exists:inventory_items,id',
            'quantity'           => 'required|integer|min:1',
            'stockin_date'       => 'required|date|before_or_equal:today',
            'reason'             => 'nullable|string|max:500',
            'remarks'            => 'nullable|string|max:1000',

            // Router IDs array — required when stock_type is router
            'router_ids'         => 'nullable|array',
            'router_ids.*'       => 'nullable|string|max:100',

            // New router item fields (when creating a new router during stock-in)
            'item_name'          => 'nullable|required_if:inventory_item_id,|string|max:150',
            'brand'              => 'nullable|string|max:100',
            'model'              => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'stock_type.required'                => 'Please select a stock type.',
            'quantity.required'                   => 'Quantity is required.',
            'quantity.min'                        => 'Quantity must be at least 1.',
            'stockin_date.required'              => 'Stock in date is required.',
            'stockin_date.before_or_equal'       => 'Stock in date cannot be in the future.',
            'router_ids.array'                   => 'Router IDs must be an array.',
            'router_ids.*.string'                => 'Each Router ID must be a string.',
            'router_ids.*.max'                   => 'Each Router ID cannot exceed 100 characters.',
            'item_name.required_if'              => 'Router name is required when creating a new router.',
        ];
    }
}
