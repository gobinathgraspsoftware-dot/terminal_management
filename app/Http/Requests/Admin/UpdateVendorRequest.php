<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit_vendors');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $vendorId = $this->route('vendor')->id;

        return [
            // Basic Information
            'vendor_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('vendors')->ignore($vendorId)->whereNull('deleted_at')
            ],
            'vendor_name' => 'required|string|max:255',
            'vendor_type' => 'required|in:supplier,subcon,courier,other',
            'company_name' => 'nullable|string|max:255',
            'registration_no' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',

            // Address Information
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',

            // Contact Information
            'pic_name' => 'nullable|string|max:100',
            'pic_email' => 'nullable|email|max:255',
            'pic_phone' => 'nullable|string|max:20',

            // Bank Details
            'bank_name' => [
                'nullable',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    $this->validateBankDetails($fail);
                }
            ],
            'bank_account_no' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:100',

            // Payment Terms
            'payment_terms' => 'required|integer|min:0|max:365',

            // Other
            'notes' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'vendor_code' => 'vendor code',
            'vendor_name' => 'vendor name',
            'vendor_type' => 'vendor type',
            'company_name' => 'company name',
            'registration_no' => 'registration number',
            'tax_id' => 'tax ID',
            'pic_name' => 'person in charge name',
            'pic_email' => 'person in charge email',
            'pic_phone' => 'person in charge phone',
            'bank_name' => 'bank name',
            'bank_account_no' => 'bank account number',
            'bank_account_name' => 'bank account name',
            'payment_terms' => 'payment terms',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'vendor_name.required' => 'Vendor name is required.',
            'vendor_type.required' => 'Please select a vendor type.',
            'vendor_type.in' => 'Invalid vendor type selected.',
            'pic_email.email' => 'Please enter a valid email address.',
            'payment_terms.required' => 'Payment terms are required.',
            'payment_terms.integer' => 'Payment terms must be a number.',
            'payment_terms.min' => 'Payment terms cannot be negative.',
            'payment_terms.max' => 'Payment terms cannot exceed 365 days.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Invalid status selected.',
        ];
    }

    /**
     * Validate bank details - if any field is filled, all must be filled
     */
    protected function validateBankDetails($fail): void
    {
        $bankName = $this->input('bank_name');
        $bankAccountNo = $this->input('bank_account_no');
        $bankAccountName = $this->input('bank_account_name');

        $filledFields = collect([$bankName, $bankAccountNo, $bankAccountName])
            ->filter(fn($value) => !empty($value))
            ->count();

        if ($filledFields > 0 && $filledFields < 3) {
            $fail('If providing bank details, all fields (Bank Name, Account Number, and Account Name) are required.');
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default country if not provided
        if (!$this->has('country') || empty($this->country)) {
            $this->merge(['country' => 'Malaysia']);
        }
    }
}
