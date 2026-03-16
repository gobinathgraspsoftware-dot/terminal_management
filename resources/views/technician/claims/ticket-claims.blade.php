@extends('layouts.app')

@section('title', 'My Ticket Claims')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-ticket-detailed me-2"></i>My Ticket Claims</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">Ticket Claims</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('technician.claims.create-ticket-claim') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> New Ticket Claim
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="ticketClaimsTable" class="table table-striped table-hover" style="width:100%">
                    <thead>
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
    $('#ticketClaimsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("technician.claims.ticket-claims-data") }}',
        columns: [
            { data: 'claim_no' },
            { data: 'ticket_no' },
            { data: 'vendor' },
            { data: 'merchant_name' },
            { data: 'total_amount', className: 'text-end' },
            { data: 'status', orderable: false },
            { data: 'submitted_at' },
            { data: 'actions', orderable: false, searchable: false },
        ],
        order: [[6, 'desc']],
        responsive: true,
    });
});
</script>
@endpush
