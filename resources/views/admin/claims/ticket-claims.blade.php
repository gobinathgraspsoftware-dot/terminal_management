@extends('layouts.app')

@section('title', 'Ticket Claims')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Ticket Claims</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.claims.index') }}">Claim Management</a></li>
                    <li class="breadcrumb-item active">Ticket Claims</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @can('export_claims')
            <a href="{{ route('admin.claims.export', ['category' => 'ticket']) }}" class="btn btn-outline-success">
                <i class="bi bi-download me-1"></i> Export
            </a>
            @endcan
            @can('bulk_pay_claims')
            <a href="{{ route('admin.claims.bulk-payment', ['category' => 'ticket']) }}" class="btn btn-success">
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
                        @foreach(\App\Models\Claim::getStatuses() as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
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
                <table id="ticket-claims-table" class="table table-hover table-sm align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Claim ID</th>
                            <th>Ticket ID</th>
                            <th>Vendor</th>
                            <th>Merchant</th>
                            <th>Supervisor</th>
                            <th>Technician</th>
                            <th class="text-end">Amount (RM)</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
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
            url: '{{ route("admin.claims.ticket-claims-data") }}',
            data: function(d) {
                d.status = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'claim_no', name: 'claim_no' },
            { data: 'ticket_no', name: 'ticket_no' },
            { data: 'vendor', name: 'vendor' },
            { data: 'merchant_name', name: 'merchant_name' },
            { data: 'supervisor', name: 'supervisor' },
            { data: 'technician', name: 'technician' },
            { data: 'total_amount', name: 'total_amount', className: 'text-end' },
            { data: 'status', name: 'status', orderable: false },
            { data: 'submitted_at', name: 'submitted_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[8, 'desc']],
        pageLength: 25,
        language: { emptyTable: 'No ticket claims found.' }
    });

    $('#filter-status').on('change', function() { table.ajax.reload(); });
    $('#btn-reset-filters').on('click', function() {
        $('#filter-status').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
