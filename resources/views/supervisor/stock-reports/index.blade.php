@extends('layouts.app')

@section('title', 'Stock Reports Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3 mb-0">Stock Reports Dashboard</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Stock Reports</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Serials</h6>
                            <h2 class="mb-0">{{ number_format($stats['total_serials']) }}</h2>
                        </div>
                        <i class="bi bi-upc-scan" style="font-size: 3rem; opacity: 0.5;"></i>
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
                            <h2 class="mb-0">{{ number_format($stats['in_stock']) }}</h2>
                        </div>
                        <i class="bi bi-box-seam" style="font-size: 3rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Issued</h6>
                            <h2 class="mb-0">{{ number_format($stats['issued']) }}</h2>
                        </div>
                        <i class="bi bi-arrow-right-circle" style="font-size: 3rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Faulty</h6>
                            <h2 class="mb-0">{{ number_format($stats['faulty']) }}</h2>
                        </div>
                        <i class="bi bi-exclamation-triangle" style="font-size: 3rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Movement Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Movements Today</h6>
                    <h3 class="mb-0">{{ number_format($stats['total_movements_today']) }}</h3>
                    <small class="text-muted">Stock movements recorded today</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Movements This Month</h6>
                    <h3 class="mb-0">{{ number_format($stats['total_movements_this_month']) }}</h3>
                    <small class="text-muted">Stock movements this month</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('supervisor.stock-reports.movement') }}" class="btn btn-outline-primary w-100 py-3">
                                <i class="bi bi-arrow-left-right d-block mb-2" style="font-size: 2rem;"></i>
                                <strong>Movement Report</strong><br>
                                <small>View all movements</small>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('supervisor.stock-reports.stock-card') }}" class="btn btn-outline-info w-100 py-3">
                                <i class="bi bi-credit-card d-block mb-2" style="font-size: 2rem;"></i>
                                <strong>Stock Card</strong><br>
                                <small>Serial history</small>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('supervisor.stock-reports.summary') }}" class="btn btn-outline-success w-100 py-3">
                                <i class="bi bi-file-earmark-bar-graph d-block mb-2" style="font-size: 2rem;"></i>
                                <strong>Summary Report</strong><br>
                                <small>Overview by model</small>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('supervisor.stock-balance.index') }}" class="btn btn-outline-warning w-100 py-3">
                                <i class="bi bi-boxes d-block mb-2" style="font-size: 2rem;"></i>
                                <strong>Stock Balance</strong><br>
                                <small>Current balances</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Stock by Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Stock by Location Type</h5>
                </div>
                <div class="card-body">
                    <canvas id="locationChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Movements -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Movements</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Serial No</th>
                                    <th>Model</th>
                                    <th>Type</th>
                                    <th class="text-end">Qty</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentMovements as $movement)
                                    <tr>
                                        <td>{{ $movement->transaction_date->format('M d, Y') }}</td>
                                        <td>
                                            @if($movement->serial)
                                                <a href="{{ route('supervisor.stock-reports.stock-card', $movement->serial->id) }}">
                                                    {{ $movement->serial->serial_no }}
                                                </a>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>{{ $movement->serial?->model?->model_name ?? '-' }}</td>
                                        <td><span class="badge bg-secondary">{{ $movement->type_label }}</span></td>
                                        <td class="text-end">
                                            @if($movement->quantity > 0)
                                                <span class="text-success">+{{ $movement->quantity }}</span>
                                            @else
                                                <span class="text-danger">{{ $movement->quantity }}</span>
                                            @endif
                                        </td>
                                        <td><small>{{ $movement->from_location_name }}</small></td>
                                        <td><small>{{ $movement->to_location_name }}</small></td>
                                        <td>{{ $movement->createdBy?->name ?? 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-3">
                                            <i class="bi bi-inbox"></i> No recent movements
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="{{ route('supervisor.stock-reports.movement') }}" class="btn btn-sm btn-outline-primary">
                            View All Movements <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Stock by Status Pie Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode($stockByStatus->keys()) !!},
            datasets: [{
                data: {!! json_encode($stockByStatus->values()) !!},
                backgroundColor: ['#28a745','#17a2b8','#007bff','#dc3545','#ffc107','#6c757d','#343a40','#fd7e14']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Stock by Location Type Bar Chart
    const locationCtx = document.getElementById('locationChart').getContext('2d');
    new Chart(locationCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($stockByLocationType->keys()->map(fn($k) => ucfirst($k))) !!},
            datasets: [{
                label: 'Stock Count',
                data: {!! json_encode($stockByLocationType->values()) !!},
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });
</script>
@endpush
