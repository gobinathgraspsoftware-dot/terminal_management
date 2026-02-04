@extends('layouts.app')

@section('title', 'Low Stock Alerts')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-exclamation-triangle me-2"></i>Stock Alerts
            </h1>
            <p class="text-muted mb-0">Items requiring attention</p>
        </div>
        <a href="{{ route('admin.stock-balance.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Stock Balance
        </a>
    </div>

    <div class="row">
        <!-- Low Stock Items -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>Low Stock Items
                        <span class="badge bg-dark float-end">{{ $lowStockItems->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($lowStockItems->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Model</th>
                                    <th>Location</th>
                                    <th class="text-end">Available</th>
                                    <th class="text-end">Min Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStockItems as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->model->model_name }}</strong><br>
                                        <small class="text-muted">{{ $item->model->model_code }}</small>
                                    </td>
                                    <td>{{ $item->location_name }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-warning text-dark">
                                            {{ number_format($item->quantity_available, 0) }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ $item->model->min_stock_level ?? 5 }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">No low stock items</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Out of Stock Items -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-x-circle me-2"></i>Out of Stock Items
                        <span class="badge bg-dark float-end">{{ $outOfStockItems->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($outOfStockItems->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Model</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($outOfStockItems as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->model->model_name }}</strong><br>
                                        <small class="text-muted">{{ $item->model->model_code }}</small>
                                    </td>
                                    <td>{{ $item->location_name }}</td>
                                    <td><span class="badge bg-danger">Out of Stock</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">No out of stock items</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
