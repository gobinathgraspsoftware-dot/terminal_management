@extends('layouts.app')
@section('title', 'Claim Management')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Claim Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Claims</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.claims.export', ['category' => 'all']) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download me-1"></i> Export All
            </a>
            <a href="{{ route('admin.claims.create-other-claim') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> New Other Claim
            </a>
        </div>
    </div>
</div>

{{-- Stats Row --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stats-card text-center h-100">
            <div class="stats-icon bg-info bg-opacity-10 text-info mx-auto mb-2"><i class="bi bi-ticket-detailed fs-4"></i></div>
            <div class="stats-value text-info">{{ $stats['ticket_submitted'] }}</div>
            <div class="stats-label">Ticket Submitted</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stats-card text-center h-100">
            <div class="stats-icon bg-success bg-opacity-10 text-success mx-auto mb-2"><i class="bi bi-check-circle fs-4"></i></div>
            <div class="stats-value text-success">{{ $stats['ticket_verified'] }}</div>
            <div class="stats-label">Ticket Verified</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stats-card text-center h-100">
            <div class="stats-icon bg-secondary bg-opacity-10 text-secondary mx-auto mb-2"><i class="bi bi-file-text fs-4"></i></div>
            <div class="stats-value text-secondary">{{ $stats['other_submitted'] }}</div>
            <div class="stats-label">Other Submitted</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stats-card text-center h-100">
            <div class="stats-icon bg-primary bg-opacity-10 text-primary mx-auto mb-2"><i class="bi bi-patch-check fs-4"></i></div>
            <div class="stats-value text-primary">{{ $stats['other_verified'] }}</div>
            <div class="stats-label">Other Verified</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stats-card text-center h-100">
            <div class="stats-icon bg-warning bg-opacity-10 text-warning mx-auto mb-2"><i class="bi bi-hourglass-split fs-4"></i></div>
            <div class="stats-value text-warning">{{ $stats['pending_payment'] }}</div>
            <div class="stats-label">Pending Payment</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stats-card text-center h-100">
            <div class="stats-icon bg-success bg-opacity-10 text-success mx-auto mb-2"><i class="bi bi-cash-stack fs-4"></i></div>
            <div class="stats-value text-success">{{ $stats['paid_this_month'] }}</div>
            <div class="stats-label">Paid This Month</div>
        </div>
    </div>
</div>

{{-- Module Navigation Cards --}}
<div class="row g-4">
    <div class="col-md-4">
        <a href="{{ route('admin.claims.ticket-claims') }}" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                        <i class="bi bi-ticket-detailed fs-2 text-info"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 text-dark">Ticket Claims</h5>
                        <p class="text-muted mb-0 small">Auto-created from completed tickets</p>
                        <span class="badge bg-info mt-1">{{ $stats['ticket_submitted'] }} awaiting</span>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('admin.claims.other-claims') }}" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-file-earmark-text fs-2 text-secondary"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 text-dark">Other Claims</h5>
                        <p class="text-muted mb-0 small">Mileage, toll, transport & misc</p>
                        <span class="badge bg-secondary mt-1">{{ $stats['other_submitted'] }} awaiting</span>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('admin.claims.bulk-payment') }}" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                        <i class="bi bi-cash-coin fs-2 text-warning"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 text-dark">Bulk Payment</h5>
                        <p class="text-muted mb-0 small">Process verified claims for payment</p>
                        <span class="badge bg-warning text-dark mt-1">{{ $stats['pending_payment'] }} pending</span>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('admin.claims.payment-history') }}" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-clock-history fs-2 text-success"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 text-dark">Payment History</h5>
                        <p class="text-muted mb-0 small">View all paid claims history</p>
                        <span class="badge bg-success mt-1">{{ $stats['paid_this_month'] }} paid this month</span>
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
