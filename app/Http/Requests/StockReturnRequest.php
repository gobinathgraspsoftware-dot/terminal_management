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
            'from_holder_type' => 'required|in:warehouse,technician',
            'from_holder_id' => 'required_if:from_holder_type,technician|nullable|exists:users,id',
            'ticket_id' => 'nullable|exists:tickets,id',
            'item_condition' => 'required|in:good,faulty,damaged',
            'reason' => 'required|string|max:500',
            'remarks' => 'nullable|string|max:1000',
            'movement_date' => 'nullable|date|before_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_item_id.required' => 'Please select an inventory item.',
            'quantity.required' => 'Quantity is required.',
            'from_holder_id.required_if' => 'Please select the technician returning the item.',
            'item_condition.required' => 'Please specify the item condition.',
            'reason.required' => 'Please provide a reason for the return.',
        ];
    }
}
