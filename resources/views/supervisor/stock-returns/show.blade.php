@extends('layouts.app')

@section('title', 'Stock Return Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3 mb-0">Stock Return - {{ $stockReturn->issue_no }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.stock-returns.index') }}">Stock Returns</a></li>
                    <li class="breadcrumb-item active">{{ $stockReturn->issue_no }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            @if($stockReturn->status === 'draft' && auth()->user()->can('edit_stock_returns'))
            <a href="{{ route('supervisor.stock-returns.edit', $stockReturn->id) }}" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            @endif
            
            @if($stockReturn->status === 'draft' && auth()->user()->can('post_stock_returns'))
            <button type="button" class="btn btn-success" onclick="postReturn()">
                <i class="bi bi-check-circle me-1"></i> Post
            </button>
            @endif
            
            @if($stockReturn->status === 'posted' && auth()->user()->can('cancel_stock_returns'))
            <button type="button" class="btn btn-danger" onclick="cancelReturn()">
                <i class="bi bi-x-circle me-1"></i> Cancel
            </button>
            @endif
            
            <a href="{{ route('supervisor.stock-returns.print', $stockReturn->id) }}" target="_blank" class="btn btn-secondary">
                <i class="bi bi-printer me-1"></i> Print
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Header Info -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Return Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Return No</label>
                            <div class="fw-bold">{{ $stockReturn->issue_no }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Return Date</label>
                            <div>{{ $stockReturn->issue_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">From Technician</label>
                            <div><i class="bi bi-person me-1"></i>{{ $stockReturn->fromTechnician->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">To Depot</label>
                            <div><i class="bi bi-building me-1"></i>{{ $stockReturn->toDepot->depot_name }}</div>
                        </div>
                        @if($stockReturn->remarks)
                        <div class="col-12">
                            <label class="text-muted small">Remarks</label>
                            <div>{{ $stockReturn->remarks }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Return Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Model</th>
                                    <th>Serial No</th>
                                    <th>Quantity</th>
                                    <th>Condition</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockReturn->lines as $line)
                                <tr class="{{ in_array($line->condition, ['damaged', 'defective']) ? 'table-warning' : '' }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $line->model->model_name }}</td>
                                    <td>{{ $line->serial_no ?? '-' }}</td>
                                    <td>{{ $line->quantity }}</td>
                                    <td>{!! $line->condition_badge !!}</td>
                                    <td>{{ $line->remarks ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th>{{ $stockReturn->total_items }}</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Status Card -->
            <div class="card mb-3">
                <div class="card-header bg-{{ $stockReturn->status === 'posted' ? 'success' : ($stockReturn->status === 'cancelled' ? 'danger' : 'secondary') }} text-white">
                    <h5 class="card-title mb-0">Status</h5>
                </div>
                <div class="card-body">
                    <h3 class="mb-0">{{ ucfirst($stockReturn->status) }}</h3>
                    @if($stockReturn->posted_at)
                    <small class="text-muted">Posted: {{ $stockReturn->posted_at->format('d M Y H:i') }}</small>
                    @endif
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Audit Trail</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Created By</label>
                        <div>{{ $stockReturn->creator->name ?? 'System' }}</div>
                        <small class="text-muted">{{ $stockReturn->created_at->format('d M Y H:i') }}</small>
                    </div>
                    @if($stockReturn->posted_by)
                    <div class="mb-3">
                        <label class="text-muted small">Posted By</label>
                        <div>{{ $stockReturn->poster->name ?? 'System' }}</div>
                        <small class="text-muted">{{ $stockReturn->posted_at->format('d M Y H:i') }}</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function postReturn() {
    Swal.fire({
        title: 'Post Stock Return?',
        text: 'This will create ledger entries and update stock balances.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: 'Yes, Post It'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ route('supervisor.stock-returns.post', $stockReturn->id) }}",
                method: 'POST',
                data: {_token: '{{ csrf_token() }}'},
                success: function(response) {
                    Swal.fire('Posted!', response.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON.message, 'error');
                }
            });
        }
    });
}

function cancelReturn() {
    Swal.fire({
        title: 'Cancel Stock Return?',
        text: 'This will reverse all ledger entries.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Cancel It'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ route('supervisor.stock-returns.cancel', $stockReturn->id) }}",
                method: 'POST',
                data: {_token: '{{ csrf_token() }}'},
                success: function(response) {
                    Swal.fire('Cancelled!', response.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON.message, 'error');
                }
            });
        }
    });
}
</script>
@endpush
