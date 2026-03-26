@extends('layouts.app')
@section('title', 'My Reports — TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-bar-chart-line me-2"></i>My Reports</h4>
            <p class="text-muted mb-0">View your personal performance reports.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4 col-lg-3">
            <a href="{{ route('technician.reports.ticket-summary') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card"><div class="card-body text-center py-4">
                    <div class="mb-3"><i class="bi bi-clipboard-data text-primary" style="font-size: 2rem;"></i></div>
                    <h6 class="fw-bold">My Ticket Summary</h6>
                    <small class="text-muted">Overview of your assigned tickets</small>
                </div></div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('technician.reports.claim') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card"><div class="card-body text-center py-4">
                    <div class="mb-3"><i class="bi bi-receipt text-success" style="font-size: 2rem;"></i></div>
                    <h6 class="fw-bold">My Claims</h6>
                    <small class="text-muted">Your claim submissions and status</small>
                </div></div>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    .report-card { transition: all 0.2s ease; cursor: pointer; }
    .report-card:hover { transform: translateY(-4px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.12) !important; }
</style>
@endpush
