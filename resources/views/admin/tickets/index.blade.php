@extends('layouts.app')

@section('title', 'Ticket Management')

@push('styles')
<style>
    .stat-card {
        border: none;
        border-radius: 10px;
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .stat-card .stat-count {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1;
    }
    .sla-alert {
        border-left: 4px solid #dc3545;
        background: #fff5f5;
    }
    .sla-alert .sla-item {
        padding: 8px 12px;
        border-bottom: 1px solid #ffe0e0;
    }
    .sla-alert .sla-item:last-child { border-bottom: none; }
    .filter-section { background: #fff; border-radius: 10px; }
    .status-filter-btn { border-radius: 20px; font-size: 0.85rem; }
    .status-filter-btn.active { box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
    .sla-breach-badge { animation: pulse 1.5s infinite; }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-ticket-detailed me-2"></i>Ticket Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Tickets</li>
                </ol>
            </nav>
        </div>
        @can('create_tickets')
        <a href="{{ route('admin.tickets.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> New Ticket
        </a>
        @endcan
    </div>

    <!-- SLA Breach Reminders -->
    @if($slaBreachedTickets->count() > 0)
    <div class="card sla-alert mb-4">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-2">
            <span><i class="bi bi-exclamation-triangle-fill me-2"></i>SLA Breach Alerts ({{ $slaBreachedTickets->count() }})</span>
            <button class="btn btn-sm btn-outline-light" data-bs-toggle="collapse" data-bs-target="#slaAlerts">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div id="slaAlerts" class="collapse show">
            <div class="card-body p-0" style="max-height: 200px; overflow-y: auto;">
                @foreach($slaBreachedTickets as $bt)
                <div class="sla-item d-flex justify-content-between align-items-center">
                    <div>
                        <a href="{{ route('admin.tickets.show', $bt->id) }}" class="fw-bold text-danger text-decoration-none">
                            {{ $bt->ticket_no }}
                        </a>
                        <span class="text-muted ms-2">{{ $bt->vendor?->vendor_name }}</span>
                        <span class="text-muted ms-2">| {{ $bt->supervisor?->name ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="badge bg-danger sla-breach-badge">
                            <i class="bi bi-clock me-1"></i>{{ $bt->sla_remaining }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-xl">
            <div class="card stat-card" onclick="filterByStatus('')">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-light text-dark me-3"><i class="bi bi-collection"></i></div>
                        <div>
                            <div class="stat-count">{{ $stats['total'] }}</div>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card stat-card" onclick="filterByStatus('open')">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3"><i class="bi bi-folder2-open"></i></div>
                        <div>
                            <div class="stat-count text-primary">{{ $stats['open'] }}</div>
                            <small class="text-muted">Open</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card stat-card" onclick="filterByStatus('assigned')">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info me-3"><i class="bi bi-person-check"></i></div>
                        <div>
                            <div class="stat-count text-info">{{ $stats['assigned'] }}</div>
                            <small class="text-muted">Assigned</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card stat-card" onclick="filterByStatus('in_progress')">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3"><i class="bi bi-gear-wide-connected"></i></div>
                        <div>
                            <div class="stat-count text-warning">{{ $stats['in_progress'] }}</div>
                            <small class="text-muted">In Progress</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card stat-card" onclick="filterByStatus('completed')">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success me-3"><i class="bi bi-check-circle"></i></div>
                        <div>
                            <div class="stat-count text-success">{{ $stats['completed'] }}</div>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card stat-card" onclick="filterByStatus('')" data-sla="breach">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3"><i class="bi bi-alarm"></i></div>
                        <div>
                            <div class="stat-count text-danger">{{ $stats['sla_breached'] }}</div>
                            <small class="text-muted">SLA Breach</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Table -->
    <div class="card">
        <div class="card-header filter-section">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="rescheduled">Rescheduled</option>
                        <option value="completed">Completed</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Priority</label>
                    <select id="filterPriority" class="form-select form-select-sm">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Vendor</label>
                    <select id="filterVendor" class="form-select form-select-sm">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $v)
                        <option value="{{ $v->id }}">{{ $v->vendor_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">SLA Breach</label>
                    <select id="filterSla" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="yes">Breached Only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" id="filterDateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" id="filterDateTo" class="form-control form-control-sm">
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="ticketsTable" class="table table-hover table-sm align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket #</th>
                            <th>Vendor</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Supervisor</th>
                            <th>Assignee</th>
                            <th>SLA Deadline</th>
                            <th>Created</th>
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
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#ticketsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.tickets.datatable") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.priority = $('#filterPriority').val();
                d.vendor_id = $('#filterVendor').val();
                d.sla_breach = $('#filterSla').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    window.location.href = '{{ route("login") }}';
                }
            }
        },
        columns: [
            {
                data: 'ticket_no',
                render: function(data, type, row) {
                    var sla = row.sla_breached ? ' <span class="badge bg-danger sla-breach-badge" title="SLA Breached"><i class="bi bi-alarm"></i></span>' : '';
                    return '<a href="{{ route("admin.tickets.index") }}/' + row.id + '" class="fw-bold text-decoration-none">' + data + '</a>' + sla;
                }
            },
            { data: 'vendor_name' },
            {
                data: 'status_label',
                render: function(data, type, row) {
                    return '<span class="badge bg-' + row.status_badge + '">' + data + '</span>';
                }
            },
            {
                data: 'priority_label',
                render: function(data, type, row) {
                    return '<span class="badge bg-' + row.priority_badge + '">' + data + '</span>';
                }
            },
            { data: 'supervisor_name' },
            {
                data: 'technician_name',
                render: function(data, type, row) {
                    return data === 'Unassigned' ? '<span class="text-muted fst-italic">Unassigned</span>' : data;
                }
            },
            {
                data: 'sla_deadline',
                render: function(data, type, row) {
                    if (!data) return '-';
                    var cls = row.sla_breached ? 'text-danger fw-bold' : '';
                    return '<span class="' + cls + '">' + data + '</span>' +
                           (row.sla_remaining ? '<br><small class="' + cls + '">' + row.sla_remaining + '</small>' : '');
                }
            },
            { data: 'created_at' },
            {
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    var html = '<div class="btn-group btn-group-sm">';
                    html += '<a href="{{ route("admin.tickets.index") }}/' + row.id + '" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>';
                    @can('edit_tickets')
                    if (row.status !== 'completed' && row.status !== 'closed') {
                        html += '<a href="{{ route("admin.tickets.index") }}/' + row.id + '/edit" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>';
                    }
                    @endcan
                    @can('delete_tickets')
                    html += '<button class="btn btn-outline-danger btn-delete" data-id="' + row.id + '" title="Delete"><i class="bi bi-trash"></i></button>';
                    @endcan
                    html += '</div>';
                    return html;
                }
            }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border text-primary spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No tickets found',
        }
    });

    // Filter changes
    $('#filterStatus, #filterPriority, #filterVendor, #filterSla, #filterDateFrom, #filterDateTo').on('change', function() {
        table.ajax.reload();
    });

    // Delete handler
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        confirmAction('Delete Ticket?', 'This action cannot be undone.', function() {
            $.ajax({
                url: '{{ route("admin.tickets.index") }}/' + id,
                method: 'DELETE',
                success: function(res) {
                    if (res.success) {
                        showToast(res.message);
                        table.ajax.reload();
                    }
                },
                error: function() { showToast('Failed to delete ticket.', 'error'); }
            });
        });
    });
});

function filterByStatus(status) {
    $('#filterStatus').val(status).trigger('change');
}
</script>
@endpush
