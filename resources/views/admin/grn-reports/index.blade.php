@extends('layouts.app')

@section('title', 'GRN Reports - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">GRN Reports</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.grns.index') }}">GRNs</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.grns.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to GRN List
        </a>
    </div>

    <!-- Report Cards -->
    <div class="row g-4">
        <!-- GRN Register -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-journal-text text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="card-title">GRN Register</h5>
                    <p class="text-muted mb-3">
                        Detailed list of all GRNs with line items, serial numbers, PO references, and posting status. Filter by date, vendor, depot, and status.
                    </p>
                    <a href="{{ route('admin.grn-reports.register') }}" class="btn btn-primary">
                        <i class="bi bi-eye me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Receiving Summary by Vendor -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-building text-success" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="card-title">Receiving by Vendor</h5>
                    <p class="text-muted mb-3">
                        Summary of goods received grouped by vendor, showing GRN count, quantities received, and total values per vendor.
                    </p>
                    <a href="{{ route('admin.grn-reports.receiving-by-vendor') }}" class="btn btn-success">
                        <i class="bi bi-eye me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Receiving Summary by Model -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-cpu text-info" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="card-title">Receiving by Model</h5>
                    <p class="text-muted mb-3">
                        Summary of goods received grouped by terminal model and category, with average costs and total quantities.
                    </p>
                    <a href="{{ route('admin.grn-reports.receiving-by-model') }}" class="btn btn-info text-white">
                        <i class="bi bi-eye me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
