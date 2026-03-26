@extends('layouts.app')
@section('title', 'Reports — TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-bar-chart-line me-2"></i>Reports</h4>
            <p class="text-muted mb-0">Select a report to view, filter, and export data.</p>
        </div>
    </div>

    <div class="row g-4">
        {{-- Ticket & Operations Reports --}}
        <div class="col-12">
            <h6 class="text-muted text-uppercase fw-bold mb-3"><i class="bi bi-ticket-detailed me-1"></i> Ticket & Operations</h6>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.reports.ticket-summary') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3"><i class="bi bi-clipboard-data text-primary" style="font-size: 2rem;"></i></div>
                        <h6 class="fw-bold">Ticket Summary</h6>
                        <small class="text-muted">Overview of all tickets and job distribution</small>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.reports.status') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3"><i class="bi bi-pie-chart text-info" style="font-size: 2rem;"></i></div>
                        <h6 class="fw-bold">Status Report</h6>
                        <small class="text-muted">Track ticket progress by status</small>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.reports.sla') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3"><i class="bi bi-clock-history text-warning" style="font-size: 2rem;"></i></div>
                        <h6 class="fw-bold">SLA Report</h6>
                        <small class="text-muted">Monitor SLA compliance and timelines</small>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.reports.rejected-rescheduled') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3"><i class="bi bi-x-octagon text-danger" style="font-size: 2rem;"></i></div>
                        <h6 class="fw-bold">Rejected / Rescheduled</h6>
                        <small class="text-muted">Track rejected and rescheduled tickets</small>
                    </div>
                </div>
            </a>
        </div>

        {{-- Financial Reports --}}
        <div class="col-12 mt-4">
            <h6 class="text-muted text-uppercase fw-bold mb-3"><i class="bi bi-currency-dollar me-1"></i> Financial</h6>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.reports.supervisor-pricing') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3"><i class="bi bi-tags text-success" style="font-size: 2rem;"></i></div>
                        <h6 class="fw-bold">Supervisor Pricing</h6>
                        <small class="text-muted">Pricing per supervisor, job type & category</small>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.reports.claim') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3"><i class="bi bi-receipt text-purple" style="font-size: 2rem;"></i></div>
                        <h6 class="fw-bold">Claim Report</h6>
                        <small class="text-muted">All claims by supervisors and technicians</small>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.reports.payment') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3"><i class="bi bi-wallet2 text-dark" style="font-size: 2rem;"></i></div>
                        <h6 class="fw-bold">Payment Report</h6>
                        <small class="text-muted">Payment processing and payout tracking</small>
                    </div>
                </div>
            </a>
        </div>

        {{-- Inventory Reports --}}
        <div class="col-12 mt-4">
            <h6 class="text-muted text-uppercase fw-bold mb-3"><i class="bi bi-box-seam me-1"></i> Inventory</h6>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.reports.inventory-balance') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3"><i class="bi bi-boxes text-secondary" style="font-size: 2rem;"></i></div>
                        <h6 class="fw-bold">Inventory Balance</h6>
                        <small class="text-muted">Current stock levels and low stock alerts</small>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.reports.router-movement') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3"><i class="bi bi-router text-info" style="font-size: 2rem;"></i></div>
                        <h6 class="fw-bold">Router Movement</h6>
                        <small class="text-muted">Track router movement by Terminal ID</small>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.reports.accessories-usage') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3"><i class="bi bi-sim text-warning" style="font-size: 2rem;"></i></div>
                        <h6 class="fw-bold">Accessories Usage</h6>
                        <small class="text-muted">Track SIM / Antenna usage and returns</small>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    .report-card { transition: all 0.2s ease; cursor: pointer; }
    .report-card:hover { transform: translateY(-4px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.12) !important; }
    .text-purple { color: #7c3aed; }
</style>
@endpush
