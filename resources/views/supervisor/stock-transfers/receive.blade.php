@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-box-arrow-in-down"></i> Receive Stock Transfer</h2>
        <a href="{{ route('supervisor.stock-transfers.show', $stockTransfer) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Enter actual quantities received. Any variance from dispatched quantity will be highlighted and requires explanation.
    </div>

    <!-- Transfer Summary -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Transfer Summary</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Transfer No:</strong>
                    <p>{{ $stockTransfer->transfer_no }}</p>
                </div>
                <div class="col-md-3">
                    <strong>From Depot:</strong>
                    <p>{{ $stockTransfer->fromDepot->depot_name }}</p>
                </div>
                <div class="col-md-3">
                    <strong>To Depot:</strong>
                    <p>{{ $stockTransfer->toDepot->depot_name }}</p>
                </div>
                <div class="col-md-3">
                    <strong>Dispatched:</strong>
                    <p>{{ $stockTransfer->dispatched_at ? $stockTransfer->dispatched_at->format('d M Y H:i') : 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Receive Form -->
    <form id="receiveForm" method="POST" action="{{ route('supervisor.stock-transfers.receive', $stockTransfer) }}">
        @csrf
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Receive Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="receiveTable">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="30%">Model</th>
                                <th width="20%">Serial No</th>
                                <th width="12%">Dispatched</th>
                                <th width="12%">Received</th>
                                <th width="10%">Variance</th>
                                <th width="21%">Variance Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockTransfer->lines as $line)
                            <tr data-line-id="{{ $line->id }}">
                                <td>{{ $line->line_no }}</td>
                                <td>{{ $line->model->model_name }}</td>
                                <td>{{ $line->serial_no ?? '-' }}</td>
                                <td>
                                    <input type="number" class="form-control form-control-sm qty-dispatched" 
                                           value="{{ $line->quantity_dispatched ?: $line->quantity_requested }}" 
                                           readonly>
                                </td>
                                <td>
                                    <input type="number" name="lines[{{ $line->id }}][quantity_received]" 
                                           class="form-control form-control-sm qty-received" 
                                           value="{{ $line->quantity_dispatched ?: $line->quantity_requested }}" 
                                           min="0" step="0.0001" required>
                                </td>
                                <td class="text-center">
                                    <span class="badge variance-badge bg-success">0.0000</span>
                                </td>
                                <td>
                                    <input type="text" name="lines[{{ $line->id }}][variance_reason]" 
                                           class="form-control form-control-sm variance-reason" 
                                           placeholder="Required if variance exists" disabled>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="3" class="text-end">Total:</th>
                                <th id="totalDispatched">0</th>
                                <th id="totalReceived">0</th>
                                <th id="totalVariance" class="text-center">-</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3">
                    <label class="form-label">Receive Remarks</label>
                    <textarea name="receive_remarks" class="form-control" rows="3" 
                              placeholder="Any additional notes about the receipt, condition of items, etc..."></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('supervisor.stock-transfers.show', $stockTransfer) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Confirm Receipt
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Calculate variance on quantity change
    $('.qty-received').on('input', function() {
        calculateVariance($(this));
        calculateTotals();
    });
    
    // Initial calculations
    $('.qty-received').trigger('input');
});

function calculateVariance($input) {
    const $row = $input.closest('tr');
    const dispatched = parseFloat($row.find('.qty-dispatched').val()) || 0;
    const received = parseFloat($input.val()) || 0;
    const variance = received - dispatched;
    
    const $varianceBadge = $row.find('.variance-badge');
    const $varianceReason = $row.find('.variance-reason');
    
    // Update variance display
    const varianceText = (variance >= 0 ? '+' : '') + variance.toFixed(4);
    $varianceBadge.text(varianceText);
    
    // Handle variance highlighting and reason requirement
    if (Math.abs(variance) > 0.0001) {
        $row.addClass('table-warning');
        $varianceBadge.removeClass('bg-success').addClass('bg-warning text-dark');
        $varianceReason.prop('disabled', false).prop('required', true);
    } else {
        $row.removeClass('table-warning');
        $varianceBadge.removeClass('bg-warning text-dark').addClass('bg-success');
        $varianceReason.prop('disabled', true).prop('required', false).val('');
    }
}

function calculateTotals() {
    let totalDispatched = 0;
    let totalReceived = 0;
    
    $('.qty-dispatched').each(function() {
        totalDispatched += parseFloat($(this).val()) || 0;
    });
    
    $('.qty-received').each(function() {
        totalReceived += parseFloat($(this).val()) || 0;
    });
    
    const totalVariance = totalReceived - totalDispatched;
    
    $('#totalDispatched').text(totalDispatched.toFixed(4));
    $('#totalReceived').text(totalReceived.toFixed(4));
    $('#totalVariance').html(
        `<span class="badge ${Math.abs(totalVariance) > 0.0001 ? 'bg-warning text-dark' : 'bg-success'}">
            ${(totalVariance >= 0 ? '+' : '') + totalVariance.toFixed(4)}
        </span>`
    );
}

// Form validation
$('#receiveForm').on('submit', function(e) {
    let hasInvalidVariance = false;
    let missingReasons = [];
    
    $('.variance-reason:required').each(function() {
        if (!$(this).val().trim()) {
            hasInvalidVariance = true;
            const lineNo = $(this).closest('tr').find('td:first').text();
            missingReasons.push('Line ' + lineNo);
        }
    });
    
    if (hasInvalidVariance) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Missing Variance Reasons',
            html: 'Please provide reasons for quantity variances in:<br><strong>' + 
                  missingReasons.join(', ') + '</strong>',
            confirmButtonText: 'OK'
        });
        return false;
    }
    
    // Confirm submission
    e.preventDefault();
    Swal.fire({
        title: 'Confirm Receipt?',
        html: 'This will update stock levels at the destination depot.<br>This action cannot be undone.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Confirm Receipt',
        confirmButtonColor: '#198754'
    }).then((result) => {
        if (result.isConfirmed) {
            e.target.submit();
        }
    });
});
</script>
@endpush
@endsection
