@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Stock Adjustment Details</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.stock-adjustments.index') }}">Stock Adjustments</a></li>
                    <li class="breadcrumb-item active">{{ $stockAdjustment->adjustment_no }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.stock-adjustments.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to List
            </a>
        </div>
    </div>

    <!-- Adjustment Header Card -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ $stockAdjustment->adjustment_no }}</h5>
            <div>
                @php
                    $statusColors = [
                        'draft' => 'secondary',
                        'pending_approval' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'dark',
                    ];
                    $statusColor = $statusColors[$stockAdjustment->status] ?? 'secondary';
                @endphp
                <span class="badge bg-{{ $statusColor }} fs-6">
                    {{ ucwords(str_replace('_', ' ', $stockAdjustment->status)) }}
                </span>
            </div>
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
                <div class="col-12">
                    <label class="text-muted small">Reason</label>
                    <div>{{ $stockAdjustment->reason }}</div>
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
                            <td class="text-end {{ $line->variance_quantity > 0 ? 'text-success' : ($line->variance_quantity < 0 ? 'text-danger' : '') }}">
                                {{ number_format($line->variance_quantity, 2) }}
                            </td>
                            <td class="text-end">{{ $line->unit_cost ? number_format($line->unit_cost, 2) : '-' }}</td>
                            <td class="text-end {{ $line->variance_value > 0 ? 'text-success' : ($line->variance_value < 0 ? 'text-danger' : '') }}">
                                {{ $line->variance_value ? number_format($line->variance_value, 2) : '-' }}
                            </td>
                            <td>{{ $line->remarks ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="5" class="text-end">Totals:</th>
                            <th class="text-end">
                                {{ number_format($stockAdjustment->lines->sum('variance_quantity'), 2) }}
                            </th>
                            <th></th>
                            <th class="text-end">
                                {{ number_format($stockAdjustment->lines->sum('variance_value'), 2) }}
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Audit Information Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Audit Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="text-muted small">Created By</label>
                    <div class="fw-bold">{{ $stockAdjustment->creator->name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Created At</label>
                    <div>{{ $stockAdjustment->created_at->format('d M Y H:i') }}</div>
                </div>
                @if($stockAdjustment->approved_by)
                <div class="col-md-3">
                    <label class="text-muted small">Approved By</label>
                    <div class="fw-bold">{{ $stockAdjustment->approver->name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Approved At</label>
                    <div>{{ $stockAdjustment->approved_at?->format('d M Y H:i') ?? 'N/A' }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Action Buttons Card -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.stock-adjustments.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to List
                    </a>
                </div>
                <div class="btn-group" role="group">
                    @if($stockAdjustment->status === 'draft' && auth()->user()->can('update', $stockAdjustment))
                        <a href="{{ route('admin.stock-adjustments.edit', $stockAdjustment) }}" class="btn btn-warning">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                        <button type="button" class="btn btn-primary" id="submitForApprovalBtn">
                            <i class="bi bi-send me-1"></i>Submit for Approval
                        </button>
                    @endif

                    @if($stockAdjustment->status === 'pending_approval' && auth()->user()->can('approve', $stockAdjustment))
                        <a href="{{ route('admin.stock-adjustments.approve', $stockAdjustment) }}" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Approve / Reject
                        </a>
                    @endif

                    @if($stockAdjustment->status === 'approved' && auth()->user()->can('post', $stockAdjustment))
                        <button type="button" class="btn btn-info" id="postAdjustmentBtn">
                            <i class="bi bi-save me-1"></i>Post to Ledger
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Submit for approval
    $('#submitForApprovalBtn').click(function() {
        Swal.fire({
            title: 'Submit for Approval?',
            text: 'This adjustment will be sent for approval.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, submit it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('admin.stock-adjustments.submit', $stockAdjustment) }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Success!', response.message, 'success').then(() => {
                                window.location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });

    // Post adjustment
    $('#postAdjustmentBtn').click(function() {
        Swal.fire({
            title: 'Post Adjustment?',
            text: 'This will update stock ledger and balances. This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, post it!',
            confirmButtonColor: '#17a2b8'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('admin.stock-adjustments.post', $stockAdjustment) }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Posted!', response.message, 'success').then(() => {
                                window.location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
