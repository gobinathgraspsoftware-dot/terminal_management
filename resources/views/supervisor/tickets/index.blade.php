@extends('layouts.app')
@section('title', 'Team Tickets')

@section('content')
<div class="container-fluid">
    <div class="row g-3 mb-4">
        @foreach(['total'=>['Total','primary'],'open'=>['Open','secondary'],'assigned'=>['Assigned','info'],'in_progress'=>['In Progress','primary'],'scheduled'=>['Scheduled','warning'],'done_success'=>['Done/Success','success'],'done_fail'=>['Done/Fail','danger'],'sla_breached'=>['SLA Breached','danger']] as $key=>[$label,$color])
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <h3 class="mb-0 text-{{ $color }}">{{ $stats[$key] }}</h3>
                    <small class="text-muted">{{ $label }}</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($slaBreachedTickets->count() > 0)
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>{{ $slaBreachedTickets->count() }} ticket(s) have breached SLA!</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-ticket-detailed me-2"></i>Team Tickets</h5>
            @can('create_tickets')
            <a href="{{ route('supervisor.tickets.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Create Ticket
            </a>
            @endcan
        </div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-2">
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        @foreach(['open'=>'Open','assigned'=>'Assigned','in_progress'=>'In Progress','scheduled'=>'Scheduled','done_success'=>'Done / Success','done_fail'=>'Done / Fail','closed'=>'Closed'] as $k=>$v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter_priority" class="form-select form-select-sm">
                        <option value="">All Priority</option>
                        @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $k=>$v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
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
                <div class="col-md-3">
                    <input type="date" id="filter_date_from" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <input type="date" id="filter_date_to" class="form-control form-control-sm">
                </div>
            </div>

            <div class="table-responsive">
                <table id="ticketsTable" class="table table-sm table-hover w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket #</th>
                            <th>Vendor</th>
                            <th>Merchant</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Technician</th>
                            <th>SLA</th>
                            <th>Claim</th>
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
            url: '{{ route("supervisor.tickets.datatable") }}',
            data: function(d) {
                d.status = $('#filter_status').val();
                d.priority = $('#filter_priority').val();
                d.vendor_id = $('#filter_vendor').val();
                d.date_from = $('#filter_date_from').val();
                d.date_to = $('#filter_date_to').val();
            }
        },
        columns: [
            { data: 'ticket_no' },
            { data: 'vendor_name', searchable: false, orderable: false },
            { data: 'merchant_name' },
            { data: 'status_badge', searchable: false, orderable: false },
            { data: 'priority_badge', searchable: false, orderable: false },
            { data: 'technician_name', searchable: false, orderable: false },
            { data: null, render: function(d) {
                if (d.sla_breached) return '<span class="text-danger fw-bold">' + (d.sla_remaining || 'Breached') + '</span>';
                return d.sla_remaining || '-';
            }, searchable: false },
            { data: 'total_claim', searchable: false, orderable: false },
            { data: 'created_at' },
            { data: null, orderable: false, searchable: false, render: function(d) {
                return '<div class="btn-group btn-group-sm">' +
                    '<a href="{{ url("supervisor/tickets") }}/' + d.id + '" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>' +
                    '<a href="{{ url("supervisor/tickets") }}/' + d.id + '/edit" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>' +
                    '</div>';
            }}
        ],
        order: [[8, 'desc']],
        pageLength: 25
    });
    $('#filter_status, #filter_priority, #filter_vendor').on('change', function() { table.draw(); });
    $('#filter_date_from, #filter_date_to').on('change', function() { table.draw(); });
});
</script>
@endpush
