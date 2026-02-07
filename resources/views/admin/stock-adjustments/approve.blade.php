@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Approve Stock Adjustment</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.stock-adjustments.index') }}">Stock Adjustments</a></li>
                    <li class="breadcrumb-item active">Approve</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($stockAdjustment->created_by === auth()->id())
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Note:</strong> You cannot approve your own adjustment. Please ask another administrator to review this.
    </div>
    @endif

    <!-- Adjustment Header Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ $stockAdjustment->adjustment_no }}</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="text-muted small">Adjustment Date</label>
                    <div class="fw-bold">{{ $stockAdjustment->adjustment_date->format('d M Y') }}</div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Adjustment Type</label>
                    <div class="fw-bold">{{ ucwords(str_replace('_', ' ', $stockAdjustment->adjustment_type)) }}</div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small">Depot</label>
                    <div class="fw-bold">{{ $stockAdjustment->depot->depot_name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-12">
                    <label class="text-muted small">Reason</label>
                    <div>{{ $stockAdjustment->reason }}</div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small">Created By</label>
                    <div class="fw-bold">{{ $stockAdjustment->creator->name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small">Created At</label>
                    <div>{{ $stockAdjustment->created_at->format('d M Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Adjustment Lines Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Adjustment Lines ({{ $stockAdjustment->total_items }} items)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Terminal Model</th>
                            <th>Serial No</th>
                            <th class="text-end">System Qty</th>
                            <th class="text-end">Physical Qty</th>
                            <th class="text-end">Variance</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Var. Value</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stockAdjustment->lines as $line)
                        <tr>
                            <td>{{ $line->line_no }}</td>
                            <td>{{ $line->model->model_name ?? 'N/A' }}</td>
                            <td>{{ $line->serial_no ?? '-' }}</td>
                            <td class="text-end">{{ number_format($line->system_quantity, 2) }}</td>
                            <td class="text-end">{{ number_format($line->physical_quantity, 2) }}</td>
                            <td class="text-end {{ $line->variance_quantity > 0 ? 'text-success fw-bold' : ($line->variance_quantity < 0 ? 'text-danger fw-bold' : '') }}">
                                {{ number_format($line->variance_quantity, 2) }}
                            </td>
                            <td class="text-end">{{ $line->unit_cost ? number_format($line->unit_cost, 2) : '-' }}</td>
                            <td class="text-end {{ $line->variance_value > 0 ? 'text-success fw-bold' : ($line->variance_value < 0 ? 'text-danger fw-bold' : '') }}">
                                {{ $line->variance_value ? number_format($line->variance_value, 2) : '-' }}
                            </td>
                            <td>{{ $line->remarks ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="5" class="text-end">Totals:</th>
                            <th class="text-end {{ $stockAdjustment->lines->sum('variance_quantity') > 0 ? 'text-success' : ($stockAdjustment->lines->sum('variance_quantity') < 0 ? 'text-danger' : '') }}">
                                {{ number_format($stockAdjustment->lines->sum('variance_quantity'), 2) }}
                            </th>
                            <th></th>
                            <th class="text-end {{ $stockAdjustment->lines->sum('variance_value') > 0 ? 'text-success' : ($stockAdjustment->lines->sum('variance_value') < 0 ? 'text-danger' : '') }}">
                                {{ number_format($stockAdjustment->lines->sum('variance_value'), 2) }}
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Approval Form Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Approval Decision</h5>
        </div>
        <div class="card-body">
            <form id="approvalForm" method="POST" action="{{ route('admin.stock-adjustments.process-approval', $stockAdjustment) }}">
                @csrf
                
                <div class="mb-3" id="remarksSection" style="display: none;">
                    <label for="remarks" class="form-label">Rejection Remarks <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Enter reason for rejection..."></textarea>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="d-flex justify-content-between gap-2">
                    <a href="{{ route('admin.stock-adjustments.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to List
                    </a>
                    
                    <div class="btn-group">
                        @if($stockAdjustment->created_by !== auth()->id())
                        <button type="button" class="btn btn-danger" id="rejectBtn">
                            <i class="bi bi-x-circle me-1"></i>Reject
                        </button>
                        <button type="button" class="btn btn-success" id="approveBtn">
                            <i class="bi bi-check-circle me-1"></i>Approve
                        </button>
                        @else
                        <button type="button" class="btn btn-secondary" disabled>
                            Cannot approve own adjustment
                        </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let actionType = '';

    // Reject button
    $('#rejectBtn').click(function() {
        actionType = 'reject';
        $('#remarksSection').slideDown();
        $('#remarks').prop('required', true);
        
        Swal.fire({
            title: 'Reject Adjustment?',
            text: 'Please provide a reason for rejection.',
            icon: 'warning',
            input: 'textarea',
            inputPlaceholder: 'Enter rejection reason...',
            inputAttributes: {
                'aria-label': 'Rejection reason'
            },
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Reject',
            preConfirm: (remarks) => {
                if (!remarks) {
                    Swal.showValidationMessage('Rejection remarks are required');
                }
                return remarks;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                submitApproval('reject', result.value);
            }
        });
    });

    // Approve button
    $('#approveBtn').click(function() {
        Swal.fire({
            title: 'Approve Adjustment?',
            text: 'This adjustment will be approved and can then be posted to update stock.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                submitApproval('approve', null);
            }
        });
    });

    // Submit approval
    function submitApproval(action, remarks) {
        let formData = {
            _token: '{{ csrf_token() }}',
            action: action
        };

        if (remarks) {
            formData.remarks = remarks;
        }

        $.ajax({
            url: "{{ route('admin.stock-adjustments.process-approval', $stockAdjustment) }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    let icon = action === 'approve' ? 'success' : 'info';
                    Swal.fire('Success!', response.message, icon).then(() => {
                        window.location.href = response.redirect || "{{ route('admin.stock-adjustments.index') }}";
                    });
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = Object.values(errors).flat().join('<br>');
                    Swal.fire('Validation Error', errorMessage, 'error');
                } else {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                }
            }
        });
    }
});
</script>
@endpush
