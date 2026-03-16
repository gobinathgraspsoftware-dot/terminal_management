@extends('layouts.app')

@section('title', 'My Claims')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">My Claims</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Claims</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="text-primary fs-3 fw-bold">{{ $stats['ticket_total'] }}</div>
                    <small class="text-muted">Ticket Claims</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="text-info fs-3 fw-bold">{{ $stats['ticket_submitted'] }}</div>
                    <small class="text-muted">Ticket Submitted</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="text-success fs-3 fw-bold">{{ $stats['other_total'] }}</div>
                    <small class="text-muted">Other Claims</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="text-warning fs-3 fw-bold">{{ $stats['other_submitted'] }}</div>
                    <small class="text-muted">Other Submitted</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'ticket' ? 'active' : '' }}" id="ticket-tab"
                            data-bs-toggle="tab" data-bs-target="#ticket-claims-pane" type="button" role="tab">
                        <i class="bi bi-ticket-detailed me-1"></i> Ticket Claims
                        <span class="badge bg-primary ms-1">{{ $stats['ticket_total'] }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'other' ? 'active' : '' }}" id="other-tab"
                            data-bs-toggle="tab" data-bs-target="#other-claims-pane" type="button" role="tab">
                        <i class="bi bi-file-earmark-text me-1"></i> Other Claims
                        <span class="badge bg-success ms-1">{{ $stats['other_total'] }}</span>
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                {{-- Ticket Claims Tab --}}
                <div class="tab-pane fade {{ $activeTab === 'ticket' ? 'show active' : '' }}" id="ticket-claims-pane" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <select id="ticket-status-filter" class="form-select form-select-sm" style="width:180px;">
                                <option value="">All Statuses</option>
                                <option value="submitted">Submitted</option>
                                <option value="verified">Verified</option>
                                <option value="non_claimable">Non-Claimable</option>
                                <option value="pending_payment">Pending Payment</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div>
                            @can('create_claims')
                            <a href="{{ route('technician.claims.create-ticket-claim') }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Create Ticket Claim
                            </a>
                            @endcan
                        </div>
                    </div>
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

                {{-- Other Claims Tab --}}
                <div class="tab-pane fade {{ $activeTab === 'other' ? 'show active' : '' }}" id="other-claims-pane" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <select id="other-status-filter" class="form-select form-select-sm" style="width:180px;">
                                <option value="">All Statuses</option>
                                <option value="submitted">Submitted</option>
                                <option value="verified">Verified</option>
                                <option value="non_claimable">Non-Claimable</option>
                                <option value="pending_payment">Pending Payment</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div>
                            @can('create_claims')
                            <a href="{{ route('technician.claims.create') }}" class="btn btn-sm btn-success">
                                <i class="bi bi-plus-circle me-1"></i> Create Other Claim
                            </a>
                            @endcan
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="other-claims-table" class="table table-hover table-sm align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>Claim No</th>
                                    <th>Claim Type</th>
                                    <th>Description</th>
                                    <th>Amount (RM)</th>
                                    <th>Status</th>
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
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Ticket Claims DataTable
    var ticketTable = $('#ticket-claims-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("technician.claims.ticket-claims-data") }}',
            data: function(d) { d.status = $('#ticket-status-filter').val(); }
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

    // Other Claims DataTable
    var otherTable = $('#other-claims-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("technician.claims.other-claims-data") }}',
            data: function(d) { d.status = $('#other-status-filter').val(); }
        },
        columns: [
            { data: 'claim_no' },
            { data: 'claim_type' },
            { data: 'description' },
            { data: 'total_amount', className: 'text-end' },
            { data: 'status', className: 'text-center' },
            { data: 'submitted_at' },
            { data: 'has_attachments', className: 'text-center', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[5, 'desc']],
        pageLength: 10,
        language: { emptyTable: 'No other claims found.' }
    });

    $('#ticket-status-filter').on('change', function() { ticketTable.ajax.reload(); });
    $('#other-status-filter').on('change', function() { otherTable.ajax.reload(); });

    // Reload inactive table when tab shown
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        var target = $(e.target).data('bs-target');
        if (target === '#ticket-claims-pane') ticketTable.columns.adjust().draw(false);
        if (target === '#other-claims-pane') otherTable.columns.adjust().draw(false);
    });
});
</script>
@endpush
