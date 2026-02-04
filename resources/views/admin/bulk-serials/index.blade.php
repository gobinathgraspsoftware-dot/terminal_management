@extends('layouts.app')

@section('title', 'Bulk Serial Operations')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-boxes"></i> Bulk Serial Operations</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.inventory-serials.index') }}">Inventory</a></li>
                        <li class="breadcrumb-item active">Bulk Operations</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
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
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">In Stock</h6>
                            <h3 class="mb-0">{{ number_format($statistics['in_stock']) }}</h3>
                        </div>
                        <i class="bi bi-archive" style="font-size: 2rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Issued to Tech</h6>
                            <h3 class="mb-0">{{ number_format($statistics['issued']) }}</h3>
                        </div>
                        <i class="bi bi-person-badge" style="font-size: 2rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Installed</h6>
                            <h3 class="mb-0">{{ number_format($statistics['installed']) }}</h3>
                        </div>
                        <i class="bi bi-laptop" style="font-size: 2rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Operations Cards -->
    <div class="row">
        <!-- Import Serials -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-upload text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title">Import Serials</h5>
                    <p class="card-text text-muted">
                        Import multiple serial numbers from Excel file
                    </p>
                    <a href="{{ route('admin.bulk-serials.import-form') }}" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Start Import
                    </a>
                </div>
            </div>
        </div>

        <!-- Bulk Update Status -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-arrow-repeat text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title">Bulk Update</h5>
                    <p class="card-text text-muted">
                        Update status for multiple serials at once
                    </p>
                    <a href="{{ route('admin.inventory-serials.index') }}?bulk_action=update" class="btn btn-success">
                        <i class="bi bi-arrow-repeat"></i> Bulk Update
                    </a>
                </div>
            </div>
        </div>

        <!-- Bulk Transfer -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-shuffle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title">Bulk Transfer</h5>
                    <p class="card-text text-muted">
                        Transfer multiple serials between locations
                    </p>
                    <a href="{{ route('admin.inventory-serials.index') }}?bulk_action=transfer" class="btn btn-warning">
                        <i class="bi bi-shuffle"></i> Bulk Transfer
                    </a>
                </div>
            </div>
        </div>

        <!-- Print Labels -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-printer text-info" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title">Print Labels</h5>
                    <p class="card-text text-muted">
                        Generate and print serial number labels
                    </p>
                    <a href="{{ route('admin.inventory-serials.index') }}?bulk_action=labels" class="btn btn-info">
                        <i class="bi bi-printer"></i> Print Labels
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <a href="{{ route('admin.bulk-serials.download-template') }}" class="btn btn-outline-primary btn-block mb-2">
                                <i class="bi bi-download"></i> Download Import Template
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.inventory-serials.export') }}" class="btn btn-outline-success btn-block mb-2">
                                <i class="bi bi-file-earmark-excel"></i> Export All Serials
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.inventory-serials.index') }}" class="btn btn-outline-secondary btn-block mb-2">
                                <i class="bi bi-list-ul"></i> View All Serials
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Instructions</h5>
                </div>
                <div class="card-body">
                    <h6>Import Serials:</h6>
                    <ol>
                        <li>Download the import template</li>
                        <li>Fill in the serial numbers and details</li>
                        <li>Upload the Excel file</li>
                        <li>Review and confirm the import</li>
                    </ol>

                    <h6 class="mt-3">Bulk Update/Transfer:</h6>
                    <ol>
                        <li>Go to the Inventory Serials list</li>
                        <li>Select the serials you want to update/transfer</li>
                        <li>Choose the bulk action from the dropdown</li>
                        <li>Preview changes before confirming</li>
                    </ol>

                    <h6 class="mt-3">Print Labels:</h6>
                    <ol>
                        <li>Select serials from the inventory list</li>
                        <li>Choose "Print Labels" from bulk actions</li>
                        <li>Configure label settings (size, layout)</li>
                        <li>Download or print the PDF</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow {
    transition: box-shadow 0.3s ease-in-out;
}

.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>
@endsection
