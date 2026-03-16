@extends('layouts.app')

@section('title', 'Other Claims')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Other Claims</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.claims.index') }}">Claim Management</a></li>
                    <li class="breadcrumb-item active">Other Claims</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @can('create_claims')
            <a href="{{ route('admin.claims.create-other-claim') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Create Other Claim
            </a>
            @endcan
            @can('export_claims')
            <a href="{{ route('admin.claims.export', ['category' => 'other']) }}" class="btn btn-outline-success">
                <i class="bi bi-download me-1"></i> Export
            </a>
            @endcan
            @can('bulk_pay_claims')
            <a href="{{ route('admin.claims.bulk-payment', ['category' => 'other']) }}" class="btn btn-success">
                <i class="bi bi-cash-stack me-1"></i> Bulk Payment
            </a>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1 small">Status</label>
                    <select id="filter-status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="submitted">Submitted</option>
                        <option value="verified">Verified</option>
                        <option value="non_claimable">Non-Claimable</option>
                        <option value="pending_payment">Pending Payment</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="btn-reset-filters" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="other-claims-table" class="table table-hover table-sm align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Claim No</th>
                            <th>Submitted By</th>
                            <th>Claim Type</th>
                            <th>Description</th>
                            <th>Amount (RM)</th>
                            <th>Status</th>
                            <th>Ticket No</th>
                            <th>Submitted</th>
                            <th><i class="bi bi-paperclip"></i></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var table = $('#other-claims-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.claims.other-claims-data") }}',
            data: function(d) {
                d.status = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'claim_no' },
            { data: 'submitted_by' },
            { data: 'claim_type' },
            { data: 'description' },
            { data: 'total_amount', className: 'text-end' },
            { data: 'status', className: 'text-center' },
            { data: 'ticket_no' },
            { data: 'submitted_at' },
            { data: 'has_attachments', className: 'text-center', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[7, 'desc']],
        pageLength: 10,
        language: { emptyTable: 'No other claims found.' }
    });

    $('#filter-status').on('change', function() { table.ajax.reload(); });
    $('#btn-reset-filters').on('click', function() {
        $('#filter-status').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
