@extends('layouts.app')

@section('title', 'Create Purchase Order - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-plus-circle"></i> Create Purchase Order</h2>
            @if($quotation)
                <p class="text-muted mb-0">Creating from Quotation: <strong>{{ $quotation->quotation_no }}</strong></p>
            @endif
        </div>
        <a href="{{ route('supervisor.purchase-orders.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- Form -->
    <form id="po-form" method="POST" action="{{ route('supervisor.purchase-orders.store') }}">
        @csrf
        
        @if($quotation)
            <input type="hidden" name="quotation_id" value="{{ $quotation->id }}">
        @endif

        @include('supervisor.purchase-orders._form', [
            'purchaseOrder' => null,
            'quotation' => $quotation,
            'submitText' => 'Create Purchase Order',
            'submitIcon' => 'check-circle'
        ])
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Form submission
    $('#po-form').on('submit', function(e) {
        e.preventDefault();
        
        // Collect form data
        const formData = new FormData(this);
        
        // Collect line items
        const lines = [];
        $('#lines-container tr').each(function(index) {
            const row = $(this);
            lines.push({
                line_no: index + 1,
                model_id: row.find('[name="lines[model_id][]"]').val(),
                description: row.find('[name="lines[description][]"]').val(),
                quantity_ordered: row.find('[name="lines[quantity][]"]').val(),
                unit: row.find('[name="lines[unit][]"]').val(),
                unit_price: row.find('[name="lines[unit_price][]"]').val(),
                discount_percent: row.find('[name="lines[discount_percent][]"]').val() || 0,
                discount_amount: row.find('[name="lines[discount_amount][]"]').val() || 0,
                tax_rate: row.find('[name="lines[tax_rate][]"]').val() || 0,
                tax_amount: row.find('[name="lines[tax_amount][]"]').val() || 0,
                line_total: row.find('[name="lines[line_total][]"]').val(),
                remarks: row.find('[name="lines[remarks][]"]').val() || ''
            });
        });

        if (lines.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Line Items',
                text: 'Please add at least one line item'
            });
            return;
        }

        formData.append('lines', JSON.stringify(lines));

        // Show loading
        Swal.fire({
            title: 'Creating Purchase Order...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Submit via AJAX
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        confirmButtonText: 'View Purchase Order'
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                }
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred while creating the purchase order.';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMsg += '\n\n';
                    Object.keys(errors).forEach(key => {
                        errorMsg += '• ' + errors[key].join(', ') + '\n';
                    });
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
            }
        });
    });

    @if($quotation)
        // Auto-populate from quotation
        $(document).ready(function() {
            // Set vendor
            $('#vendor_id').val({{ $quotation->vendor_id }}).trigger('change');
            
            // Populate lines from quotation
            @foreach($quotation->lines as $index => $line)
                addLineFromQuotation({
                    model_id: {{ $line->model_id }},
                    model_name: '{{ $line->model->model_name }}',
                    description: '{{ $line->description }}',
                    quantity: {{ $line->quantity }},
                    unit_price: {{ $line->unit_price }},
                    discount_percent: {{ $line->discount_percent ?? 0 }},
                    tax_rate: {{ $line->tax_rate ?? 0 }}
                });
            @endforeach
            
            calculateAllTotals();
        });
    @endif
});
</script>
@endpush
