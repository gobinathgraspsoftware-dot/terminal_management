<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DispatchStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('dispatch_stock_transfers');
    }

    public function rules(): array
    {
        return [
            'lines' => 'required|array',
            'lines.*.quantity_dispatched' => 'required|numeric|min:0|max:99999.9999',
        ];
    }
}
