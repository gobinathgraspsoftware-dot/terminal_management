@extends('layouts.app')
@section('title', 'Ticket Management')

@section('content')
<div class="container-fluid">
    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <h3 class="mb-0 text-primary">{{ $stats['total'] }}</h3>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <h3 class="mb-0 text-secondary">{{ $stats['open'] }}</h3>
                    <small class="text-muted">Open</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <h3 class="mb-0 text-info">{{ $stats['assigned'] }}</h3>
                    <small class="text-muted">Assigned</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <h3 class="mb-0 text-primary">{{ $stats['in_progress'] }}</h3>
                    <small class="text-muted">In Progress</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <h3 class="mb-0 text-warning">{{ $stats['scheduled'] }}</h3>
                    <small class="text-muted">Scheduled</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <h3 class="mb-0 text-success">{{ $stats['done_success'] }}</h3>
                    <small class="text-muted">Done/Success</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <h3 class="mb-0 text-danger">{{ $stats['done_fail'] }}</h3>
                    <small class="text-muted">Done/Fail</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <h3 class="mb-0 text-danger">{{ $stats['sla_breached'] }}</h3>
                    <small class="text-muted">SLA Breached</small>
                </div>
            </div>
        </div>
    </div>

    {{-- SLA Breach Alert --}}
    @if($slaBreachedTickets->count() > 0)
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>{{ $slaBreachedTickets->count() }} ticket(s) have breached SLA!</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Filters & Actions --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-ticket-detailed me-2"></i>All Tickets</h5>
            @can('create_tickets')
            <a href="{{ route('admin.tickets.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Create Ticket
            </a>
            @endcan
        </div>
        <div class="card-body">
            {{-- Filters Row --}}
            <div class="row g-2 mb-3">
                <div class="col-md-2">
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="open">Open</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="done_success">Done / Success</option>
                        <option value="done_fail">Done / Fail</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter_priority" class="form-select form-select-sm">
                        <option value="">All Priority</option>
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter_vendor" class="form-select form-select-sm">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->vendor_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter_supervisor" class="form-select form-select-sm">
                        <option value="">All Supervisors</option>
                        @foreach($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" id="filter_date_from" class="form-control form-control-sm" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" id="filter_date_to" class="form-control form-control-sm" placeholder="To">
                </div>
            </div>

            {{-- DataTable --}}
            <div class="table-responsive">
                <table id="ticketsTable" class="table table-sm table-hover w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket #</th>
                            <th>Vendor</th>
                            <th>Merchant</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Supervisor</th>
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
    var table = $('#ticketsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.tickets.datatable") }}',
            data: function(d) {
                d.status = $('#filter_status').val();
                d.priority = $('#filter_priority').val();
                d.vendor_id = $('#filter_vendor').val();
                d.supervisor_id = $('#filter_supervisor').val();
                d.date_from = $('#filter_date_from').val();
                d.date_to = $('#filter_date_to').val();
            }
        },
        columns: [
            { data: 'ticket_no', name: 'ticket_no' },
            { data: 'vendor_name', name: 'vendor_id', searchable: false, orderable: false },
            { data: 'merchant_name', name: 'merchant_name' },
            { data: 'status_badge', name: 'status', searchable: false, orderable: false },
            { data: 'priority_badge', name: 'priority', searchable: false, orderable: false },
            { data: 'supervisor_name', name: 'supervisor_id', searchable: false, orderable: false },
            { data: 'technician_name', name: 'technician_id', searchable: false, orderable: false },
            { data: null, name: 'sla_deadline', render: function(data) {
                if (data.sla_breached) return '<span class="text-danger fw-bold">' + (data.sla_remaining || 'Breached') + '</span>';
                return data.sla_remaining || '-';
            }, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: null, name: null, orderable: false, searchable: false, render: function(data) {
                return '<div class="btn-group btn-group-sm">' +
                    '<a href="{{ url("admin/tickets") }}/' + data.id + '" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>' +
                    '<a href="{{ url("admin/tickets") }}/' + data.id + '/edit" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>' +
                    '</div>';
            }}
        ],
        order: [[8, 'desc']],
        pageLength: 25,
        language: { search: '', searchPlaceholder: 'Search tickets...' }
    });

    $('#filter_status, #filter_priority, #filter_vendor, #filter_supervisor').on('change', function() { table.draw(); });
    $('#filter_date_from, #filter_date_to').on('change', function() { table.draw(); });
});
</script>
@endpush
