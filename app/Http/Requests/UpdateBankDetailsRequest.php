<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only technicians can update bank details
        return $this->user()->hasRole('Technician');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_account_no' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            'bank_account_name' => ['required', 'string', 'max:100'],
            'ifsc_code' => ['required', 'string', 'max:20', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'branch_name' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bank_name' => 'bank name',
            'bank_account_no' => 'account number',
            'bank_account_name' => 'account holder name',
            'ifsc_code' => 'IFSC code',
            'branch_name' => 'branch name',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bank_account_no.regex' => 'The account number must contain only digits.',
            'ifsc_code.regex' => 'The IFSC code format is invalid. It should be 11 characters (e.g., SBIN0001234).',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert IFSC code to uppercase
        if ($this->ifsc_code) {
            $this->merge([
                'ifsc_code' => strtoupper($this->ifsc_code),
            ]);
        }
    }
}
