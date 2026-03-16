@extends('layouts.app')

@section('title', 'My Claims')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-file-earmark-text me-2"></i>My Claims</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Claims</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-start border-primary border-4 h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Ticket Claims</div>
                    <h3 class="mb-0">{{ $stats['ticket_total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-info border-4 h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Ticket Submitted</div>
                    <h3 class="mb-0">{{ $stats['ticket_submitted'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-warning border-4 h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Other Claims</div>
                    <h3 class="mb-0">{{ $stats['other_total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-success border-4 h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Other Submitted</div>
                    <h3 class="mb-0">{{ $stats['other_submitted'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'ticket' ? 'active' : '' }}" href="{{ route('technician.claims.index', ['tab' => 'ticket']) }}">
                <i class="bi bi-ticket-detailed me-1"></i> Ticket Claims
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'other' ? 'active' : '' }}" href="{{ route('technician.claims.index', ['tab' => 'other']) }}">
                <i class="bi bi-file-earmark-text me-1"></i> Other Claims
            </a>
        </li>
    </ul>

    {{-- Tab Content --}}
    @if($activeTab === 'ticket')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">My Ticket Claims</h5>
                <a href="{{ route('technician.claims.create-ticket-claim') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> New Ticket Claim
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="ticketClaimsTable" class="table table-striped table-hover" style="width:100%">
                        <thead>
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
        </div>
    @else
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">My Other Claims</h5>
                <a href="{{ route('technician.claims.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> New Other Claim
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="otherClaimsTable" class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Claim No</th>
                                <th>Type</th>
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
    @endif
</div>
@endsection

@push('scripts')
<script>
$(function() {
    @if($activeTab === 'ticket')
    $('#ticketClaimsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("technician.claims.ticket-claims-data") }}',
        columns: [
            { data: 'claim_no' },
            { data: 'ticket_no' },
            { data: 'vendor' },
            { data: 'merchant_name' },
            { data: 'total_amount', className: 'text-end' },
            { data: 'status', orderable: false },
            { data: 'submitted_at' },
            { data: 'actions', orderable: false, searchable: false },
        ],
        order: [[6, 'desc']],
        responsive: true,
    });
    @else
    $('#otherClaimsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("technician.claims.other-claims-data") }}',
        columns: [
            { data: 'claim_no' },
            { data: 'claim_type' },
            { data: 'description' },
            { data: 'total_amount', className: 'text-end' },
            { data: 'status', orderable: false },
            { data: 'submitted_at' },
            { data: 'has_attachments', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false },
        ],
        order: [[5, 'desc']],
        responsive: true,
    });
    @endif
});
</script>
@endpush
