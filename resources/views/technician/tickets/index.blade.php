@extends('layouts.app')
@section('title', 'My Tickets')

@section('content')
<div class="container-fluid">
    <div class="row g-3 mb-4">
        @foreach(['total'=>['Total','primary'],'in_progress'=>['In Progress','primary'],'scheduled'=>['Scheduled','warning'],'done_success'=>['Done/Success','success'],'done_fail'=>['Done/Fail','danger'],'sla_breached'=>['SLA Breached','danger']] as $key=>[$label,$color])
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <h3 class="mb-0 text-{{ $color }}">{{ $stats[$key] ?? 0 }}</h3>
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

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-ticket-detailed me-2"></i>My Tickets</h5>
        </div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        @foreach(['assigned'=>'Assigned','in_progress'=>'In Progress','scheduled'=>'Scheduled','done_success'=>'Done / Success','done_fail'=>'Done / Fail','closed'=>'Closed'] as $k=>$v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filter_priority" class="form-select form-select-sm">
                        <option value="">All Priority</option>
                        <option value="low">Low</option><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option>
                    </select>
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
        processing: true, serverSide: true,
        ajax: { url: '{{ route("technician.tickets.datatable") }}', data: function(d) { d.status=$('#filter_status').val(); d.priority=$('#filter_priority').val(); } },
        columns: [
            { data: 'ticket_no' },
            { data: 'vendor_name', searchable:false, orderable:false },
            { data: 'merchant_name' },
            { data: 'status_badge', searchable:false, orderable:false },
            { data: 'priority_badge', searchable:false, orderable:false },
            { data: null, render: function(d) { return d.sla_breached ? '<span class="text-danger fw-bold">'+(d.sla_remaining||'Breached')+'</span>' : (d.sla_remaining||'-'); }, searchable:false },
            { data: 'total_claim', searchable:false, orderable:false },
            { data: 'created_at' },
            { data: null, orderable:false, searchable:false, render: function(d) { return '<a href="{{ url("technician/tickets") }}/'+d.id+'" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>'; }}
        ],
        order: [[7, 'desc']], pageLength: 25
    });
    $('#filter_status, #filter_priority').on('change', function() { table.draw(); });
});
</script>
@endpush
