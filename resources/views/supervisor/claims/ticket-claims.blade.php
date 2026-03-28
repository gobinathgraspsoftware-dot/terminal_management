@extends('layouts.app')
@section('title', 'Ticket Claims')
@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1><i class="bi bi-ticket-detailed me-2 text-info"></i>Ticket Claims</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('supervisor.claims.index') }}">Claims</a></li>
                <li class="breadcrumb-item active">Ticket Claims</li>
            </ol>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-table me-2"></i>
            {{ $isInternal ? 'Team Ticket Claims' : 'My Ticket Claims' }}
        </span>
        <select id="statusFilter" class="form-select form-select-sm" style="width:160px">
            <option value="">All Statuses</option>
            <option value="submitted">Submitted</option>
            <option value="verified">Verified</option>
            <option value="pending_payment">Pending Payment</option>
            <option value="paid">Paid</option>
        </select>
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
                        @if($isInternal)<th>Technician</th>@endif
                        <th class="text-end">Amount (RM)</th>
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
    var isInternal = {{ $isInternal ? 'true' : 'false' }};
    var cols = [
        { data: 'claim_no' },
        { data: 'ticket_no', orderable: false },
        { data: 'vendor', orderable: false },
        { data: 'merchant_name', orderable: false },
    ];
    if (isInternal) cols.push({ data: 'technician', orderable: false });
    cols.push(
        { data: 'total_amount', className: 'text-end' },
        { data: 'status', orderable: false },
        { data: 'submitted_at' },
        { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
    );

    var table = $('#ticketClaimsTable').DataTable({
        processing: true, serverSide: true,
        ajax: { url: '{{ route("supervisor.claims.ticket-claims-data") }}', data: function(d){ d.status = $('#statusFilter').val(); } },
        columns: cols,
        order: [[isInternal ? 7 : 6, 'desc']],
        pageLength: 25,
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' }
    });
    $('#statusFilter').on('change', function(){ table.ajax.reload(); });
});
</script>
@endpush
