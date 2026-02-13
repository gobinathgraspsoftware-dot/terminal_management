@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">GRN Details - {{ $grn->grn_no }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.grns.index') }}">GRNs</a></li>
                    <li class="breadcrumb-item active">{{ $grn->grn_no }}</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('update', $grn)
                @if($grn->status === 'draft')
                <a href="{{ route('admin.grns.edit', $grn) }}" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                @endif
            @endcan
            @can('post', $grn)
                @if($grn->status === 'draft')
                <button type="button" class="btn btn-success" id="postBtn">
                    <i class="bi bi-check-circle me-1"></i> Post GRN
                </button>
                @endif
            @endcan
            <a href="{{ route('admin.grns.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>GRN Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">GRN Number:</label>
                            <div>{{ $grn->grn_no }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">GRN Date:</label>
                            <div>{{ $grn->grn_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Purchase Order:</label>
                            <div>{{ $grn->purchaseOrder->po_no ?? '-' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Vendor:</label>
                            <div>{{ $grn->vendor->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Receiving Depot:</label>
                            <div>{{ $grn->receivingDepot->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Status:</label>
                            <div>
                                @php
                                    $badges = ['draft' => 'secondary', 'posted' => 'success', 'cancelled' => 'danger'];
                                    $badge = $badges[$grn->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $badge }}">{{ ucfirst($grn->status) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Received Items</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Unit Cost</th>
                                    <th>Total</th>
                                    <th>Serials</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($grn->lines as $line)
                                <tr>
                                    <td>{{ $line->line_no }}</td>
                                    <td><strong>{{ $line->model->name ?? '' }}</strong></td>
                                    <td class="text-end">{{ $line->quantity_received }}</td>
                                    <td>{{ $line->unit }}</td>
                                    <td class="text-end">{{ number_format($line->unit_cost, 2) }}</td>
                                    <td class="text-end">{{ number_format($line->line_total, 2) }}</td>
                                    <td class="text-center">
                                        @if($line->serials->count() > 0)
                                            <span class="badge bg-info">{{ $line->serials->count() }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No items</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Audit Trail</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="fw-bold">Created By:</label>
                        <div>{{ $grn->createdBy->name ?? '-' }}</div>
                        <small class="text-muted">{{ $grn->created_at->format('d M Y H:i') }}</small>
                    </div>
                    @if($grn->posted_at)
                    <div>
                        <label class="fw-bold">Posted By:</label>
                        <div>{{ $grn->postedBy->name ?? '-' }}</div>
                        <small class="text-muted">{{ $grn->posted_at->format('d M Y H:i') }}</small>
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
$(document).ready(function() {
    $('#postBtn').on('click', function() {
        Swal.fire({
            title: 'Post GRN?',
            text: 'This will update inventory and cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, Post It'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.grns.post", $grn) }}',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Posted!', response.message, 'success').then(() => location.reload());
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to post GRN', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
@endsection
