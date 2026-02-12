@extends('layouts.app')

@section('title', 'Edit Purchase Order - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-pencil-square"></i> Edit Purchase Order</h2>
            <p class="text-muted mb-0">PO No: <strong>{{ $purchaseOrder->po_no }}</strong></p>
        </div>
        <a href="{{ route('supervisor.purchase-orders.show', $purchaseOrder) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Details
        </a>
    </div>

    @if($purchaseOrder->status !== 'draft' && $purchaseOrder->status !== 'pending_approval')
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> This purchase order is in <strong>{{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }}</strong> status and cannot be edited.
        </div>
    @else
        <!-- Form -->
        <form id="po-form" method="POST" action="{{ route('supervisor.purchase-orders.update', $purchaseOrder) }}">
            @csrf
            @method('PUT')

            @include('supervisor.purchase-orders._form', [
                'purchaseOrder' => $purchaseOrder,
                'quotation' => null,
                'submitText' => 'Update Purchase Order',
                'submitIcon' => 'save'
            ])
        </form>
    @endif
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
            title: 'Updating Purchase Order...',
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
                let errorMsg = 'An error occurred while updating the purchase order.';
                
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
});
</script>
@endpush
