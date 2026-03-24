<?php

namespace App\Http\Requests\Admin\Inventory;

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
            'quantity'          => 'required|integer|min:1',
            'movement_date'     => 'required|date',
            'reason'            => 'nullable|string|max:500',
            'remarks'           => 'nullable|string|max:1000',
        ];
    }
}
