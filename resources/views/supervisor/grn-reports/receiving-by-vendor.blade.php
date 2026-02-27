@extends('layouts.app')

@section('title', 'Receiving Summary by Vendor - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Receiving Summary by Vendor</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.grn-reports.index') }}">GRN Reports</a></li>
                    <li class="breadcrumb-item active">By Vendor</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('supervisor.grn-reports.index') }}" class="btn btn-outline-secondary btn-sm">
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
                    <div class="small text-white-50">Total Vendors</div>
                    <div class="h4 mb-0">{{ number_format($vendorSummary->count()) }}</div>
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
            <form method="GET" action="{{ route('supervisor.grn-reports.receiving-by-vendor') }}" id="filterForm">
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
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Status</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">Posted (Default)</option>
                            <option value="draft" {{ ($filters['status'] ?? '') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="posted" {{ ($filters['status'] ?? '') == 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="cancelled" {{ ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                        <a href="{{ route('supervisor.grn-reports.receiving-by-vendor') }}" class="btn btn-sm btn-outline-secondary">
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
                <table class="table table-striped table-hover table-sm" id="vendorTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Vendor</th>
                            <th class="text-center">No. of GRNs</th>
                            <th class="text-center">No. of Models</th>
                            <th class="text-end">Total Qty Received</th>
                            <th class="text-end">Total Value (RM)</th>
                            <th class="text-end">% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendorSummary as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-semibold">{{ $row->vendor_name ?? $row->company_name ?? 'N/A' }}</td>
                                <td class="text-center">{{ number_format($row->grn_count) }}</td>
                                <td class="text-center">{{ number_format($row->model_count) }}</td>
                                <td class="text-end">{{ number_format($row->total_qty_received) }}</td>
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
                                <td colspan="7" class="text-center text-muted py-4">No data found matching your criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($vendorSummary->isNotEmpty())
                    <tfoot class="table-secondary fw-bold">
                        <tr>
                            <td colspan="2">Grand Total</td>
                            <td class="text-center">{{ number_format($grandTotals['total_grns']) }}</td>
                            <td></td>
                            <td class="text-end">{{ number_format($grandTotals['total_qty']) }}</td>
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
    @if($vendorSummary->isNotEmpty())
    <div class="card mt-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-bar-chart me-1"></i> Receiving Value by Vendor</h6>
        </div>
        <div class="card-body">
            <canvas id="vendorChart" height="300"></canvas>
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
        window.location.href = '{{ route("supervisor.grn-reports.export-receiving-by-vendor") }}?' + params;
    });

    @if($vendorSummary->isNotEmpty())
    // Chart
    var ctx = document.getElementById('vendorChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($vendorSummary->pluck('vendor_name')->map(fn($v) => $v ?? 'N/A')) !!},
            datasets: [{
                label: 'Total Value (RM)',
                data: {!! json_encode($vendorSummary->pluck('total_value')) !!},
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RM ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    @endif
});
</script>
@endpush
