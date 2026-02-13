<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGrnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $grn = $this->route('grn');
        return $this->user()->can('update', $grn);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'grn_date' => 'required|date|before_or_equal:today',
            'receiving_depot_id' => 'required|exists:depots,id',
            'delivery_note_no' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            
            'lines' => 'required|array|min:1',
            'lines.*.po_line_id' => 'required|exists:purchase_order_lines,id',
            'lines.*.quantity_received' => 'required|numeric|min:0.0001',
            'lines.*.remarks' => 'nullable|string',
            
            'lines.*.serials' => 'nullable|array',
            'lines.*.serials.*.serial_no' => 'required_with:lines.*.serials|string|max:100',
            'lines.*.serials.*.hardware_type' => 'nullable|string|max:50',
            'lines.*.serials.*.device_type' => 'nullable|string|max:50',
            'lines.*.serials.*.telco' => 'nullable|string|max:50',
            'lines.*.serials.*.sim_quota' => 'nullable|string|max:50',
            'lines.*.serials.*.warranty_start' => 'nullable|date',
            'lines.*.serials.*.warranty_end' => 'nullable|date|after_or_equal:lines.*.serials.*.warranty_start',
            'lines.*.serials.*.remarks' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'grn_date.required' => 'GRN date is required',
            'grn_date.before_or_equal' => 'GRN date cannot be in the future',
            'receiving_depot_id.required' => 'Receiving depot is required',
            
            'lines.required' => 'At least one line item is required',
            'lines.min' => 'At least one line item is required',
            'lines.*.quantity_received.required' => 'Received quantity is required',
            'lines.*.quantity_received.min' => 'Received quantity must be greater than 0',
            
            'lines.*.serials.*.serial_no.required_with' => 'Serial number is required',
            'lines.*.serials.*.warranty_end.after_or_equal' => 'Warranty end date must be after or equal to warranty start date',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);
            
            foreach ($lines as $index => $line) {
                if (!empty($line['po_line_id']) && !empty($line['quantity_received'])) {
                    $poLine = \App\Models\PurchaseOrderLine::find($line['po_line_id']);
                    
                    if ($poLine) {
                        $outstanding = $poLine->quantity_ordered - $poLine->quantity_received - $poLine->quantity_cancelled;
                        
                        if ($line['quantity_received'] > $outstanding) {
                            $validator->errors()->add(
                                "lines.$index.quantity_received",
                                "Received quantity cannot exceed outstanding quantity ($outstanding)"
                            );
                        }
                        
                        if (!empty($line['serials'])) {
                            $serialCount = count($line['serials']);
                            
                            if ($serialCount != $line['quantity_received']) {
                                $validator->errors()->add(
                                    "lines.$index.serials",
                                    "Number of serials ($serialCount) must match received quantity ({$line['quantity_received']})"
                                );
                            }
                        }
                    }
                }
            }
        });
    }
}
