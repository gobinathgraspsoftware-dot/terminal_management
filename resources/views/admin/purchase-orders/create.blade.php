@extends('layouts.app')

@section('title', 'Create Purchase Order - TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-plus-circle"></i> Create Purchase Order</h2>
        <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <form id="po-form">
        @csrf
        @include('admin.purchase-orders._form', ['submitText' => 'Create Purchase Order'])
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#po-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validate lines exist
        const lineCount = $('#lines-container tr').length;
        if (lineCount === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please add at least one line item'
            });
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'Creating Purchase Order...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Manually build form data with correct structure
        const formData = {
            _token: $('input[name="_token"]').val(),
            vendor_id: $('select[name="vendor_id"]').val(),
            po_date: $('input[name="po_date"]').val(),
            delivery_date: $('input[name="delivery_date"]').val(),
            delivery_address: $('textarea[name="delivery_address"]').val(),
            receiving_depot_id: $('select[name="receiving_depot_id"]').val(),
            currency: $('select[name="currency"]').val(),
            reference: $('input[name="reference"]').val(),
            payment_terms: $('input[name="payment_terms"]').val(),
            terms_conditions: $('textarea[name="terms_conditions"]').val(),
            notes: $('textarea[name="notes"]').val(),
            quotation_id: $('input[name="quotation_id"]').val(),
            lines: []
        };
        
        // Build lines array - use class selectors instead of name attribute
        $('#lines-container tr').each(function(index) {
            const $row = $(this);
            
            // Get values using class selectors (more reliable)
            const modelId = $row.find('.model-select').val();
            const description = $row.find('input[name*="description"]').val() || '';
            const quantity = $row.find('.qty-input').val();
            const unit = $row.find('select[name*="unit"]').val();
            const unitPrice = $row.find('.price-input').val();
            const taxRate = $row.find('.tax-input').val() || '0';
            const lineTotal = $row.find('.line-total').val();
            
            // Debug log each line
            console.log('Line ' + index + ':', {
                modelId: modelId,
                description: description,
                quantity: quantity,
                unit: unit,
                unitPrice: unitPrice,
                taxRate: taxRate,
                lineTotal: lineTotal
            });
            
            // Only add if we have required fields
            if (modelId && quantity && unitPrice) {
                const line = {
                    model_id: modelId,
                    description: description,
                    quantity_ordered: quantity,
                    unit: unit,
                    unit_price: unitPrice,
                    tax_rate: taxRate,
                    line_total: lineTotal
                };
                formData.lines.push(line);
            } else {
                console.error('Missing required fields in line ' + index);
            }
        });
        
        console.log('Final formData:', formData);
        
        // Validate we actually collected lines
        if (formData.lines.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Could not read line items. Please check all fields are filled correctly.'
            });
            return;
        }
        
        $.ajax({
            url: '{{ route('admin.purchase-orders.store') }}',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'An error occurred'
                    });
                }
            },
            error: function(xhr) {
                Swal.close();
                
                console.error('AJAX Error:', xhr);
                
                let errorMsg = 'An error occurred';
                
                if (xhr.responseJSON) {
                    console.error('Response JSON:', xhr.responseJSON);
                    
                    if (xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    
                    if (xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        errorMsg = '<ul style="text-align: left;">';
                        Object.keys(errors).forEach(key => {
                            errors[key].forEach(error => {
                                errorMsg += '<li>' + error + '</li>';
                            });
                        });
                        errorMsg += '</ul>';
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: errorMsg
                });
            }
        });
    });
});
</script>
@endpush
