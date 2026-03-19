@extends('layouts.app')

@section('title', 'Ticket Claims')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Ticket Claims</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">Ticket Claims</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($isInternal)
    <div class="alert alert-info d-flex align-items-center mb-3">
        <i class="bi bi-info-circle-fill me-2"></i>
        <span><strong>Internal Supervisor</strong> — Viewing team claims for oversight. Claims are not applicable for your role.</span>
    </div>
    @endif

    {{-- Status Filter --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0 small fw-bold">Filter by Status:</label>
                </div>
                <div class="col-md-3">
                    <select id="statusFilter" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        @foreach(\App\Models\Claim::getStatuses() as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="ticketClaimsTable" class="table table-hover table-sm align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Claim No</th>
                            <th>Ticket No</th>
                            <th>Vendor</th>
                            <th>Merchant</th>
                            <th>Technician</th>
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
    let rowCounter = 0;
    const table = $('#ticketClaimsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.claims.ticket-claims-data") }}',
            data: function(d) {
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { data: null, orderable: false, searchable: false, render: function(data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            }},
            { data: 'claim_no' },
            { data: 'ticket_no' },
            { data: 'vendor' },
            { data: 'merchant_name' },
            { data: 'technician' },
            { data: 'total_amount', className: 'text-end' },
            { data: 'status', orderable: false },
            { data: 'submitted_at' },
            { data: 'actions', orderable: false, searchable: false },
        ],
        order: [[8, 'desc']],
        pageLength: 25,
        language: { emptyTable: 'No ticket claims found.' }
    });

    $('#statusFilter').on('change', function() {
        table.ajax.reload();
    });
});
</script>
@endpush
