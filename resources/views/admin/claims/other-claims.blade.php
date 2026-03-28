@extends('layouts.app')
@section('title', 'Other Claims')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1><i class="bi bi-file-earmark-text me-2 text-secondary"></i>Other Claims</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.claims.index') }}">Claims</a></li>
                <li class="breadcrumb-item active">Other Claims</li>
            </ol>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.claims.export', ['category' => 'other']) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download me-1"></i> Export
            </a>
            <a href="{{ route('admin.claims.create-other-claim') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> New Claim
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-table me-2"></i>Other Claims List</span>
        <select id="statusFilter" class="form-select form-select-sm" style="width:160px">
            <option value="">All Statuses</option>
            <option value="submitted">Submitted</option>
            <option value="verified">Verified</option>
            <option value="non_claimable">Non-Claimable</option>
            <option value="pending_payment">Pending Payment</option>
            <option value="paid">Paid</option>
        </select>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="otherClaimsTable" class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Claim No</th>
                        <th>Submitted By</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Ticket</th>
                        <th>Amount (RM)</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th class="text-center">Att.</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var table = $('#otherClaimsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.claims.other-claims-data") }}',
            data: function (d) { d.status = $('#statusFilter').val(); }
        },
        columns: [
            { data: 'claim_no' },
            { data: 'submitted_by', orderable: false },
            { data: 'claim_type', orderable: false },
            { data: 'description', orderable: false },
            { data: 'ticket_no', orderable: false },
            { data: 'total_amount', className: 'text-end' },
            { data: 'status', orderable: false },
            { data: 'submitted_at' },
            { data: 'has_attachments', orderable: false, searchable: false, className: 'text-center' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' }
    });
    $('#statusFilter').on('change', function () { table.ajax.reload(); });
});
</script>
@endpush
