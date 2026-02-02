<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\RateCard;

class StoreRateCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_rate_cards');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'rate_card_name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'job_type' => [
                'required',
                'in:all,installation,service,repair,replacement,collection',
            ],
            'model_id' => [
                'nullable',
                'exists:terminal_models,id',
            ],
            'state' => [
                'nullable',
                'string',
                'max:100',
            ],
            'calculation_type' => [
                'required',
                'in:flat,per_terminal,percentage',
            ],
            'rate_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'min_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'lt:max_amount',
            ],
            'max_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'gt:min_amount',
            ],
            'effective_from' => [
                'required',
                'date',
            ],
            'effective_to' => [
                'nullable',
                'date',
                'after:effective_from',
            ],
            'status' => [
                'required',
                'in:active,inactive',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'rate_card_name.required' => 'Rate card name is required.',
            'job_type.required' => 'Job type is required.',
            'job_type.in' => 'Invalid job type selected.',
            'model_id.exists' => 'Selected terminal model does not exist.',
            'calculation_type.required' => 'Calculation type is required.',
            'calculation_type.in' => 'Invalid calculation type selected.',
            'rate_amount.required' => 'Rate amount is required.',
            'rate_amount.numeric' => 'Rate amount must be a valid number.',
            'rate_amount.min' => 'Rate amount cannot be negative.',
            'min_amount.lt' => 'Minimum amount must be less than maximum amount.',
            'max_amount.gt' => 'Maximum amount must be greater than minimum amount.',
            'effective_from.required' => 'Effective from date is required.',
            'effective_from.date' => 'Effective from must be a valid date.',
            'effective_to.after' => 'Effective to date must be after effective from date.',
            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status selected.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'rate_card_name' => 'rate card name',
            'job_type' => 'job type',
            'model_id' => 'terminal model',
            'calculation_type' => 'calculation type',
            'rate_amount' => 'rate amount',
            'min_amount' => 'minimum amount',
            'max_amount' => 'maximum amount',
            'effective_from' => 'effective from date',
            'effective_to' => 'effective to date',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean numeric values
        if ($this->has('rate_amount')) {
            $this->merge([
                'rate_amount' => $this->cleanNumeric($this->rate_amount),
            ]);
        }

        if ($this->has('min_amount')) {
            $this->merge([
                'min_amount' => $this->cleanNumeric($this->min_amount),
            ]);
        }

        if ($this->has('max_amount')) {
            $this->merge([
                'max_amount' => $this->cleanNumeric($this->max_amount),
            ]);
        }

        // Set model_id to null if empty string
        if ($this->model_id === '') {
            $this->merge(['model_id' => null]);
        }

        // Set state to null if empty string
        if ($this->state === '') {
            $this->merge(['state' => null]);
        }

        // Set effective_to to null if empty string
        if ($this->effective_to === '') {
            $this->merge(['effective_to' => null]);
        }
    }

    /**
     * Clean numeric value by removing commas
     */
    private function cleanNumeric($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return str_replace(',', '', $value);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation: Check for overlapping rate cards with same criteria
            $this->checkForOverlappingRates($validator);

            // Validate percentage rates
            if ($this->calculation_type === 'percentage') {
                $this->validatePercentageRate($validator);
            }
        });
    }

    /**
     * Check for overlapping rate cards
     */
    private function checkForOverlappingRates($validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $query = RateCard::where('status', 'active')
            ->where('job_type', $this->job_type);

        // Check model_id
        if ($this->model_id) {
            $query->where(function($q) {
                $q->where('model_id', $this->model_id)
                  ->orWhereNull('model_id');
            });
        } else {
            $query->whereNull('model_id');
        }

        // Check state
        if ($this->state) {
            $query->where(function($q) {
                $q->where('state', $this->state)
                  ->orWhereNull('state');
            });
        } else {
            $query->whereNull('state');
        }

        // Check date overlap
        $query->where(function($q) {
            $q->where(function($subQ) {
                // New card starts within existing range
                $subQ->where('effective_from', '<=', $this->effective_from)
                     ->where(function($dateQ) {
                         $dateQ->whereNull('effective_to')
                               ->orWhere('effective_to', '>=', $this->effective_from);
                     });
            })->orWhere(function($subQ) {
                // New card ends within existing range (if has end date)
                if ($this->effective_to) {
                    $subQ->where('effective_from', '<=', $this->effective_to)
                         ->where(function($dateQ) {
                             $dateQ->whereNull('effective_to')
                                   ->orWhere('effective_to', '>=', $this->effective_to);
                         });
                }
            })->orWhere(function($subQ) {
                // Existing card falls entirely within new card range
                $subQ->where('effective_from', '>=', $this->effective_from);
                if ($this->effective_to) {
                    $subQ->where(function($dateQ) {
                        $dateQ->whereNotNull('effective_to')
                              ->where('effective_to', '<=', $this->effective_to);
                    });
                }
            });
        });

        if ($query->exists()) {
            $validator->errors()->add(
                'effective_from',
                'A rate card with the same criteria already exists for this date range.'
            );
        }
    }

    /**
     * Validate percentage rate
     */
    private function validatePercentageRate($validator): void
    {
        if ($this->rate_amount > 100) {
            $validator->errors()->add(
                'rate_amount',
                'Percentage rate cannot exceed 100%.'
            );
        }
    }
}
