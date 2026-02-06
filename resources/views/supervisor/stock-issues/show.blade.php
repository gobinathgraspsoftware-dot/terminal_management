@extends('layouts.app')

@section('title', 'Stock Issue Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-file-text text-primary"></i> Stock Issue Details
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.stock-issues.index') }}">Stock Issues</a></li>
                    <li class="breadcrumb-item active">{{ $stockIssue->issue_no }}</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            @if($stockIssue->status === 'draft' && auth()->user()->can('update', $stockIssue))
                <a href="{{ route('supervisor.stock-issues.edit', $stockIssue->id) }}" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            @endif
            @if($stockIssue->status === 'draft' && auth()->user()->can('post', $stockIssue))
                <button type="button" class="btn btn-success" onclick="postIssue()">
                    <i class="bi bi-check-circle"></i> Post
                </button>
            @endif
            @if($stockIssue->status === 'posted' && auth()->user()->can('cancel', $stockIssue))
                <button type="button" class="btn btn-danger" onclick="cancelIssue()">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
            @endif
            <a href="{{ route('supervisor.stock-issues.print', $stockIssue->id) }}" target="_blank" class="btn btn-secondary">
                <i class="bi bi-printer"></i> Print
            </a>
            <a href="{{ route('supervisor.stock-issues.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Status Alert -->
    @if($stockIssue->status === 'cancelled')
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i> This stock issue has been cancelled.
    </div>
    @endif

    <!-- Header Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="bi bi-info-circle"></i> Issue Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th width="40%">Issue No:</th>
                            <td><strong>{{ $stockIssue->issue_no }}</strong></td>
                        </tr>
                        <tr>
                            <th>Issue Date:</th>
                            <td>{{ $stockIssue->issue_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th>Issue Type:</th>
                            <td>
                                @if($stockIssue->issue_type === 'issue_to_tech')
                                    <span class="badge bg-primary">Issue to Technician</span>
                                @else
                                    <span class="badge bg-success">Return from Technician</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                @if($stockIssue->status === 'draft')
                                    <span class="badge bg-secondary">Draft</span>
                                @elseif($stockIssue->status === 'posted')
                                    <span class="badge bg-success">Posted</span>
                                @else
                                    <span class="badge bg-danger">Cancelled</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Total Items:</th>
                            <td><span class="badge bg-info">{{ $stockIssue->total_items }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0"><i class="bi bi-arrow-left-right"></i> Movement Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        @if($stockIssue->issue_type === 'issue_to_tech')
                            <tr>
                                <th width="40%">From Depot:</th>
                                <td>
                                    <i class="bi bi-building text-primary"></i> 
                                    {{ $stockIssue->fromDepot->name ?? '-' }}
                                </td>
                            </tr>
                            <tr>
                                <th>To Technician:</th>
                                <td>
                                    <i class="bi bi-person text-success"></i> 
                                    {{ $stockIssue->toTechnician->name ?? '-' }}
                                </td>
                            </tr>
                        @else
                            <tr>
                                <th width="40%">From Technician:</th>
                                <td>
                                    <i class="bi bi-person text-primary"></i> 
                                    {{ $stockIssue->fromTechnician->name ?? '-' }}
                                </td>
                            </tr>
                            <tr>
                                <th>To Depot:</th>
                                <td>
                                    <i class="bi bi-building text-success"></i> 
                                    {{ $stockIssue->toDepot->name ?? '-' }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <th>Remarks:</th>
                            <td>{{ $stockIssue->remarks ?? '-' }}</td>
                        </tr>
                        @if($stockIssue->posted_at)
                        <tr>
                            <th>Posted At:</th>
                            <td>{{ $stockIssue->posted_at->format('d M Y H:i') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-list-ul"></i> Line Items</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="30%">Model</th>
                            <th width="25%">Serial Number</th>
                            <th width="15%">Quantity</th>
                            <th width="25%">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockIssue->lines as $line)
                        <tr>
                            <td class="text-center">{{ $line->line_no }}</td>
                            <td>
                                <strong>{{ $line->model->model_name ?? '-' }}</strong>
                                @if($line->model)
                                    <br><small class="text-muted">{{ $line->model->category->name ?? '' }}</small>
                                @endif
                            </td>
                            <td>
                                @if($line->serial_no)
                                    <code class="text-primary">{{ $line->serial_no }}</code>
                                    @if($line->serial)
                                        <br><small class="badge bg-{{ $line->serial->status === 'available' ? 'success' : 'warning' }}">
                                            {{ ucfirst($line->serial->status) }}
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">Non-Serialized</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ number_format($line->quantity, 4) }}</span>
                            </td>
                            <td>{{ $line->remarks ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No line items found</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($stockIssue->lines->count() > 0)
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Total Items:</th>
                            <th class="text-center">
                                <span class="badge bg-primary">{{ $stockIssue->total_items }}</span>
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <!-- Audit Information -->
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0"><i class="bi bi-clock-history"></i> Audit Trail</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">Created: {{ $stockIssue->created_at->format('d M Y H:i') }}</small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">Last Updated: {{ $stockIssue->updated_at->format('d M Y H:i') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function postIssue() {
    Swal.fire({
        title: 'Post Stock Issue?',
        text: 'This will create ledger entries and update stock balances. This cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Post it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("supervisor.stock-issues.post", $stockIssue->id) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('Posted!', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to post stock issue', 'error');
                }
            });
        }
    });
}

function cancelIssue() {
    Swal.fire({
        title: 'Cancel Stock Issue?',
        text: 'This will reverse all ledger entries. Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Cancel it!',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("supervisor.stock-issues.cancel", $stockIssue->id) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('Cancelled!', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to cancel stock issue', 'error');
                }
            });
        }
    });
}
</script>
@endpush
