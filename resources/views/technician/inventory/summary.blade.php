@extends('layouts.app')

@section('title', 'My Inventory Summary')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-bar-chart"></i> Inventory Summary</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.inventory.index') }}">My Inventory</a></li>
                    <li class="breadcrumb-item active">Summary</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('technician.inventory.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Inventory
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Items</h6>
                            <h2 class="mb-0">{{ $summary['total_items'] }}</h2>
                            <small class="text-muted">In my possession</small>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-boxes fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Issued</h6>
                            <h2 class="mb-0 text-success">{{ $summary['issued_items'] }}</h2>
                            <small class="text-muted">Ready for deployment</small>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Deployed</h6>
                            <h2 class="mb-0 text-info">{{ $summary['deployed_items'] }}</h2>
                            <small class="text-muted">At client sites</small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-geo-alt fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Faulty</h6>
                            <h2 class="mb-0 text-danger">{{ $summary['faulty_items'] }}</h2>
                            <small class="text-muted">Need return/repair</small>
                        </div>
                        <div class="text-danger">
                            <i class="bi bi-exclamation-triangle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Inventory by Model -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-grid"></i> Inventory by Model</h5>
                    <span class="badge bg-primary">{{ $inventoryByModel->count() }} Models</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Model</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Issued</th>
                                    <th class="text-center">Deployed</th>
                                    <th class="text-center">Faulty</th>
                                    <th class="text-center">Chart</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventoryByModel as $item)
                                <tr>
                                    <td>{{ $item->category_name }}</td>
                                    <td><strong>{{ $item->model_name }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $item->total_quantity }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ $item->issued_qty }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $item->deployed_qty }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($item->faulty_qty > 0)
                                        <span class="badge bg-danger">{{ $item->faulty_qty }}</span>
                                        @else
                                        <span class="badge bg-secondary">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            @php
                                                $issuedPct = ($item->issued_qty / $item->total_quantity) * 100;
                                                $deployedPct = ($item->deployed_qty / $item->total_quantity) * 100;
                                                $faultyPct = ($item->faulty_qty / $item->total_quantity) * 100;
                                            @endphp
                                            <div class="progress-bar bg-success" style="width: {{ $issuedPct }}%" title="Issued: {{ $item->issued_qty }}">{{ $item->issued_qty }}</div>
                                            <div class="progress-bar bg-info" style="width: {{ $deployedPct }}%" title="Deployed: {{ $item->deployed_qty }}">{{ $item->deployed_qty }}</div>
                                            @if($faultyPct > 0)
                                            <div class="progress-bar bg-danger" style="width: {{ $faultyPct }}%" title="Faulty: {{ $item->faulty_qty }}">{{ $item->faulty_qty }}</div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No inventory data available
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <!-- Inventory by Category -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> By Category</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart"></canvas>
                    
                    <div class="mt-3">
                        @foreach($inventoryByCategory as $cat)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>{{ $cat->category_name }}</span>
                            <span class="badge bg-primary">{{ $cat->total_quantity }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Inventory by Status -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-diagram-3"></i> By Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>

                    <div class="mt-3">
                        @foreach($inventoryByStatus as $status)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-capitalize">{{ $status->status }}</span>
                            <span class="badge 
                                @if($status->status == 'issued') bg-success
                                @elseif($status->status == 'deployed') bg-info
                                @elseif($status->status == 'faulty') bg-danger
                                @else bg-secondary
                                @endif
                            ">{{ $status->count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Issue/Return Statistics -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Movement Stats</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Issued (All time)</span>
                            <strong>{{ $issueStats['total_issued'] }}</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Returned (All time)</span>
                            <strong>{{ $issueStats['total_returned'] }}</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: 100%"></div>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Issued (Last 30 days)</span>
                            <strong class="text-success">{{ $issueStats['issued_last_30_days'] }}</strong>
                        </div>
                    </div>

                    <div class="mb-0">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Returned (Last 30 days)</span>
                            <strong class="text-warning">{{ $issueStats['returned_last_30_days'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Quick Actions</h6>
                            <small class="text-muted">Manage your inventory efficiently</small>
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('technician.inventory.index') }}" class="btn btn-outline-primary">
                                <i class="bi bi-list-ul"></i> View All Items
                            </a>
                            <a href="{{ route('technician.inventory.return-request') }}" class="btn btn-danger">
                                <i class="bi bi-arrow-return-left"></i> Request Return
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    // Category Chart (Pie)
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryData = @json($inventoryByCategory);
    
    new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: categoryData.map(item => item.category_name),
            datasets: [{
                data: categoryData.map(item => item.total_quantity),
                backgroundColor: [
                    '#667eea', '#764ba2', '#f093fb', '#4facfe', 
                    '#43e97b', '#fa709a', '#fee140', '#30cfd0'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Status Chart (Doughnut)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusData = @json($inventoryByStatus);
    
    const statusColors = {
        'issued': '#28a745',
        'deployed': '#17a2b8',
        'faulty': '#dc3545'
    };
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
            datasets: [{
                data: statusData.map(item => item.count),
                backgroundColor: statusData.map(item => statusColors[item.status] || '#6c757d')
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>
@endpush

@push('styles')
<style>
    .card {
        border-radius: 10px;
    }
    
    .progress {
        border-radius: 5px;
    }
    
    @media (max-width: 768px) {
        .btn-group {
            flex-direction: column;
            width: 100%;
        }
        
        .btn-group .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }
</style>
@endpush
