@extends('layouts.app')

@section('title', 'GRN Details - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">GRN Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.grns.index') }}">GRNs</a></li>
                    <li class="breadcrumb-item active">{{ $grn->grn_no }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @if($grn->isDraft())
                @can('update', $grn)
                <a href="{{ route('admin.grns.edit', $grn) }}" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                @endcan
                @can('post', $grn)
                <button type="button" class="btn btn-success" id="btn-post">
                    <i class="bi bi-check-circle me-1"></i> Post GRN
                </button>
                @endcan
                @can('cancel', $grn)
                <button type="button" class="btn btn-danger" id="btn-cancel">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </button>
                @endcan
            @endif

            {{-- PDF Buttons - Always visible --}}
            @can('print', $grn)
            <div class="btn-group">
                <a href="{{ route('admin.grns.pdf', $grn) }}" class="btn btn-secondary" target="_blank" title="View PDF in Browser">
                    <i class="bi bi-printer me-1"></i> Print PDF
                </a>
                <a href="{{ route('admin.grns.download', $grn) }}" class="btn btn-outline-secondary" title="Download PDF">
                    <i class="bi bi-download me-1"></i> Download
                </a>
            </div>
            @endcan

            <a href="{{ route('admin.grns.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    {{-- Status Banner --}}
    @php
        $statusColors = ['draft' => 'secondary', 'posted' => 'success', 'cancelled' => 'danger'];
        $statusColor  = $statusColors[$grn->status] ?? 'secondary';
        $statusIcons  = ['draft' => 'pencil-square', 'posted' => 'check-circle', 'cancelled' => 'x-circle'];
        $statusIcon   = $statusIcons[$grn->status] ?? 'info-circle';
    @endphp
    <div class="alert alert-{{ $statusColor }} d-flex align-items-center mb-3">
        <i class="bi bi-{{ $statusIcon }} fs-4 me-2"></i>
        <div>
            <strong>Status: {{ ucfirst($grn->status) }}</strong>
            @if($grn->isPosted() && $grn->posted_at)
                — Posted on {{ $grn->posted_at->format('d M Y H:i') }} by {{ $grn->postedBy?->name ?? '-' }}
            @endif
        </div>
    </div>

    <div class="row">
        {{-- Left Column: GRN Info --}}
        <div class="col-lg-8">
            {{-- GRN Header Card --}}
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>GRN Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted small">GRN Number:</label>
                            <div class="fw-semibold">{{ $grn->grn_no }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted small">GRN Date:</label>
                            <div>{{ $grn->grn_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted small">Purchase Order:</label>
                            <div>{{ $grn->purchaseOrder?->po_no ?? '-' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted small">Vendor:</label>
                            <div>{{ $grn->vendor?->vendor_name ?? $grn->vendor?->company_name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted small">Receiving Depot:</label>
                            <div>{{ $grn->receivingDepot?->depot_name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted small">Delivery Note No:</label>
                            <div>{{ $grn->delivery_note_no ?? '-' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold text-muted small">Total Items:</label>
                            <div><span class="badge bg-info">{{ $grn->total_items }}</span></div>
                        </div>
                        @if($grn->remarks)
                        <div class="col-md-12 mb-3">
                            <label class="fw-bold text-muted small">Remarks:</label>
                            <div>{{ $grn->remarks }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Line Items</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="30%">Model / Description</th>
                                    <th width="10%" class="text-end">Qty Received</th>
                                    <th width="10%">Unit</th>
                                    <th width="15%" class="text-end">Unit Cost (MYR)</th>
                                    <th width="15%" class="text-end">Line Total (MYR)</th>
                                    <th width="10%" class="text-center">Serials</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($grn->lines as $line)
                                <tr>
                                    <td>{{ $line->line_no }}</td>
                                    <td>
                                        <strong>{{ $line->model?->model_name ?? '-' }}</strong>
                                        @if($line->description)
                                            <br><small class="text-muted">{{ $line->description }}</small>
                                        @endif
                                        @if($line->model?->category)
                                            <br><span class="badge bg-light text-dark">{{ $line->model->category->category_name ?? '' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($line->quantity_received) }}</td>
                                    <td>{{ $line->unit ?? 'pcs' }}</td>
                                    <td class="text-end">{{ number_format($line->unit_cost, 2) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($line->line_total, 2) }}</td>
                                    <td class="text-center">
                                        @if($line->serials->count() > 0)
                                            <button type="button" class="btn btn-sm btn-outline-info view-serials-btn"
                                                data-line-id="{{ $line->id }}"
                                                data-model-name="{{ $line->model?->model_name ?? '-' }}"
                                                data-serials='@json($line->serials->pluck("serial_no"))'>
                                                <i class="bi bi-upc-scan me-1"></i>{{ $line->serials->count() }}
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No line items</td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($grn->lines->count() > 0)
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="2" class="fw-bold">Total</td>
                                    <td class="text-end fw-bold">{{ number_format($grn->lines->sum('quantity_received')) }}</td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-end fw-bold">{{ number_format($grn->lines->sum('line_total'), 2) }}</td>
                                    <td class="text-center fw-bold">{{ $grn->lines->sum(fn($l) => $l->serials->count()) }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Audit Trail --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Audit Trail</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="fw-bold text-muted small">Created By:</label>
                        <div>{{ $grn->createdBy?->name ?? '-' }}</div>
                        <small class="text-muted">{{ $grn->created_at?->format('d M Y H:i') }}</small>
                    </div>
                    @if($grn->updated_at && $grn->updated_at != $grn->created_at)
                    <div class="mb-3">
                        <label class="fw-bold text-muted small">Last Updated:</label>
                        <div><small class="text-muted">{{ $grn->updated_at->format('d M Y H:i') }}</small></div>
                    </div>
                    @endif
                    @if($grn->posted_at)
                    <div class="mb-3">
                        <label class="fw-bold text-muted small">Posted By:</label>
                        <div>{{ $grn->postedBy?->name ?? '-' }}</div>
                        <small class="text-muted">{{ $grn->posted_at->format('d M Y H:i') }}</small>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions Card --}}
            @can('print', $grn)
            <div class="card mt-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.grns.pdf', $grn) }}" class="btn btn-outline-secondary" target="_blank">
                            <i class="bi bi-printer me-1"></i> View PDF in Browser
                        </a>
                        <a href="{{ route('admin.grns.download', $grn) }}" class="btn btn-outline-primary">
                            <i class="bi bi-download me-1"></i> Download PDF
                        </a>
                    </div>
                </div>
            </div>
            @endcan
        </div>
    </div>
</div>

{{-- Serial Numbers Modal --}}
<div class="modal fade" id="serialsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upc-scan me-2"></i>Serial Numbers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2"><strong>Model:</strong> <span id="serial-model-name"></span></p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Serial Number</th>
                            </tr>
                        </thead>
                        <tbody id="serial-list"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {

    // ── Post GRN ──────────────────────────────────────────────────────────────
    $('#btn-post').on('click', function() {
        Swal.fire({
            title: 'Post GRN?',
            text: 'This will update inventory and stock balances. This action cannot be undone.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Post GRN',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                let btn = $('#btn-post');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Posting...');

                $.ajax({
                    url: '{{ route("admin.grns.post", $grn) }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                            btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Post GRN');
                        }
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message || 'An error occurred while posting the GRN.';
                        Swal.fire('Error', msg, 'error');
                        btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Post GRN');
                    }
                });
            }
        });
    });

    // ── Cancel GRN ────────────────────────────────────────────────────────────
    $('#btn-cancel').on('click', function() {
        Swal.fire({
            title: 'Cancel GRN?',
            text: 'Are you sure you want to cancel this GRN?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Cancel GRN',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.grns.cancel", $grn) }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Cancelled!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to cancel GRN.', 'error');
                    }
                });
            }
        });
    });

    // ── View Serials Modal ────────────────────────────────────────────────────
    $(document).on('click', '.view-serials-btn', function() {
        let modelName = $(this).data('model-name');
        let serials   = $(this).data('serials');

        $('#serial-model-name').text(modelName);
        let tbody = $('#serial-list');
        tbody.empty();

        serials.forEach(function(serial, index) {
            tbody.append('<tr><td>' + (index + 1) + '</td><td><code>' + serial + '</code></td></tr>');
        });

        $('#serialsModal').modal('show');
    });

});
</script>
@endpush
