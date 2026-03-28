@extends('layouts.app')
@section('title', 'My Claims')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Claims</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Claims</li>
            </ol>
        </div>
        @if(!$isInternal)
        <a href="{{ route('supervisor.claims.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>New Other Claim
        </a>
        @endif
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stats-card text-center">
            <div class="stats-icon bg-info bg-opacity-10 text-info mx-auto mb-2"><i class="bi bi-ticket-detailed fs-4"></i></div>
            <div class="stats-value text-info">{{ $stats['ticket_total'] }}</div>
            <div class="stats-label">Ticket Claims</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stats-card text-center">
            <div class="stats-icon bg-secondary bg-opacity-10 text-secondary mx-auto mb-2"><i class="bi bi-file-text fs-4"></i></div>
            <div class="stats-value text-secondary">{{ $stats['other_total'] }}</div>
            <div class="stats-label">Other Claims</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stats-card text-center">
            <div class="stats-icon bg-warning bg-opacity-10 text-warning mx-auto mb-2"><i class="bi bi-hourglass-split fs-4"></i></div>
            <div class="stats-value text-warning">{{ $stats['ticket_submitted'] + $stats['other_submitted'] }}</div>
            <div class="stats-label">Awaiting Review</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stats-card text-center">
            <div class="stats-icon bg-success bg-opacity-10 text-success mx-auto mb-2"><i class="bi bi-cash-stack fs-4"></i></div>
            <div class="stats-value text-success">{{ $stats['paid_total'] }}</div>
            <div class="stats-label">Paid</div>
        </div>
    </div>
</div>

{{-- Navigation Cards --}}
<div class="row g-4">
    <div class="col-md-4">
        <a href="{{ route('supervisor.claims.ticket-claims') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm hover-lift">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                        <i class="bi bi-ticket-detailed fs-2 text-info"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 text-dark">Ticket Claims</h5>
                        <p class="text-muted mb-0 small">Auto-created when tickets complete</p>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('supervisor.claims.other-claims') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm hover-lift">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-file-earmark-text fs-2 text-secondary"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 text-dark">Other Claims</h5>
                        <p class="text-muted mb-0 small">Transport, toll & miscellaneous</p>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('supervisor.claims.payment-history') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm hover-lift">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-clock-history fs-2 text-success"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 text-dark">Payment History</h5>
                        <p class="text-muted mb-0 small">View all paid claims</p>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection
@push('styles')
<style>
.hover-lift { transition: transform 0.15s ease, box-shadow 0.15s ease; }
.hover-lift:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important; }
</style>
@endpush
