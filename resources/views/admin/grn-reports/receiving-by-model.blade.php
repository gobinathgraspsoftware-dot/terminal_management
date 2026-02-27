@extends('layouts.app')

@section('title', 'Receiving Summary by Model - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Receiving Summary by Model</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.grn-reports.index') }}">GRN Reports</a></li>
                    <li class="breadcrumb-item active">By Model</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.grn-reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <button type="button" class="btn btn-outline-success btn-sm ms-1" id="btnExport">
                <i class="bi bi-download me-1"></i> Export Excel
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm ms-1" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
        </div>
    </div>

    <!-- Grand Totals -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white border-0">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-white-50">Total Models</div>
                    <div class="h4 mb-0">{{ number_format($modelSummary->count()) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white border-0">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-white-50">Total GRNs</div>
                    <div class="h4 mb-0">{{ number_format($grandTotals['total_grns']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white border-0">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-white-50">Total Qty Received</div>
                    <div class="h4 mb-0">{{ number_format($grandTotals['total_qty']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark border-0">
                <div class="card-body py-2 px-3 text-center">
                    <div class="small text-muted">Total Value</div>
                    <div class="h4 mb-0">RM {{ number_format($grandTotals['total_value'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.grn-reports.receiving-by-model') }}" id="filterForm">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Date From</label>
                        <input type="date" class="form-control form-control-sm" name="date_from"
                               value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Date To</label>
                        <input type="date" class="form-control form-control-sm" name="date_to"
                               value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Category</label>
                        <select class="form-select form-select-sm" name="category_id">
                            <option value="">All Categories</option>
                            @foreach($filterOptions['categories'] as $category)
                                <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Vendor</label>
                        <select class="form-select form-select-sm select2-filter" name="vendor_id">
                            <option value="">All Vendors</option>
                            @foreach($filterOptions['vendors'] as $vendor)
                                <option value="{{ $vendor->id }}" {{ ($filters['vendor_id'] ?? '') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->vendor_name ?? $vendor->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Depot</label>
                        <select class="form-select form-select-sm" name="receiving_depot_id">
                            <option value="">All Depots</option>
                            @foreach($filterOptions['depots'] as $depot)
                                <option value="{{ $depot->id }}" {{ ($filters['receiving_depot_id'] ?? '') == $depot->id ? 'selected' : '' }}>
                                    {{ $depot->depot_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                        <a href="{{ route('admin.grn-reports.receiving-by-model') }}" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm" id="modelTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Model</th>
                            <th>Category</th>
                            <th class="text-center">GRNs</th>
                            <th class="text-center">Vendors</th>
                            <th class="text-end">Qty Received</th>
                            <th class="text-end">Avg Unit Cost (RM)</th>
                            <th class="text-end">Total Value (RM)</th>
                            <th class="text-end">% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($modelSummary as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-semibold">{{ $row->model_name ?? 'N/A' }}</td>
                                <td>{{ $row->category_name ?? 'N/A' }}</td>
                                <td class="text-center">{{ number_format($row->grn_count) }}</td>
                                <td class="text-center">{{ number_format($row->vendor_count) }}</td>
                                <td class="text-end">{{ number_format($row->total_qty_received) }}</td>
                                <td class="text-end">{{ number_format($row->avg_unit_cost, 2) }}</td>
                                <td class="text-end">{{ number_format($row->total_value, 2) }}</td>
                                <td class="text-end">
                                    @if($grandTotals['total_value'] > 0)
                                        {{ number_format(($row->total_value / $grandTotals['total_value']) * 100, 1) }}%
                                    @else
                                        0%
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No data found matching your criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($modelSummary->isNotEmpty())
                    <tfoot class="table-secondary fw-bold">
                        <tr>
                            <td colspan="3">Grand Total</td>
                            <td class="text-center">{{ number_format($grandTotals['total_grns']) }}</td>
                            <td></td>
                            <td class="text-end">{{ number_format($grandTotals['total_qty']) }}</td>
                            <td></td>
                            <td class="text-end">{{ number_format($grandTotals['total_value'], 2) }}</td>
                            <td class="text-end">100%</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <!-- Chart -->
    @if($modelSummary->isNotEmpty())
    <div class="card mt-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-pie-chart me-1"></i> Receiving Qty by Model</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <canvas id="modelPieChart" height="300"></canvas>
                </div>
                <div class="col-md-6">
                    <canvas id="modelBarChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2-filter').select2({
        theme: 'bootstrap-5',
        allowClear: true,
        placeholder: 'Select...',
        width: '100%'
    });

    $('#btnExport').on('click', function() {
        var params = $('#filterForm').serialize();
        window.location.href = '{{ route("admin.grn-reports.export-receiving-by-model") }}?' + params;
    });

    @if($modelSummary->isNotEmpty())
    var labels = {!! json_encode($modelSummary->pluck('model_name')) !!};
    var qtyData = {!! json_encode($modelSummary->pluck('total_qty_received')) !!};
    var valueData = {!! json_encode($modelSummary->pluck('total_value')) !!};

    var colors = [
        '#0d6efd', '#198754', '#0dcaf0', '#ffc107', '#dc3545',
        '#6610f2', '#fd7e14', '#20c997', '#6f42c1', '#d63384'
    ];

    // Pie Chart - Qty
    new Chart(document.getElementById('modelPieChart'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: qtyData,
                backgroundColor: colors.slice(0, labels.length)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: { display: true, text: 'Quantity Distribution' },
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
            }
        }
    });

    // Bar Chart - Value
    new Chart(document.getElementById('modelBarChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Value (RM)',
                data: valueData,
                backgroundColor: 'rgba(25, 135, 84, 0.7)',
                borderColor: 'rgba(25, 135, 84, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                title: { display: true, text: 'Value by Model (RM)' },
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(val) { return 'RM ' + val.toLocaleString(); }
                    }
                }
            }
        }
    });
    @endif
});
</script>
@endpush
