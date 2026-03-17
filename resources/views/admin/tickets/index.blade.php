@php use App\Models\Ticket; @endphp
@extends('layouts.app')

@section('title', 'Ticket Management')

@section('content')
<div class="container-fluid">
    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-primary mb-0">{{ $stats['total'] }}</h3>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-secondary mb-0">{{ $stats['open'] }}</h3>
                    <small class="text-muted">Open</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-info mb-0">{{ $stats['assigned'] }}</h3>
                    <small class="text-muted">Assigned</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-primary mb-0">{{ $stats['in_progress'] }}</h3>
                    <small class="text-muted">In Progress</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-warning mb-0">{{ $stats['scheduled'] }}</h3>
                    <small class="text-muted">Scheduled</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-success mb-0">{{ $stats['done_success'] }}</h3>
                    <small class="text-muted">Done/Success</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-danger mb-0">{{ $stats['done_fail'] }}</h3>
                    <small class="text-muted">Done/Fail</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-dark mb-0">{{ $stats['closed'] }}</h3>
                    <small class="text-muted">Closed</small>
                </div>
            </div>
        </div>
    </div>

    {{-- SLA Breach Alert --}}
    @if($slaBreachedTickets->count())
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>
            <strong>{{ $slaBreachedTickets->count() }} ticket(s) have breached SLA!</strong>
            <span class="ms-2">
                @foreach($slaBreachedTickets->take(5) as $bt)
                    <a href="{{ route('admin.tickets.show', $bt->id) }}" class="text-danger fw-bold">{{ $bt->ticket_no }}</a>{{ !$loop->last ? ', ' : '' }}
                @endforeach
                @if($slaBreachedTickets->count() > 5) <span class="text-muted">and {{ $slaBreachedTickets->count() - 5 }} more...</span> @endif
            </span>
        </div>
    </div>
    @endif

    {{-- Filter & Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0"><i class="bi bi-ticket-detailed me-2"></i>Tickets</h5>
            @can('create_tickets')
            <a href="{{ route('admin.tickets.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>New Ticket
            </a>
            @endcan
        </div>
        <div class="card-body">
            {{-- Filters --}}
            <div class="row g-2 mb-3">
                <div class="col-md-2">
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        @foreach(Ticket::getStatuses() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter_priority" class="form-select form-select-sm">
                        <option value="">All Priority</option>
                        @foreach(Ticket::getPriorities() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter_vendor" class="form-select form-select-sm">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}">{{ $v->vendor_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter_supervisor" class="form-select form-select-sm">
                        <option value="">All Supervisors</option>
                        @foreach($supervisors as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter_sla" class="form-select form-select-sm">
                        <option value="">SLA: All</option>
                        <option value="yes">Breached Only</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" id="btn_reset_filters" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-circle me-1"></i>Reset
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="ticketsTable" class="table table-sm table-hover align-middle w-100">
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
                d.sla_breach = $('#filter_sla').val();
            }
        },
        columns: [
            { data: 'ticket_no', render: function(data, type, row) {
                return '<a href="{{ url("admin/tickets") }}/' + row.id + '" class="fw-bold text-decoration-none">' + data + '</a>' +
                       (row.vendor_ticket_ref_no !== '-' ? '<br><small class="text-muted">' + row.vendor_ticket_ref_no + '</small>' : '');
            }},
            { data: 'vendor_name' },
            { data: 'merchant_name', render: function(data, type, row) {
                return data + (row.tid !== '-' ? '<br><small class="text-muted">TID: ' + row.tid + '</small>' : '');
            }},
            { data: 'status_badge', orderable: true },
            { data: 'priority_badge', orderable: true },
            { data: 'supervisor_name' },
            { data: 'technician_name' },
            { data: 'sla_deadline', render: function(data, type, row) {
                if (!data) return '-';
                var html = data;
                if (row.sla_breached) {
                    html += '<br><span class="badge bg-danger">BREACHED</span>';
                } else if (row.sla_remaining) {
                    html += '<br><small class="text-muted">' + row.sla_remaining + '</small>';
                }
                return html;
            }},
            { data: 'created_at' },
            { data: null, className: 'text-center', orderable: false, render: function(data, type, row) {
                return '<a href="{{ url("admin/tickets") }}/' + row.id + '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>';
            }}
        ],
        order: [[8, 'desc']],
        pageLength: 25,
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading...' }
    });

    $('#filter_status, #filter_priority, #filter_vendor, #filter_supervisor, #filter_sla').on('change', function() {
        table.ajax.reload();
    });

    $('#btn_reset_filters').on('click', function() {
        $('#filter_status, #filter_priority, #filter_vendor, #filter_supervisor, #filter_sla').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
