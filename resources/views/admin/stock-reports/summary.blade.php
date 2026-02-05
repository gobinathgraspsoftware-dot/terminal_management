@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Stock Summary Report</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.stock-reports.index') }}">Stock Reports</a></li>
                            <li class="breadcrumb-item active">Summary</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.stock-reports.summary-export') }}" class="btn btn-success">
                        <i class="bi bi-file-excel"></i> Export to Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock by Model -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-box-seam"></i> Stock by Terminal Model</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Model</th>
                                    <th>Category</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">In Stock</th>
                                    <th class="text-end">Issued</th>
                                    <th class="text-end">Installed</th>
                                    <th class="text-end">Faulty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byModel->take(10) as $item)
                                    <tr>
                                        <td>
                                            @if($item->model)
                                                <strong>{{ $item->model->model_name }}</strong>
                                            @else
                                                <span class="text-muted">Unknown Model</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->model && $item->model->category)
                                                <span class="badge bg-secondary">{{ $item->model->category->category_name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end"><strong>{{ number_format($item->total) }}</strong></td>
                                        <td class="text-end"><span class="badge bg-success">{{ number_format($item->in_stock) }}</span></td>
                                        <td class="text-end"><span class="badge bg-info">{{ number_format($item->issued) }}</span></td>
                                        <td class="text-end"><span class="badge bg-primary">{{ number_format($item->installed) }}</span></td>
                                        <td class="text-end">
                                            @if($item->faulty > 0)
                                                <span class="badge bg-danger">{{ number_format($item->faulty) }}</span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">
                                            <i class="bi bi-inbox"></i> No stock data available
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($byModel->count() > 10)
                                <tfoot>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <small>Showing top 10 models. Total models: {{ $byModel->count() }}</small>
                                        </td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock by Status -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Stock by Status</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Status</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byStatus as $status)
                                    <tr>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'in_stock' => 'success',
                                                    'issued' => 'info',
                                                    'installed' => 'primary',
                                                    'faulty' => 'danger',
                                                    'returned' => 'warning',
                                                    'wasted' => 'dark',
                                                ];
                                                $color = $statusColors[$status->current_status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">{{ ucfirst(str_replace('_', ' ', $status->current_status)) }}</span>
                                        </td>
                                        <td class="text-end"><strong>{{ number_format($status->count) }}</strong></td>
                                        <td class="text-end">{{ number_format($status->percentage, 2) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Aging -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Stock Aging</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Age Group</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($aging as $age)
                                    <tr>
                                        <td>{{ $age->age_group }}</td>
                                        <td class="text-end"><strong>{{ number_format($age->count) }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock by Depot -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-building"></i> Stock by Depot</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Depot</th>
                                    <th>Model</th>
                                    <th class="text-end">Available</th>
                                    <th class="text-end">Reserved</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byDepot->take(15) as $item)
                                    <tr>
                                        <td>
                                            @if($item->depot)
                                                {{ $item->depot->depot_name }}
                                            @else
                                                <span class="text-muted">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->model)
                                                {{ $item->model->model_name }}
                                            @else
                                                <span class="text-muted">Unknown Model</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($item->available) }}</td>
                                        <td class="text-end">{{ number_format($item->reserved) }}</td>
                                        <td class="text-end"><strong>{{ number_format($item->total) }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            <i class="bi bi-inbox"></i> No depot stock data available
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

    <!-- Low Stock Alerts -->
    @if($lowStock->count() > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Low Stock Alerts (≤ 5 units)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Depot</th>
                                        <th>Model</th>
                                        <th class="text-end">Available</th>
                                        <th class="text-end">Reserved</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lowStock as $item)
                                        <tr class="{{ $item->available == 0 ? 'table-danger' : 'table-warning' }}">
                                            <td>
                                                @if($item->depot)
                                                    {{ $item->depot->depot_name }}
                                                @else
                                                    <span class="text-muted">Unknown</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item->model)
                                                    {{ $item->model->model_name }}
                                                @else
                                                    <span class="text-muted">Unknown Model</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <strong class="{{ $item->available == 0 ? 'text-danger' : 'text-warning' }}">
                                                    {{ number_format($item->available) }}
                                                </strong>
                                            </td>
                                            <td class="text-end">{{ number_format($item->reserved) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Optional: Add charts if needed with Chart.js
    // You can add pie charts for status distribution, bar charts for model comparison, etc.
</script>
@endpush
