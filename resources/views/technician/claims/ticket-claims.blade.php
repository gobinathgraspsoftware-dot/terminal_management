@extends('layouts.app')
@section('title', 'My Ticket Claims')
@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1><i class="bi bi-ticket-detailed me-2 text-info"></i>My Ticket Claims</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('technician.claims.index') }}">Claims</a></li>
                <li class="breadcrumb-item active">Ticket Claims</li>
            </ol>
        </div>
    </div>
</div>

<div class="alert alert-info border-0 py-2">
    <i class="bi bi-info-circle me-2"></i>
    Ticket claims are automatically created when your tickets are completed. To update the claim amount, edit the claim fields on the ticket itself.
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-table me-2"></i>Ticket Claims</span>
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
    var table = $('#ticketClaimsTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: '{{ route("technician.claims.ticket-claims-data") }}',
            data: function(d){ d.status = $('#statusFilter').val(); }
        },
        columns: [
            { data: 'claim_no' }, { data: 'ticket_no', orderable: false },
            { data: 'vendor', orderable: false }, { data: 'merchant_name', orderable: false },
            { data: 'total_amount', className: 'text-end' }, { data: 'status', orderable: false },
            { data: 'submitted_at' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[6, 'desc']], pageLength: 25,
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div>' }
    });
    $('#statusFilter').on('change', function(){ table.ajax.reload(); });
});
</script>
@endpush
