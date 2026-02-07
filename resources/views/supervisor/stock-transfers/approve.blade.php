@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-check-circle"></i> Approve Stock Transfer</h2>
        <a href="{{ route('supervisor.stock-transfers.show', $stockTransfer) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Review the transfer details below before approving. The system will validate stock availability.
    </div>

    <!-- Transfer Details -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Transfer Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-5">Transfer No:</dt>
                        <dd class="col-sm-7"><strong>{{ $stockTransfer->transfer_no }}</strong></dd>
                        
                        <dt class="col-sm-5">Transfer Date:</dt>
                        <dd class="col-sm-7">{{ $stockTransfer->transfer_date->format('d M Y') }}</dd>
                        
                        <dt class="col-sm-5">From Depot:</dt>
                        <dd class="col-sm-7">{{ $stockTransfer->fromDepot->depot_name }}</dd>
                        
                        <dt class="col-sm-5">To Depot:</dt>
                        <dd class="col-sm-7">{{ $stockTransfer->toDepot->depot_name }}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-5">Total Items:</dt>
                        <dd class="col-sm-7">{{ number_format($stockTransfer->total_items) }}</dd>
                        
                        <dt class="col-sm-5">Created By:</dt>
                        <dd class="col-sm-7">{{ $stockTransfer->creator->name ?? 'N/A' }}</dd>
                        
                        <dt class="col-sm-5">Created:</dt>
                        <dd class="col-sm-7">{{ $stockTransfer->created_at->format('d M Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
            
            @if($stockTransfer->remarks)
            <hr>
            <div>
                <strong>Transfer Remarks:</strong>
                <p class="mb-0">{{ $stockTransfer->remarks }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Items Table -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Transfer Items</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="35%">Model</th>
                            <th width="25%">Serial No</th>
                            <th width="20%">Quantity Requested</th>
                            <th width="15%">Stock Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stockTransfer->lines as $line)
                        <tr>
                            <td>{{ $line->line_no }}</td>
                            <td>{{ $line->model->category->category_name }} - {{ $line->model->model_name }}</td>
                            <td>{{ $line->serial_no ?? '-' }}</td>
                            <td>{{ number_format($line->quantity_requested, 4) }}</td>
                            <td>
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Available
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Approval Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Approval Decision</h5>
        </div>
        <div class="card-body">
            <form id="approvalForm" method="POST" action="{{ route('supervisor.stock-transfers.approve', $stockTransfer) }}">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label">Approval Remarks (Optional)</label>
                    <textarea name="remarks" class="form-control" rows="3" 
                              placeholder="Any comments, instructions, or special handling notes..."></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-danger" onclick="rejectTransfer()">
                        <i class="bi bi-x-circle"></i> Reject Transfer
                    </button>
                    <div>
                        <a href="{{ route('supervisor.stock-transfers.show', $stockTransfer) }}" class="btn btn-secondary me-2">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Approve Transfer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function rejectTransfer() {
    Swal.fire({
        title: 'Reject Transfer?',
        html: '<textarea id="rejectionReason" class="swal2-input" placeholder="Enter rejection reason..." rows="3" style="width:100%;"></textarea>',
        showCancelButton: true,
        confirmButtonText: 'Reject',
        confirmButtonColor: '#dc3545',
        preConfirm: () => {
            const reason = document.getElementById('rejectionReason').value;
            if (!reason) {
                Swal.showValidationMessage('Rejection reason is required');
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('{{ route("supervisor.stock-transfers.reject", $stockTransfer) }}', {
                _token: '{{ csrf_token() }}',
                rejection_reason: result.value
            }).done(function() {
                Swal.fire('Rejected!', 'The transfer has been rejected.', 'success')
                .then(() => {
                    window.location.href = '{{ route("supervisor.stock-transfers.index") }}';
                });
            }).fail(function(xhr) {
                Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to reject transfer', 'error');
            });
        }
    });
}

$('#approvalForm').submit(function(e) {
    e.preventDefault();
    
    Swal.fire({
        title: 'Approve Transfer?',
        text: 'This will allow the transfer to proceed to dispatch.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        confirmButtonColor: '#198754'
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});
</script>
@endpush
@endsection
