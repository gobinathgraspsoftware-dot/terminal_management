@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-eye"></i> Stock Transfer Details</h2>
        <div>
            <a href="{{ route('supervisor.stock-transfers.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('supervisor.stock-transfers.print', $stockTransfer) }}" 
               class="btn btn-outline-secondary" target="_blank">
                <i class="bi bi-printer"></i> Print
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Transfer Header -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Transfer Information</h5>
                    @php
                    $statusBadges = [
                        'draft' => 'bg-secondary',
                        'pending_approval' => 'bg-warning text-dark',
                        'approved' => 'bg-info',
                        'in_transit' => 'bg-primary',
                        'received' => 'bg-success',
                        'cancelled' => 'bg-danger',
                    ];
                    @endphp
                    <span class="badge {{ $statusBadges[$stockTransfer->status] ?? 'bg-secondary' }}">
                        {{ ucfirst(str_replace('_', ' ', $stockTransfer->status)) }}
                    </span>
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
                                
                                @if($stockTransfer->approved_at)
                                <dt class="col-sm-5">Approved:</dt>
                                <dd class="col-sm-7">{{ $stockTransfer->approved_at->format('d M Y H:i') }}</dd>
                                @endif
                                
                                @if($stockTransfer->dispatched_at)
                                <dt class="col-sm-5">Dispatched:</dt>
                                <dd class="col-sm-7">{{ $stockTransfer->dispatched_at->format('d M Y H:i') }}</dd>
                                @endif
                                
                                @if($stockTransfer->received_at)
                                <dt class="col-sm-5">Received:</dt>
                                <dd class="col-sm-7">{{ $stockTransfer->received_at->format('d M Y H:i') }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                    
                    @if($stockTransfer->remarks)
                    <hr>
                    <div>
                        <strong>Remarks:</strong>
                        <p class="mb-0">{{ $stockTransfer->remarks }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Transfer Lines -->
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
                                    <th width="30%">Model</th>
                                    <th width="20%">Serial No</th>
                                    <th width="15%">Qty Requested</th>
                                    @if($stockTransfer->status !== 'draft' && $stockTransfer->status !== 'pending_approval')
                                    <th width="15%">Qty Dispatched</th>
                                    @endif
                                    @if($stockTransfer->status === 'received')
                                    <th width="15%">Qty Received</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockTransfer->lines as $line)
                                <tr>
                                    <td>{{ $line->line_no }}</td>
                                    <td>{{ $line->model->model_name }}</td>
                                    <td>{{ $line->serial_no ?? '-' }}</td>
                                    <td>{{ number_format($line->quantity_requested, 4) }}</td>
                                    @if($stockTransfer->status !== 'draft' && $stockTransfer->status !== 'pending_approval')
                                    <td>{{ number_format($line->quantity_dispatched, 4) }}</td>
                                    @endif
                                    @if($stockTransfer->status === 'received')
                                    <td>
                                        {{ number_format($line->quantity_received, 4) }}
                                        @php
                                        $variance = $line->quantity_received - $line->quantity_dispatched;
                                        @endphp
                                        @if($variance != 0)
                                        <span class="badge bg-warning text-dark ms-1">
                                            {{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 4) }}
                                        </span>
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Actions Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @if($stockTransfer->status === 'draft')
                        @can('edit_stock_transfers')
                        <a href="{{ route('supervisor.stock-transfers.edit', $stockTransfer) }}" class="btn btn-warning w-100 mb-2">
                            <i class="bi bi-pencil"></i> Edit Transfer
                        </a>
                        @endcan
                        
                        <button type="button" class="btn btn-primary w-100 mb-2" onclick="submitForApproval()">
                            <i class="bi bi-send"></i> Submit for Approval
                        </button>

                        @can('cancel_stock_transfers')
                        <button type="button" class="btn btn-danger w-100" onclick="cancelTransfer()">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        @endcan
                    @endif
                    
                    @if($stockTransfer->status === 'pending_approval')
                        @can('approve_stock_transfers')
                        <a href="{{ route('supervisor.stock-transfers.approve-form', $stockTransfer) }}" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-check-circle"></i> Approve
                        </a>
                        <button type="button" class="btn btn-danger w-100" onclick="rejectTransfer()">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                        @endcan
                    @endif

                    @if($stockTransfer->status === 'approved')
                        @can('dispatch_stock_transfers')
                        <button type="button" class="btn btn-primary w-100 mb-2" onclick="dispatchTransfer()">
                            <i class="bi bi-truck"></i> Dispatch Transfer
                        </button>
                        @endcan
                    @endif
                    
                    @if(in_array($stockTransfer->status, ['in_transit', 'approved']))
                        @can('receive_stock_transfers')
                        <a href="{{ route('supervisor.stock-transfers.receive-form', $stockTransfer) }}" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-box-arrow-in-down"></i> Receive Transfer
                        </a>
                        @endcan
                    @endif
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-secondary"></div>
                            <div class="timeline-content">
                                <h6>Created</h6>
                                <p class="small text-muted">{{ $stockTransfer->created_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                        
                        @if($stockTransfer->approved_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6>Approved</h6>
                                <p class="small text-muted">{{ $stockTransfer->approved_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                        @endif
                        
                        @if($stockTransfer->dispatched_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6>Dispatched</h6>
                                <p class="small text-muted">{{ $stockTransfer->dispatched_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                        @endif
                        
                        @if($stockTransfer->received_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6>Received</h6>
                                <p class="small text-muted">{{ $stockTransfer->received_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline:before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}
.timeline-item {
    position: relative;
    margin-bottom: 20px;
}
.timeline-marker {
    position: absolute;
    left: -26px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #fff;
}
.timeline-content h6 {
    margin-bottom: 5px;
    font-size: 14px;
}
</style>
@endpush

@push('scripts')
<script>
function submitForApproval() {
    if (confirm('Submit this transfer for approval?')) {
        $.post('{{ route("supervisor.stock-transfers.submit", $stockTransfer) }}', {
            _token: '{{ csrf_token() }}'
        }).done(function() {
            location.reload();
        });
    }
}

function cancelTransfer() {
    const reason = prompt('Enter cancellation reason:');
    if (reason) {
        $.post('{{ route("supervisor.stock-transfers.cancel", $stockTransfer) }}', {
            _token: '{{ csrf_token() }}',
            cancellation_reason: reason
        }).done(function() {
            location.reload();
        });
    }
}

function rejectTransfer() {
    const reason = prompt('Enter rejection reason:');
    if (reason) {
        $.post('{{ route("supervisor.stock-transfers.reject", $stockTransfer) }}', {
            _token: '{{ csrf_token() }}',
            rejection_reason: reason
        }).done(function() {
            location.reload();
        });
    }
}

function dispatchTransfer() {
    if (confirm('Dispatch this transfer?')) {
        // Get all lines and quantities
        const lines = {};
        @foreach($stockTransfer->lines as $line)
        lines[{{ $line->id }}] = { quantity_dispatched: {{ $line->quantity_requested }} };
        @endforeach

        $.post('{{ route("supervisor.stock-transfers.dispatch", $stockTransfer) }}', {
            _token: '{{ csrf_token() }}',
            lines: lines
        }).done(function() {
            location.reload();
        });
    }
}
</script>
@endpush
@endsection
