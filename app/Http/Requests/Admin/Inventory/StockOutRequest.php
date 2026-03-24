<?php

namespace App\Http\Requests\Admin\Inventory;

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
            'technician_id'     => 'required|exists:users,id',
            'quantity'          => 'required|integer|min:1',
            'movement_date'     => 'required|date',
            'ticket_id'         => 'nullable|exists:tickets,id',
            'reason'            => 'nullable|string|max:500',
            'remarks'           => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'technician_id.required' => 'Please select a technician.',
            'technician_id.exists'   => 'Selected technician is invalid.',
        ];
    }
}
