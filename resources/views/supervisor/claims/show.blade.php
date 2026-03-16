@php use App\Models\Claim; @endphp
@extends('layouts.app')

@section('title', 'Claim Detail - ' . $claim->claim_no)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Claim Detail: {{ $claim->claim_no }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.claims.index') }}">Claims</a></li>
                    <li class="breadcrumb-item active">{{ $claim->claim_no }}</li>
                </ol>
            </nav>
        </div>
        <div>{!! Claim::getStatusBadge($claim->status) !!}</div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Claim Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-1"></i> Claim Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Claim No</small>
                            <strong>{{ $claim->claim_no }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Category</small>
                            @if($claim->claim_category === 'ticket')
                                <span class="badge bg-info">Ticket Claim</span>
                            @else
                                <span class="badge bg-secondary">Other Claim</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Submitted</small>
                            <strong>{{ $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-' }}</strong>
                        </div>

                        @if($claim->ticket)
                        <div class="col-md-4">
                            <small class="text-muted d-block">Ticket No</small>
                            <strong>{{ $claim->ticket->ticket_no }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Vendor</small>
                            <strong>{{ $claim->ticket->vendor->company_name ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Merchant</small>
                            <strong>{{ $claim->ticket->merchant_name ?? '-' }}</strong>
                        </div>
                        @endif

                        @if($claim->claim_category === 'other')
                        <div class="col-md-4">
                            <small class="text-muted d-block">Claim Type</small>
                            <strong>{{ $claim->claim_type_label ?? 'Others' }}</strong>
                        </div>
                        <div class="col-md-8">
                            <small class="text-muted d-block">Description</small>
                            <strong>{{ $claim->description }}</strong>
                        </div>
                        @endif

                        <div class="col-md-4">
                            <small class="text-muted d-block">Technician</small>
                            <strong>{{ $claim->technician->name ?? '-' }}</strong>
                        </div>
                    </div>

                    @if($claim->claim_category === 'ticket')
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <tr><td class="bg-light fw-semibold" style="width:40%;">Mileage (KM)</td><td>{{ $claim->total_mileage_km ?? '0.00' }}</td></tr>
                            <tr><td class="bg-light fw-semibold">Mileage Amount (RM)</td><td>{{ number_format((float)($claim->total_mileage_amount ?? 0), 2) }}</td></tr>
                            <tr><td class="bg-light fw-semibold">Allowance (RM)</td><td>{{ number_format((float)($claim->total_allowance_amount ?? 0), 2) }}</td></tr>
                            <tr class="table-primary"><td class="fw-bold">Total (RM)</td><td class="fw-bold">{{ number_format((float)$claim->total_amount, 2) }}</td></tr>
                            @if($claim->original_amount && $claim->original_amount != $claim->total_amount)
                            <tr class="table-warning"><td class="fw-semibold">Original Amount (RM)</td><td><s>{{ number_format((float)$claim->original_amount, 2) }}</s> <small class="text-muted">(adjusted by admin)</small></td></tr>
                            @endif
                        </table>
                    </div>
                    @endif

                    @if($claim->remarks)
                    <hr>
                    <small class="text-muted d-block">My Remarks</small>
                    <p class="mb-0">{{ $claim->remarks }}</p>
                    @endif

                    @if($claim->admin_remarks)
                    <hr>
                    <small class="text-muted d-block">Admin Remarks</small>
                    <p class="mb-0 text-danger">{{ $claim->admin_remarks }}</p>
                    @endif
                </div>
            </div>

            <!-- Attachments -->
            @if($claim->attachments->isNotEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-paperclip me-1"></i> Attachments</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($claim->attachments as $attachment)
                        <div class="col-md-4">
                            <div class="border rounded p-2 text-center">
                                @if(in_array($attachment->mime_type, ['image/png', 'image/jpeg', 'image/jpg']))
                                    <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $attachment->file_path) }}" class="img-fluid rounded mb-2" style="max-height: 150px;" alt="Proof">
                                    </a>
                                @else
                                    <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> {{ $attachment->file_name }}
                                    </a>
                                @endif
                                <div class="small text-muted mt-1">{{ $attachment->uploadedBy->name ?? 'Unknown' }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-1"></i> Status</h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">{!! Claim::getStatusBadge($claim->status) !!}</div>
                    <div class="fs-4 fw-bold text-primary">RM {{ number_format((float)$claim->total_amount, 2) }}</div>
                    <small class="text-muted">Claim Amount</small>
                    @if($claim->verified_at)
                    <hr>
                    <small class="text-muted d-block">Verified on {{ $claim->verified_at->format('d/m/Y H:i') }}</small>
                    <small class="text-muted">by {{ $claim->verifier->name ?? '-' }}</small>
                    @endif
                    @if($claim->paid_at)
                    <hr>
                    <small class="text-muted d-block">Paid on {{ $claim->paid_at->format('d/m/Y H:i') }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
