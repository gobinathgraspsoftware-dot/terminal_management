@extends('layouts.app')

@section('title', 'Stock Reports')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Stock Reports</h1>
                    <p class="text-muted">Comprehensive stock movement and inventory reports</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Cards -->
    <div class="row g-4">
        <!-- Movement Report Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                <i class="bi bi-arrow-left-right fs-2"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="card-title mb-0">Movement Report</h5>
                            <p class="text-muted small mb-0">Detailed stock movements</p>
                        </div>
                    </div>
                    <p class="card-text">
                        View all stock movements with comprehensive filtering by date, location, model, and transaction type.
                    </p>
                    <a href="{{ route('admin.stock-reports.movement') }}" class="btn btn-primary w-100">
                        <i class="bi bi-bar-chart me-2"></i>View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Stock Card Report -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                                <i class="bi bi-card-list fs-2"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="card-title mb-0">Stock Card</h5>
                            <p class="text-muted small mb-0">Serial-level tracking</p>
                        </div>
                    </div>
                    <p class="card-text">
                        Track complete movement history for individual serial numbers with running balance and detailed timeline.
                    </p>
                    <a href="{{ route('admin.stock-reports.stock-card') }}" class="btn btn-success w-100">
                        <i class="bi bi-card-text me-2"></i>View Stock Cards
                    </a>
                </div>
            </div>
        </div>

        <!-- Summary Report -->
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 text-info rounded-3 p-3">
                                <i class="bi bi-pie-chart fs-2"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="card-title mb-0">Summary Report</h5>
                            <p class="text-muted small mb-0">Aggregated insights</p>
                        </div>
                    </div>
                    <p class="card-text">
                        Comprehensive summary with movements by location, model, top movers, and statistical insights.
                    </p>
                    <a href="{{ route('admin.stock-reports.summary') }}" class="btn btn-info w-100">
                        <i class="bi bi-graph-up me-2"></i>View Summary
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-speedometer2 me-2"></i>Quick Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <i class="bi bi-box-seam text-primary fs-1"></i>
                                <h4 class="mt-2 mb-0">{{ \App\Models\InventorySerial::count() }}</h4>
                                <p class="text-muted mb-0">Total Serials</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <i class="bi bi-arrow-repeat text-success fs-1"></i>
                                <h4 class="mt-2 mb-0">{{ \App\Models\StockLedger::whereMonth('transaction_date', now()->month)->count() }}</h4>
                                <p class="text-muted mb-0">Movements This Month</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <i class="bi bi-building text-warning fs-1"></i>
                                <h4 class="mt-2 mb-0">{{ \App\Models\Depot::count() }}</h4>
                                <p class="text-muted mb-0">Depot Locations</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <i class="bi bi-people text-info fs-1"></i>
                                <h4 class="mt-2 mb-0">{{ \App\Models\User::role('technician')->count() }}</h4>
                                <p class="text-muted mb-0">Active Technicians</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Movements -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>Recent Movements
                    </h5>
                    <a href="{{ route('admin.stock-reports.movement') }}" class="btn btn-sm btn-outline-primary">
                        View All <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction No</th>
                                    <th>Type</th>
                                    <th>Serial No</th>
                                    <th>Model</th>
                                    <th>From</th>
                                    <th>To</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $recentMovements = \App\Models\StockLedger::with(['model', 'serial'])
                                        ->notReversed()
                                        ->orderBy('transaction_date', 'desc')
                                        ->orderBy('id', 'desc')
                                        ->limit(10)
                                        ->get();
                                @endphp
                                @forelse($recentMovements as $movement)
                                    <tr>
                                        <td>{{ $movement->transaction_date->format('M d, Y') }}</td>
                                        <td><span class="badge bg-secondary">{{ $movement->transaction_no }}</span></td>
                                        <td>{!! $movement->type_badge !!}</td>
                                        <td>
                                            @if($movement->serial_id)
                                                <a href="{{ route('admin.stock-reports.stock-card', $movement->serial_id) }}" class="text-decoration-none">
                                                    {{ $movement->serial_no }}
                                                </a>
                                            @else
                                                {{ $movement->serial_no ?? '-' }}
                                            @endif
                                        </td>
                                        <td>{{ $movement->model ? $movement->model->model_name : '-' }}</td>
                                        <td>{{ $movement->from_location_name }}</td>
                                        <td>{{ $movement->to_location_name }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            No recent movements found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
