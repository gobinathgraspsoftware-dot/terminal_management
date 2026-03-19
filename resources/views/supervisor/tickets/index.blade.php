@extends('layouts.app')
@section('title', 'Ticket Management')

@section('content')
<div class="container-fluid">
    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        @php
            $statCards = [
                ['label' => 'Total', 'key' => 'total', 'bg' => 'primary', 'icon' => 'bi-ticket-detailed'],
                ['label' => 'Open', 'key' => 'open', 'bg' => 'secondary', 'icon' => 'bi-folder2-open'],
                ['label' => 'Assigned', 'key' => 'assigned', 'bg' => 'info', 'icon' => 'bi-person-check'],
                ['label' => 'Accepted', 'key' => 'accepted', 'bg' => 'primary', 'icon' => 'bi-check-circle'],
                ['label' => 'In Progress', 'key' => 'in_progress', 'bg' => 'primary', 'icon' => 'bi-gear'],
                ['label' => 'Scheduled', 'key' => 'scheduled', 'bg' => 'warning', 'icon' => 'bi-calendar-event'],
                ['label' => 'Success', 'key' => 'done_success', 'bg' => 'success', 'icon' => 'bi-check-circle-fill'],
                ['label' => 'Failed', 'key' => 'done_fail', 'bg' => 'danger', 'icon' => 'bi-x-circle-fill'],
                ['label' => 'Closed', 'key' => 'closed', 'bg' => 'dark', 'icon' => 'bi-lock-fill'],
                ['label' => 'SLA Breach', 'key' => 'sla_breached', 'bg' => 'danger', 'icon' => 'bi-exclamation-triangle-fill'],
            ];
        @endphp
        @foreach($statCards as $card)
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-sm h-100 stat-card" data-status="{{ $card['key'] }}" style="cursor:pointer;">
                <div class="card-body p-3 text-center">
                    <i class="bi {{ $card['icon'] }} text-{{ $card['bg'] }} fs-4"></i>
                    <h4 class="mb-0 mt-1">{{ $stats[$card['key']] ?? 0 }}</h4>
                    <small class="text-muted">{{ $card['label'] }}</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($slaBreachedTickets->count() > 0)
    <div class="alert alert-danger d-flex align-items-center mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <strong>{{ $slaBreachedTickets->count() }} ticket(s) have breached SLA!</strong>
    </div>
    @endif

    {{-- Filters --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
            <button class="btn btn-sm btn-outline-secondary" id="btnResetFilters"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(\App\Models\Ticket::getStatuses() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priority</label>
                    <select id="filterPriority" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(\App\Models\Ticket::getPriorities() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" id="filterDateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" id="filterDateTo" class="form-control form-control-sm">
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-ticket-detailed me-2"></i>Tickets</h5>
            @can('create_tickets')
            <a href="{{ route('supervisor.tickets.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>New Ticket
            </a>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="ticketsTable" class="table table-sm table-hover w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket No</th>
                            <th>Vendor</th>
                            <th>Merchant</th>
                            <th>Category / Type</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Technician</th>
                            <th>SLA</th>
                            <th>Created</th>
                            <th>Actions</th>
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
    let table = $('#ticketsTable').DataTable({
        processing: true, serverSide: true, pageLength: 25,
        ajax: {
            url: '{{ route("supervisor.tickets.datatable") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.priority = $('#filterPriority').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
            }
        },
        columns: [
            { data: 'ticket_no', render: (data, type, row) => `<a href="/supervisor/tickets/${row.id}" class="fw-bold text-decoration-none">${data}</a>` },
            { data: 'vendor_name' },
            { data: 'merchant_name', render: (data, type, row) => data + (row.tid !== '-' ? `<br><small class="text-muted">TID: ${row.tid}</small>` : '') },
            { data: 'job_category', render: (data, type, row) => data + `<br><small class="text-muted">${row.job_type}</small>` },
            { data: 'price', className: 'text-end' },
            { data: 'status_badge' },
            { data: 'priority_badge' },
            { data: 'technician_name' },
            { data: 'sla_remaining', render: (data, type, row) => !data ? '-' : `<span class="${row.sla_breached ? 'text-danger fw-bold' : 'text-success'}">${data}</span>` },
            { data: 'created_at' },
            { data: null, orderable: false, render: (data, type, row) =>
                `<div class="btn-group btn-group-sm">
                    <a href="/supervisor/tickets/${row.id}" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                    <a href="/supervisor/tickets/${row.id}/edit" class="btn btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                </div>`
            }
        ],
        order: [[9, 'desc']]
    });

    $('#filterStatus, #filterPriority').on('change', () => table.ajax.reload());
    $('#filterDateFrom, #filterDateTo').on('change', () => table.ajax.reload());
    $('#btnResetFilters').on('click', function() {
        $('#filterStatus, #filterPriority').val('');
        $('#filterDateFrom, #filterDateTo').val('');
        table.ajax.reload();
    });
    $('.stat-card').on('click', function() {
        let s = $(this).data('status');
        $('#filterStatus').val(s === 'total' || s === 'sla_breached' ? '' : s);
        table.ajax.reload();
    });
});
</script>
@endpush
