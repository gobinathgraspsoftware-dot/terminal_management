@extends('layouts.app')

@section('title', 'Claim Detail - ' . $claim->claim_no)

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ $claim->claim_no }}
                @if($claim->claim_category === 'ticket')
                    <span class="badge bg-primary">Ticket Claim</span>
                @else
                    <span class="badge bg-info">Other Claim</span>
                @endif
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">{{ $claim->claim_no }}</li>
                </ol>
            </nav>
        </div>
        <div>{!! \App\Models\Claim::getStatusBadge($claim->status) !!}</div>
    </div>

    <div class="row g-4">
        {{-- Left Column: Claim Info --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Claim Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small">Claim No</div>
                            <div class="fw-bold">{{ $claim->claim_no }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Category</div>
                            <div>{!! $claim->claim_category === 'ticket' ? '<span class="badge bg-primary">Ticket Claim</span>' : '<span class="badge bg-info">Other Claim</span>' !!}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Submitted</div>
                            <div>{{ $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-' }}</div>
                        </div>

                        @if($claim->claim_category === 'other')
                        <div class="col-md-4">
                            <div class="text-muted small">Claim Type</div>
                            <div class="fw-bold">{{ $claim->claim_type_label ?? 'Others' }}</div>
                        </div>
                        @endif

                        <div class="col-md-4">
                            <div class="text-muted small">Submitted By</div>
                            <div>{{ $claim->submitter->name ?? '-' }}</div>
                        </div>

                        @if($claim->ticket)
                        <div class="col-md-4">
                            <div class="text-muted small">Linked Ticket</div>
                            <div>{{ $claim->ticket->ticket_no ?? '-' }}</div>
                        </div>
                        @endif

                        <div class="col-md-12">
                            <div class="text-muted small">Description</div>
                            <div>{{ $claim->description ?? '-' }}</div>
                        </div>

                        @if($claim->remarks)
                        <div class="col-md-12">
                            <div class="text-muted small">Remarks</div>
                            <div>{{ $claim->remarks }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Claim Amounts --}}
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-calculator me-2"></i>Claim Amounts</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small">Original Amount (RM)</div>
                            <div>{{ number_format((float)$claim->original_amount, 2) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small fw-bold">Total Amount (RM)</div>
                            <div class="fs-5 fw-bold text-success">{{ number_format((float)$claim->total_amount, 2) }}</div>
                        </div>
                        @if($claim->claim_category === 'ticket')
                        <div class="col-md-4">
                            <div class="text-muted small">Mileage</div>
                            <div>{{ number_format((float)$claim->total_mileage_km, 2) }} KM (RM {{ number_format((float)$claim->total_mileage_amount, 2) }})</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Attachments --}}
            @if($claim->attachments->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-paperclip me-2"></i>Attachments</h5></div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach($claim->attachments as $att)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-earmark me-1"></i> {{ $att->file_name }}
                                <small class="text-muted ms-2">{{ $att->uploadedBy->name ?? '' }}</small>
                            </div>
                            <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Right Column: Status --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Claim Status</h5></div>
                <div class="card-body text-center">
                    <div class="mb-3">{!! \App\Models\Claim::getStatusBadge($claim->status) !!}</div>

                    @if($claim->verifier)
                    <div class="text-start mt-3">
                        <div class="text-muted small">Verified By</div>
                        <div class="fw-bold">{{ $claim->verifier->name }}</div>
                        <div class="text-muted small mt-1">Verified At</div>
                        <div>{{ $claim->verified_at ? $claim->verified_at->format('d/m/Y H:i') : '-' }}</div>
                    </div>
                    @endif

                    @if($claim->payer)
                    <div class="text-start mt-3">
                        <div class="text-muted small">Paid By</div>
                        <div class="fw-bold">{{ $claim->payer->name }}</div>
                        <div class="text-muted small mt-1">Paid At</div>
                        <div>{{ $claim->paid_at ? $claim->paid_at->format('d/m/Y H:i') : '-' }}</div>
                    </div>
                    @endif

                    @if($claim->admin_remarks)
                    <div class="text-start mt-3">
                        <div class="text-muted small">Admin Remarks</div>
                        <div class="text-danger">{{ $claim->admin_remarks }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <a href="{{ route('technician.claims.index') }}" class="btn btn-secondary w-100">
                <i class="bi bi-arrow-left me-1"></i> Back to Claims
            </a>
        </div>
    </div>
</div>
@endsection
