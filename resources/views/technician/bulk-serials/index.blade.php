@extends('layouts.app')

@section('title', 'My Inventory')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-box-seam"></i> My Inventory</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Inventory</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Serials</h6>
                            <h3 class="mb-0">{{ number_format($statistics['total_serials']) }}</h3>
                        </div>
                        <i class="bi bi-box-seam" style="font-size: 2rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Issued to Me</h6>
                            <h3 class="mb-0">{{ number_format($statistics['issued']) }}</h3>
                        </div>
                        <i class="bi bi-person-badge" style="font-size: 2rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Reserved</h6>
                            <h3 class="mb-0">{{ number_format($statistics['reserved']) }}</h3>
                        </div>
                        <i class="bi bi-bookmark-star" style="font-size: 2rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Actions -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-printer text-info" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title">Print Labels</h5>
                    <p class="card-text text-muted">
                        Select serials from your inventory and print labels
                    </p>
                    <a href="{{ route('technician.inventory-serials.index') }}" class="btn btn-info">
                        <i class="bi bi-list-ul"></i> View My Inventory
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-file-earmark-excel text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title">Export Serials</h5>
                    <p class="card-text text-muted">
                        Export your assigned serials to Excel file
                    </p>
                    <a href="{{ route('technician.inventory-serials.index') }}" class="btn btn-success">
                        <i class="bi bi-list-ul"></i> View My Inventory
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Information Card -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Information</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <h6><strong>What you can do:</strong></h6>
                        <ul class="mb-3">
                            <li>View all serials currently assigned to you</li>
                            <li>Print labels for your assigned serials</li>
                            <li>Export your inventory list to Excel</li>
                            <li>View movement history of your serials</li>
                        </ul>

                        <h6><strong>Need to perform bulk operations?</strong></h6>
                        <p class="mb-0">
                            For bulk status updates, transfers, or other bulk operations, please contact your supervisor.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
