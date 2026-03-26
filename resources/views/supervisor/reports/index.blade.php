@extends('layouts.app')
@section('title', 'Reports — TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-bar-chart-line me-2"></i>Reports</h4>
            <p class="text-muted mb-0">View your team's performance reports.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <h6 class="text-muted text-uppercase fw-bold mb-3"><i class="bi bi-ticket-detailed me-1"></i> Ticket & Operations</h6>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('supervisor.reports.ticket-summary') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card"><div class="card-body text-center py-4">
                    <div class="mb-3"><i class="bi bi-clipboard-data text-primary" style="font-size: 2rem;"></i></div>
                    <h6 class="fw-bold">Ticket Summary</h6>
                    <small class="text-muted">Team ticket overview</small>
                </div></div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('supervisor.reports.status') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card"><div class="card-body text-center py-4">
                    <div class="mb-3"><i class="bi bi-pie-chart text-info" style="font-size: 2rem;"></i></div>
                    <h6 class="fw-bold">Status Report</h6>
                    <small class="text-muted">Track ticket progress</small>
                </div></div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('supervisor.reports.sla') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card"><div class="card-body text-center py-4">
                    <div class="mb-3"><i class="bi bi-clock-history text-warning" style="font-size: 2rem;"></i></div>
                    <h6 class="fw-bold">SLA Report</h6>
                    <small class="text-muted">SLA compliance monitoring</small>
                </div></div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('supervisor.reports.rejected-rescheduled') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card"><div class="card-body text-center py-4">
                    <div class="mb-3"><i class="bi bi-x-octagon text-danger" style="font-size: 2rem;"></i></div>
                    <h6 class="fw-bold">Rejected / Rescheduled</h6>
                    <small class="text-muted">Track rejected tickets</small>
                </div></div>
            </a>
        </div>

        <div class="col-12 mt-4">
            <h6 class="text-muted text-uppercase fw-bold mb-3"><i class="bi bi-currency-dollar me-1"></i> Financial</h6>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('supervisor.reports.claim') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card"><div class="card-body text-center py-4">
                    <div class="mb-3"><i class="bi bi-receipt text-success" style="font-size: 2rem;"></i></div>
                    <h6 class="fw-bold">Claim Report</h6>
                    <small class="text-muted">Team claims overview</small>
                </div></div>
            </a>
        </div>

        <div class="col-12 mt-4">
            <h6 class="text-muted text-uppercase fw-bold mb-3"><i class="bi bi-box-seam me-1"></i> Inventory</h6>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('supervisor.reports.inventory-balance') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card"><div class="card-body text-center py-4">
                    <div class="mb-3"><i class="bi bi-boxes text-secondary" style="font-size: 2rem;"></i></div>
                    <h6 class="fw-bold">Inventory Balance</h6>
                    <small class="text-muted">Stock levels</small>
                </div></div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('supervisor.reports.router-movement') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card"><div class="card-body text-center py-4">
                    <div class="mb-3"><i class="bi bi-router text-info" style="font-size: 2rem;"></i></div>
                    <h6 class="fw-bold">Router Movement</h6>
                    <small class="text-muted">Track routers</small>
                </div></div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('supervisor.reports.accessories-usage') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card"><div class="card-body text-center py-4">
                    <div class="mb-3"><i class="bi bi-sim text-warning" style="font-size: 2rem;"></i></div>
                    <h6 class="fw-bold">Accessories Usage</h6>
                    <small class="text-muted">SIM / Antenna usage</small>
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
