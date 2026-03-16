@php use App\Models\Claim; @endphp
@extends('layouts.app')

@section('title', 'Claim Detail - ' . $claim->claim_no)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Claim Detail</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">{{ $claim->claim_no }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('supervisor.claims.index', ['tab' => $claim->claim_category === 'ticket' ? 'ticket' : 'other']) }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Claims
        </a>
    </div>

    <div class="row">
        {{-- Main Info --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bi bi-file-earmark-text me-1"></i> {{ $claim->claim_no }}
                        @if($claim->claim_category === 'ticket')
                            <span class="badge bg-info ms-2">Ticket Claim</span>
                        @else
                            <span class="badge bg-secondary ms-2">Other Claim</span>
                        @endif
                    </h6>
                    {!! Claim::getStatusBadge($claim->status) !!}
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Claim No</small>
                            <strong>{{ $claim->claim_no }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Claim Date</small>
                            <strong>{{ $claim->claim_date ? \Carbon\Carbon::parse($claim->claim_date)->format('d/m/Y') : '-' }}</strong>
                        </div>

                        @if($claim->claim_category === 'ticket' && $claim->ticket)
                        <div class="col-md-6">
                            <small class="text-muted d-block">Ticket No</small>
                            <strong>{{ $claim->ticket->ticket_no ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Vendor</small>
                            <strong>{{ $claim->ticket->vendor->company_name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Merchant</small>
                            <strong>{{ $claim->ticket->merchant_name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Job Type</small>
                            <strong>{{ $claim->ticket->jobType->name ?? '-' }}</strong>
                        </div>
                        @endif

                        @if($claim->claim_category === 'other')
                        <div class="col-md-6">
                            <small class="text-muted d-block">Claim Type</small>
                            <strong>{{ $claim->claim_type_label ?? 'Other' }}</strong>
                        </div>
                        @if($claim->ticket)
                        <div class="col-md-6">
                            <small class="text-muted d-block">Linked Ticket</small>
                            <strong>{{ $claim->ticket->ticket_no ?? '-' }}</strong>
                        </div>
                        @endif
                        @endif

                        <div class="col-md-6">
                            <small class="text-muted d-block">Technician</small>
                            <strong>{{ $claim->technician->name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Submitted By</small>
                            <strong>{{ $claim->submitter->name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Submitted At</small>
                            <strong>{{ $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-' }}</strong>
                        </div>

                        <div class="col-12">
                            <small class="text-muted d-block">Description</small>
                            <strong>{{ $claim->description ?? '-' }}</strong>
                        </div>

                        @if($claim->remarks)
                        <div class="col-12">
                            <small class="text-muted d-block">Remarks</small>
                            <strong>{{ $claim->remarks }}</strong>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Amounts --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-calculator me-1"></i> Claim Amounts</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($claim->claim_category === 'ticket')
                        <div class="col-md-4">
                            <small class="text-muted d-block">Mileage (KM)</small>
                            <strong>{{ number_format((float) $claim->total_mileage_km, 2) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Mileage Amount (RM)</small>
                            <strong>{{ number_format((float) $claim->total_mileage_amount, 2) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Allowance (RM)</small>
                            <strong>{{ number_format((float) $claim->total_allowance_amount, 2) }}</strong>
                        </div>
                        @endif
                        <div class="col-md-4">
                            <small class="text-muted d-block">Original Amount (RM)</small>
                            <strong>{{ number_format((float) $claim->original_amount, 2) }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block fw-bold text-primary">Total Amount (RM)</small>
                            <strong class="fs-5 text-primary">{{ number_format((float) $claim->total_amount, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Attachments --}}
            @if($claim->attachments && $claim->attachments->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-paperclip me-1"></i> Attachments ({{ $claim->attachments->count() }})</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($claim->attachments as $attachment)
                        <div class="col-md-6">
                            <div class="border rounded p-2 d-flex align-items-center">
                                @if(in_array(strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION)), ['png','jpg','jpeg']))
                                    <img src="{{ asset('storage/' . $attachment->file_path) }}" alt="{{ $attachment->file_name }}"
                                         class="me-2 rounded" style="width:50px;height:50px;object-fit:cover;">
                                @else
                                    <i class="bi bi-file-earmark-pdf text-danger fs-3 me-2"></i>
                                @endif
                                <div class="flex-grow-1 text-truncate">
                                    <small class="d-block text-truncate">{{ $attachment->file_name }}</small>
                                    <small class="text-muted">{{ $attachment->uploadedBy->name ?? '-' }}</small>
                                </div>
                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            @if($claim->verified_at)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-shield-check me-1"></i> Verification</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted d-block">Verified By</small>
                        <strong>{{ $claim->verifier->name ?? '-' }}</strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Verified At</small>
                        <strong>{{ \Carbon\Carbon::parse($claim->verified_at)->format('d/m/Y H:i') }}</strong>
                    </div>
                    @if($claim->admin_remarks)
                    <div>
                        <small class="text-muted d-block">Admin Remarks</small>
                        <strong>{{ $claim->admin_remarks }}</strong>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            @if($claim->paid_at)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-cash-coin me-1"></i> Payment</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted d-block">Paid By</small>
                        <strong>{{ $claim->payer->name ?? '-' }}</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Paid At</small>
                        <strong>{{ \Carbon\Carbon::parse($claim->paid_at)->format('d/m/Y H:i') }}</strong>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
