<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve_stock_transfers');
    }

    public function rules(): array
    {
        return [
            'remarks' => 'nullable|string|max:500',
        ];
    }
}
