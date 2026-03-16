@extends('layouts.app')

@section('title', 'Ticket Claims')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">My Ticket Claims</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">Ticket Claims</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-ticket-detailed me-1"></i> My Ticket Claims</h6>
            <select id="status-filter" class="form-select form-select-sm" style="width:180px;">
                <option value="">All Statuses</option>
                <option value="submitted">Submitted</option>
                <option value="verified">Verified</option>
                <option value="non_claimable">Non-Claimable</option>
                <option value="pending_payment">Pending Payment</option>
                <option value="paid">Paid</option>
            </select>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="ticket-claims-table" class="table table-hover table-sm align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Claim No</th>
                            <th>Ticket No</th>
                            <th>Vendor</th>
                            <th>Merchant</th>
                            <th>Amount (RM)</th>
                            <th>Status</th>
                            <th>Submitted</th>
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
    var table = $('#ticket-claims-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("technician.claims.ticket-claims-data") }}',
            data: function(d) {
                d.status = $('#status-filter').val();
            }
        },
        columns: [
            { data: 'claim_no' },
            { data: 'ticket_no' },
            { data: 'vendor' },
            { data: 'merchant_name' },
            { data: 'total_amount', className: 'text-end' },
            { data: 'status', className: 'text-center' },
            { data: 'submitted_at' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[6, 'desc']],
        pageLength: 10,
        language: { emptyTable: 'No ticket claims found.' }
    });

    $('#status-filter').on('change', function() { table.ajax.reload(); });
});
</script>
@endpush
