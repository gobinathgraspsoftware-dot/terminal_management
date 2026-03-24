<?php

namespace App\Http\Requests\Admin\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'from_holder_type'  => 'required|in:warehouse,technician',
            'from_holder_id'    => 'nullable|exists:users,id',
            'to_holder_type'    => 'required|in:warehouse,technician',
            'to_holder_id'      => 'nullable|exists:users,id',
            'quantity'          => 'required|integer|min:1',
            'transfer_date'     => 'required|date',
            'reason'            => 'nullable|string|max:500',
            'remarks'           => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $fromType = $this->input('from_holder_type');
            $fromId   = $this->input('from_holder_id');
            $toType   = $this->input('to_holder_type');
            $toId     = $this->input('to_holder_id');

            // Cannot transfer to same holder
            if ($fromType === $toType && $fromId == $toId) {
                $validator->errors()->add('to_holder_id', 'Source and destination cannot be the same.');
            }

            // Technician holder requires holder_id
            if ($fromType === 'technician' && empty($fromId)) {
                $validator->errors()->add('from_holder_id', 'Source technician is required.');
            }
            if ($toType === 'technician' && empty($toId)) {
                $validator->errors()->add('to_holder_id', 'Destination technician is required.');
            }
        });
    }
}
