@extends('layouts.app')

@section('title', 'Claim Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Claim Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Claim Management</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('bulk_pay_claims')
            <a href="{{ route('admin.claims.bulk-payment') }}" class="btn btn-success">
                <i class="bi bi-cash-stack me-1"></i> Bulk Payment
            </a>
            @endcan
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-info fs-2 fw-bold">{{ $stats['ticket_submitted'] + $stats['other_submitted'] }}</div>
                    <small class="text-muted">Pending Review</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-success fs-2 fw-bold">{{ $stats['ticket_verified'] + $stats['other_verified'] }}</div>
                    <small class="text-muted">Verified</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-warning fs-2 fw-bold">{{ $stats['pending_payment'] }}</div>
                    <small class="text-muted">Pending Payment</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary fs-2 fw-bold">{{ $stats['ticket_submitted'] + $stats['ticket_verified'] + $stats['other_submitted'] + $stats['other_verified'] }}</div>
                    <small class="text-muted">Total Active</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Cards -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-5">
                    <i class="bi bi-ticket-detailed text-primary" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Ticket Claims</h5>
                    <p class="text-muted">Claims generated from completed tickets</p>
                    <div class="d-flex justify-content-center gap-3 mb-3">
                        <span class="badge bg-info">{{ $stats['ticket_submitted'] }} Submitted</span>
                        <span class="badge bg-success">{{ $stats['ticket_verified'] }} Verified</span>
                    </div>
                    <a href="{{ route('admin.claims.ticket-claims') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-right me-1"></i> Open Ticket Claims
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-5">
                    <i class="bi bi-file-earmark-text text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Other Claims</h5>
                    <p class="text-muted">Manually submitted non-ticket claims</p>
                    <div class="d-flex justify-content-center gap-3 mb-3">
                        <span class="badge bg-info">{{ $stats['other_submitted'] }} Submitted</span>
                        <span class="badge bg-success">{{ $stats['other_verified'] }} Verified</span>
                    </div>
                    <a href="{{ route('admin.claims.other-claims') }}" class="btn btn-success">
                        <i class="bi bi-arrow-right me-1"></i> Open Other Claims
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
