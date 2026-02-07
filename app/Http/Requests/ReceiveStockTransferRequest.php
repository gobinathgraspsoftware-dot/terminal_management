<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('receive_stock_transfers');
    }

    public function rules(): array
    {
        return [
            'lines' => 'required|array',
            'lines.*.quantity_received' => 'required|numeric|min:0|max:99999.9999',
            'receive_remarks' => 'nullable|string|max:1000',
        ];
    }
}
