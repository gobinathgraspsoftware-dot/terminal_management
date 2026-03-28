@extends('layouts.app')
@section('title', 'Ticket Claims')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1><i class="bi bi-ticket-detailed me-2 text-info"></i>Ticket Claims</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.claims.index') }}">Claims</a></li>
                <li class="breadcrumb-item active">Ticket Claims</li>
            </ol>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.claims.export', ['category' => 'ticket']) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download me-1"></i> Export
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-table me-2"></i>Ticket Claims List</span>
        <div class="d-flex gap-2 align-items-center">
            <select id="statusFilter" class="form-select form-select-sm" style="width:160px">
                <option value="">All Statuses</option>
                <option value="submitted">Submitted</option>
                <option value="verified">Verified</option>
                <option value="non_claimable">Non-Claimable</option>
                <option value="pending_payment">Pending Payment</option>
                <option value="paid">Paid</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="ticketClaimsTable" class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Claim No</th>
                        <th>Ticket No</th>
                        <th>Vendor</th>
                        <th>Merchant</th>
                        <th>Supervisor</th>
                        <th>Technician</th>
                        <th>Amount (RM)</th>
                        <th>Status</th>
                        <th>Submitted</th>
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
    var table = $('#ticketClaimsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.claims.ticket-claims-data") }}',
            data: function (d) {
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { data: 'claim_no', name: 'claim_no' },
            { data: 'ticket_no', name: 'ticket_id', searchable: false },
            { data: 'vendor', name: 'vendor', searchable: false, orderable: false },
            { data: 'merchant_name', name: 'merchant_name', searchable: false, orderable: false },
            { data: 'supervisor', name: 'supervisor', searchable: false, orderable: false },
            { data: 'technician', name: 'technician_id', searchable: false },
            { data: 'total_amount', name: 'total_amount', className: 'text-end' },
            { data: 'status', name: 'status', searchable: false, orderable: false },
            { data: 'submitted_at', name: 'submitted_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[8, 'desc']],
        pageLength: 25,
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' }
    });

    $('#statusFilter').on('change', function () { table.ajax.reload(); });
});
</script>
@endpush
