<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockOutRequest extends FormRequest
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
            'ticket_id' => 'nullable|exists:tickets,id',
            'technician_id' => 'nullable|exists:users,id',
            'reason' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:1000',
            'movement_date' => 'nullable|date|before_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_item_id.required' => 'Please select an inventory item.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be at least 1.',
            'ticket_id.exists' => 'Selected ticket is invalid.',
        ];
    }
}
