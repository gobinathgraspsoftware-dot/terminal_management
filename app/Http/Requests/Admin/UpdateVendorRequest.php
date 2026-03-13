<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit_vendors');
    }

    public function rules(): array
    {
        $vendorId = $this->route('vendor')->id;

        return [
            // vendor_code is NOT accepted on update — it's readonly
            'vendor_name' => 'required|string|max:255',
            'vendor_type_id' => [
                'required',
                'integer',
                Rule::exists('vendor_types', 'id')->where('is_active', true),
            ],
            'company_name' => 'nullable|string|max:255',
            'registration_no' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',

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

            // Payment Terms - nullable
            'payment_terms' => 'nullable|integer|min:0|max:365',

            // Other
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:active,inactive',

            // Branches
            'branches' => 'required|array|min:1',
            'branches.*.id' => 'nullable|integer',
            'branches.*.branch_name' => 'required|string|max:255',
            'branches.*.address' => 'nullable|string|max:500',
            'branches.*.state_id' => 'nullable|integer|exists:states,id',
            'branches.*.city_id' => 'nullable|integer|exists:cities,id',
            'branches.*.postcode' => 'nullable|string|max:20',
            'branches.*.country' => 'nullable|string|max:100',
            'branches.*.contact_person' => 'nullable|string|max:100',
            'branches.*.contact_email' => 'nullable|email|max:255',
            'branches.*.contact_phone' => 'nullable|string|max:20',
            'branches.*.is_primary' => 'nullable|boolean',
            'branches.*.status' => 'nullable|in:active,inactive',
        ];
    }

    public function attributes(): array
    {
        return [
            'vendor_name' => 'vendor name',
            'vendor_type_id' => 'vendor type',
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
            'branches.*.branch_name' => 'branch name',
            'branches.*.state_id' => 'state',
            'branches.*.city_id' => 'city',
            'branches.*.contact_email' => 'branch contact email',
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_name.required' => 'Vendor name is required.',
            'vendor_type_id.required' => 'Please select a vendor type.',
            'vendor_type_id.exists' => 'The selected vendor type is invalid or inactive.',
            'pic_email.email' => 'Please enter a valid email address.',
            'payment_terms.integer' => 'Payment terms must be a number.',
            'payment_terms.min' => 'Payment terms cannot be negative.',
            'payment_terms.max' => 'Payment terms cannot exceed 365 days.',
            'status.in' => 'Invalid status selected.',
            'branches.required' => 'At least one branch is required.',
            'branches.min' => 'At least one branch is required.',
            'branches.*.branch_name.required' => 'Branch name is required for all branches.',
            'branches.*.state_id.exists' => 'The selected state is invalid.',
            'branches.*.city_id.exists' => 'The selected city is invalid.',
            'branches.*.contact_email.email' => 'Please enter a valid email for the branch contact.',
        ];
    }

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

    protected function prepareForValidation(): void
    {
        if (!$this->has('country') || empty($this->country)) {
            $this->merge(['country' => 'Malaysia']);
        }
    }
}
