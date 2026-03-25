<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'new_quantity' => 'required|integer|min:0',
            'reason' => 'required|string|min:10|max:1000',
            'remarks' => 'nullable|string|max:1000',
            'old_value' => 'nullable|string|max:255',
            'new_value' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_item_id.required' => 'Please select an inventory item.',
            'new_quantity.required' => 'New quantity is required.',
            'new_quantity.min' => 'Quantity cannot be negative.',
            'reason.required' => 'Reason for adjustment is mandatory.',
            'reason.min' => 'Reason must be at least 10 characters for audit purposes.',
        ];
    }
}
