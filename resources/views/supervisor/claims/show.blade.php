@extends('layouts.app')
@section('title', 'Claim – ' . $claim->claim_no)
@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1><i class="bi bi-receipt me-2 text-primary"></i>{{ $claim->claim_no }}</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('supervisor.claims.index') }}">Claims</a></li>
                <li class="breadcrumb-item">
                    @if($claim->claim_category === 'ticket')
                        <a href="{{ route('supervisor.claims.ticket-claims') }}">Ticket Claims</a>
                    @else
                        <a href="{{ route('supervisor.claims.other-claims') }}">Other Claims</a>
                    @endif
                </li>
                <li class="breadcrumb-item active">{{ $claim->claim_no }}</li>
            </ol>
        </div>
        <div class="d-flex gap-2 align-items-center">
            {!! \App\Models\Claim::getStatusBadge($claim->status) !!}
            <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">

        {{-- Claim Info --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-info-circle me-2"></i>Claim Information</span>
                <span class="badge {{ $claim->claim_category === 'ticket' ? 'bg-info' : 'bg-secondary' }}">
                    {{ ucfirst($claim->claim_category) }} Claim
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4"><div class="text-muted small mb-1">Claim No</div><div class="fw-semibold">{{ $claim->claim_no }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small mb-1">Claim Date</div><div>{{ $claim->claim_date ? $claim->claim_date->format('d/m/Y') : '-' }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small mb-1">Status</div><div>{!! \App\Models\Claim::getStatusBadge($claim->status) !!}</div></div>
                    @if($claim->claim_category === 'other')
                    <div class="col-sm-4"><div class="text-muted small mb-1">Claim Type</div><div>{{ $claim->claim_type_label ?? 'Other' }}</div></div>
                    @endif
                    <div class="col-sm-8"><div class="text-muted small mb-1">Description</div><div>{{ $claim->description ?? '-' }}</div></div>
                    @if($claim->remarks)<div class="col-12"><div class="text-muted small mb-1">Remarks</div><div class="text-muted">{{ $claim->remarks }}</div></div>@endif
                    @if($claim->admin_remarks)<div class="col-12"><div class="alert alert-info py-2 mb-0"><strong>Admin Remarks:</strong> {{ $claim->admin_remarks }}</div></div>@endif
                </div>
            </div>
        </div>

        {{-- Linked Ticket --}}
        @if($claim->claim_category === 'ticket' && $claim->ticket)
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-ticket-detailed me-2 text-info"></i>Linked Ticket
                <a href="{{ route('supervisor.tickets.show', $claim->ticket->id) }}" class="btn btn-sm btn-outline-info ms-2" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-1"></i>View Ticket
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4"><div class="text-muted small mb-1">Ticket No</div><div class="fw-semibold text-info">{{ $claim->ticket->ticket_no }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small mb-1">Vendor</div><div>{{ $claim->ticket->vendor->company_name ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small mb-1">Merchant</div><div>{{ $claim->ticket->merchant_name ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small mb-1">Job Category</div><div>{{ $claim->ticket->jobCategory->name ?? '-' }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small mb-1">Job Type</div><div>{{ $claim->ticket->jobType->name ?? '-' }}</div></div>
                    <div class="col-sm-4">
                        <div class="text-muted small mb-1">Ticket Status</div>
                        @php $ts = $claim->ticket->status; @endphp
                        <span class="badge bg-{{ in_array($ts,['done_success','closed']) ? 'success' : (in_array($ts,['done_fail']) ? 'danger' : 'secondary') }}">
                            {{ ucwords(str_replace('_',' ',$ts)) }}
                        </span>
                    </div>
                    <div class="col-sm-4"><div class="text-muted small mb-1">Location</div><div>{{ $claim->ticket->state->name ?? '' }}{{ $claim->ticket->city ? ', '.$claim->ticket->city->name : '' }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small mb-1">Completed At</div><div>{{ $claim->ticket->completed_at ? $claim->ticket->completed_at->format('d/m/Y H:i') : '-' }}</div></div>
                </div>
            </div>
        </div>
        @elseif($claim->claim_category === 'ticket')
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Linked ticket not found.</div>
        @endif

        {{-- Financial Breakdown --}}
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-calculator me-2 text-success"></i>Financial Breakdown</div>
            <div class="card-body">
                @if($claim->claim_category === 'ticket')
                <div class="row g-2 mb-3">
                    <div class="col-sm-4"><div class="text-muted small">Mileage</div><div>{{ number_format($claim->total_mileage_km, 2) }} km</div></div>
                    <div class="col-sm-4"><div class="text-muted small">Mileage Amount</div><div>RM {{ number_format($claim->total_mileage_amount, 2) }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">Allowances</div><div>RM {{ number_format($claim->total_allowance_amount, 2) }}</div></div>
                </div>
                @endif
                <div class="d-flex align-items-center justify-content-between bg-light rounded p-3">
                    <span class="fw-semibold">Total Claim Amount</span>
                    <span class="fw-bold fs-5 text-success">RM {{ number_format($claim->total_amount, 2) }}</span>
                </div>
                @if($claim->original_amount && $claim->original_amount != $claim->total_amount)
                <div class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>Original: RM {{ number_format($claim->original_amount, 2) }}</div>
                @endif
            </div>
        </div>

        {{-- Attachments --}}
        @if($claim->attachments->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-paperclip me-2"></i>Attachments ({{ $claim->attachments->count() }})</div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($claim->attachments as $att)
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center border rounded p-2">
                            <i class="bi bi-file-earmark text-primary me-2 fs-5"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="text-truncate small fw-semibold">{{ $att->file_name }}</div>
                                <div class="text-muted" style="font-size:.75rem">{{ $att->created_at ? $att->created_at->format('d/m/Y') : '' }}</div>
                            </div>
                            <a href="{{ asset('storage/'.$att->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2"><i class="bi bi-eye"></i></a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-people me-2"></i>People</div>
            <div class="card-body">
                <div class="mb-3"><div class="text-muted small mb-1">Claimant</div><div class="fw-semibold">{{ $claim->technician->name ?? '-' }}</div></div>
                <div class="mb-3">
                    <div class="text-muted small mb-1">Submitted By</div>
                    <div>{{ $claim->submitter->name ?? '-' }}</div>
                    @if($claim->submitted_at)<div class="text-muted" style="font-size:.8rem">{{ $claim->submitted_at->format('d/m/Y H:i') }}</div>@endif
                </div>
                @if($claim->verifier)
                <div class="mb-3">
                    <div class="text-muted small mb-1">Verified By</div>
                    <div>{{ $claim->verifier->name }}</div>
                    @if($claim->verified_at)<div class="text-muted" style="font-size:.8rem">{{ $claim->verified_at->format('d/m/Y H:i') }}</div>@endif
                </div>
                @endif
                @if($claim->payer)
                <div class="mb-3">
                    <div class="text-muted small mb-1">Paid By</div>
                    <div>{{ $claim->payer->name }}</div>
                    @if($claim->paid_at)<div class="text-muted" style="font-size:.8rem">{{ $claim->paid_at->format('d/m/Y H:i') }}</div>@endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
