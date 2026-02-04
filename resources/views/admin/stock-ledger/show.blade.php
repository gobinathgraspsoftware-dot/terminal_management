@extends('layouts.app')

@section('title', 'Stock Ledger Entry #' . $stockLedger->id)

@section('content')
<div class="container-fluid">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="{{ route('admin.stock-ledger.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Ledger
        </a>
    </div>

    <!-- Movement Details Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="bi bi-journal-text me-2"></i>Movement Details - {{ $stockLedger->transaction_no }}
            </h4>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">Transaction Information</h5>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th width="40%">Transaction Date</th>
                                <td>{{ $stockLedger->transaction_date->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <th>Transaction No</th>
                                <td><strong>{{ $stockLedger->transaction_no }}</strong></td>
                            </tr>
                            <tr>
                                <th>Transaction Type</th>
                                <td>{!! $stockLedger->type_badge !!}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if($stockLedger->is_reversed)
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Reversed</span>
                                    @elseif($stockLedger->reversal_of_id)
                                        <span class="badge bg-warning text-dark"><i class="bi bi-arrow-counterclockwise me-1"></i>Reversal Entry</span>
                                    @else
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td>{{ $stockLedger->created_at->format('d M Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>Created By</th>
                                <td>{{ $stockLedger->createdBy->name ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">Item & Movement Details</h5>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th width="40%">Model</th>
                                <td>
                                    <strong>{{ $stockLedger->model->model_name ?? '-' }}</strong><br>
                                    <small class="text-muted">{{ $stockLedger->model->model_code ?? '' }}</small>
                                </td>
                            </tr>
                            <tr>
                                <th>Serial Number</th>
                                <td>
                                    @if($stockLedger->serial_no)
                                        <span class="badge bg-info">{{ $stockLedger->serial_no }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Quantity</th>
                                <td><h5 class="mb-0">{{ number_format($stockLedger->quantity, 2) }}</h5></td>
                            </tr>
                            <tr>
                                <th>From Location</th>
                                <td>
                                    @if($stockLedger->from_location_name != '-')
                                        <i class="bi bi-geo-alt text-danger me-1"></i>{{ $stockLedger->from_location_name }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>To Location</th>
                                <td>
                                    @if($stockLedger->to_location_name != '-')
                                        <i class="bi bi-geo-alt text-success me-1"></i>{{ $stockLedger->to_location_name }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Financial & Reference Information -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="border-bottom pb-2 mb-3">Financial & Reference Information</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted">Unit Cost</h6>
                                    <h4>{{ $stockLedger->unit_cost ? 'RM ' . number_format($stockLedger->unit_cost, 2) : 'N/A' }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted">Total Cost</h6>
                                    <h4>{{ $stockLedger->total_cost ? 'RM ' . number_format($stockLedger->total_cost, 2) : 'N/A' }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted">Reference</h6>
                                    <h5>
                                        @if($stockLedger->reference_url)
                                            <a href="{{ $stockLedger->reference_url }}" target="_blank">
                                                {{ $stockLedger->reference_label }}
                                                <i class="bi bi-box-arrow-up-right ms-1"></i>
                                            </a>
                                        @else
                                            {{ $stockLedger->reference_label }}
                                        @endif
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            @if($stockLedger->remarks)
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="border-bottom pb-2 mb-3">Remarks</h5>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>{{ $stockLedger->remarks }}
                    </div>
                </div>
            </div>
            @endif

            <!-- Reversal Information -->
            @if($stockLedger->is_reversed || $stockLedger->reversal_of_id)
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="border-bottom pb-2 mb-3">Reversal Information</h5>
                    
                    @if($stockLedger->is_reversed)
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-x-circle me-2"></i>This movement has been reversed</h6>
                        <table class="table table-sm table-borderless mb-0 mt-2">
                            <tr>
                                <th width="200px">Reversed At:</th>
                                <td>{{ $stockLedger->reversed_at?->format('d M Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>Reversed By:</th>
                                <td>{{ $stockLedger->reversedByUser->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Reversal Entry:</th>
                                <td>
                                    @if($stockLedger->reversedByEntry)
                                        <a href="{{ route('admin.stock-ledger.show', $stockLedger->reversedByEntry->id) }}">
                                            {{ $stockLedger->reversedByEntry->transaction_no }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    @endif

                    @if($stockLedger->reversal_of_id)
                    <div class="alert alert-warning">
                        <h6><i class="bi bi-arrow-counterclockwise me-2"></i>This is a reversal entry</h6>
                        <table class="table table-sm table-borderless mb-0 mt-2">
                            <tr>
                                <th width="200px">Original Movement:</th>
                                <td>
                                    @if($stockLedger->reversalOfEntry)
                                        <a href="{{ route('admin.stock-ledger.show', $stockLedger->reversalOfEntry->id) }}">
                                            {{ $stockLedger->reversalOfEntry->transaction_no }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-md-12">
                    @can('reverse', $stockLedger)
                        @if($stockLedger->is_reversible && !$stockLedger->is_reversed)
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#reverseModal">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reverse This Movement
                        </button>
                        @endif
                    @endcan

                    <a href="{{ route('admin.stock-ledger.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reverse Movement Modal -->
<div class="modal fade" id="reverseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>Reverse Movement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reverseForm" action="{{ route('admin.stock-ledger.reverse', $stockLedger->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> This will create a counter-entry to reverse this stock movement. This action cannot be undone.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Reversal</label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="Optional: Enter reason for reversing this movement"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Confirm Reversal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#reverseForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const url = form.attr('action');
        const data = form.serialize();
        
        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Failed to reverse movement';
                toastr.error(error);
            }
        });
    });
});
</script>
@endpush
