@php use App\Models\Ticket; @endphp
@extends('layouts.app')

@section('title', 'My Tickets')

@section('content')
<div class="container-fluid">
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100"><div class="card-body text-center"><h3 class="fw-bold text-primary mb-0">{{ $stats['total'] }}</h3><small class="text-muted">Total</small></div></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100"><div class="card-body text-center"><h3 class="fw-bold text-info mb-0">{{ $stats['assigned'] }}</h3><small class="text-muted">Assigned</small></div></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100"><div class="card-body text-center"><h3 class="fw-bold text-primary mb-0">{{ $stats['in_progress'] }}</h3><small class="text-muted">In Progress</small></div></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100"><div class="card-body text-center"><h3 class="fw-bold text-warning mb-0">{{ $stats['scheduled'] }}</h3><small class="text-muted">Scheduled</small></div></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100"><div class="card-body text-center"><h3 class="fw-bold text-success mb-0">{{ $stats['done_success'] }}</h3><small class="text-muted">Done/Success</small></div></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="card border-0 shadow-sm h-100"><div class="card-body text-center"><h3 class="fw-bold text-danger mb-0">{{ $stats['done_fail'] }}</h3><small class="text-muted">Done/Fail</small></div></div>
        </div>
    </div>

    @if($slaBreachedTickets->count())
    <div class="alert alert-danger d-flex align-items-center mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>
            <strong>{{ $slaBreachedTickets->count() }} ticket(s) breached SLA!</strong>
            @foreach($slaBreachedTickets->take(5) as $bt)
                <a href="{{ route('technician.tickets.show', $bt->id) }}" class="text-danger fw-bold ms-1">{{ $bt->ticket_no }}</a>{{ !$loop->last ? ',' : '' }}
            @endforeach
        </div>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h5 class="mb-0"><i class="bi bi-ticket-detailed me-2"></i>My Tickets</h5></div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        @foreach(Ticket::getStatuses() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filter_priority" class="form-select form-select-sm">
                        <option value="">All Priority</option>
                        @foreach(Ticket::getPriorities() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" id="btn_reset_filters" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-x-circle me-1"></i>Reset</button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="ticketsTable" class="table table-sm table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr><th>Ticket #</th><th>Vendor</th><th>Merchant</th><th>Status</th><th>Priority</th><th>SLA</th><th>Created</th><th class="text-center">View</th></tr>
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
        ajax: {
            url: '{{ route("technician.tickets.datatable") }}',
            data: function(d) { d.status = $('#filter_status').val(); d.priority = $('#filter_priority').val(); }
        },
        columns: [
            { data: 'ticket_no', render: function(d,t,r) { return '<a href="{{ url("technician/tickets") }}/' + r.id + '" class="fw-bold text-decoration-none">' + d + '</a>'; }},
            { data: 'vendor_name' },
            { data: 'merchant_name', render: function(d,t,r) { return d + (r.tid !== '-' ? '<br><small class="text-muted">TID: ' + r.tid + '</small>' : ''); }},
            { data: 'status_badge' },
            { data: 'priority_badge' },
            { data: 'sla_deadline', render: function(d,t,r) { if (!d) return '-'; var h = d; if (r.sla_breached) h += '<br><span class="badge bg-danger">BREACHED</span>'; else if (r.sla_remaining) h += '<br><small>' + r.sla_remaining + '</small>'; return h; }},
            { data: 'created_at' },
            { data: null, className: 'text-center', orderable: false, render: function(d,t,r) { return '<a href="{{ url("technician/tickets") }}/' + r.id + '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>'; }}
        ],
        order: [[6, 'desc']], pageLength: 25
    });
    $('#filter_status, #filter_priority').on('change', function() { table.ajax.reload(); });
    $('#btn_reset_filters').on('click', function() { $('#filter_status, #filter_priority').val(''); table.ajax.reload(); });
});
</script>
@endpush
