<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('send_purchase_orders');
    }

    public function rules(): array
    {
        return [
            'email_to' => 'required|email',
            'email_cc' => 'nullable|array',
            'email_cc.*' => 'email',
            'email_message' => 'nullable|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'email_to' => 'recipient email',
            'email_cc.*' => 'CC email',
        ];
    }
}
