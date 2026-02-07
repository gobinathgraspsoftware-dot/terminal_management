@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Stock Variance Report</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.stock-adjustments.index') }}">Stock Adjustments</a></li>
                    <li class="breadcrumb-item active">Variance Report</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('supervisor.stock-adjustments.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4" id="summaryCards" style="display: none;">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Total Variances</h6>
                            <h3 class="mb-0" id="totalVariances">0</h3>
                        </div>
                        <i class="bi bi-list-ul fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Positive Variance</h6>
                            <h3 class="mb-0" id="positiveQty">0</h3>
                        </div>
                        <i class="bi bi-arrow-up-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Negative Variance</h6>
                            <h3 class="mb-0" id="negativeQty">0</h3>
                        </div>
                        <i class="bi bi-arrow-down-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Total Value Impact</h6>
                            <h3 class="mb-0" id="totalValue">0.00</h3>
                        </div>
                        <i class="bi bi-currency-dollar fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-funnel me-2"></i>Report Filters
            </h5>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Depot</label>
                        <select name="depot_id" id="depot_id" class="form-select">
                            <option value="">All Depots</option>
                            @foreach($depots as $depot)
                                <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Adjustment Type</label>
                        <select name="adjustment_type" id="adjustment_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="count">Stock Count</option>
                            <option value="correction">Correction</option>
                            <option value="write_off">Write Off</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" id="generateBtn" class="btn btn-primary me-2">
                            <i class="bi bi-bar-chart me-1"></i>Generate Report
                        </button>
                        <button type="button" id="resetFilters" class="btn btn-secondary me-2">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                        </button>
                        <button type="button" id="exportBtn" class="btn btn-success" style="display: none;">
                            <i class="bi bi-file-excel me-1"></i>Export to Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Results Card -->
    <div class="card" id="resultsCard" style="display: none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Variance Details</h5>
            <span class="badge bg-primary" id="resultCount">0 records</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="varianceTable" class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Adj. No</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Depot</th>
                            <th>Model</th>
                            <th>Serial No</th>
                            <th class="text-end">System</th>
                            <th class="text-end">Physical</th>
                            <th class="text-end">Variance</th>
                            <th class="text-end">Value</th>
                            <th>Remarks</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="varianceTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- No Results Message -->
    <div class="card" id="noResults" style="display: none;">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
            <h5 class="text-muted">No Variance Data Found</h5>
            <p class="text-muted">Please adjust your filters and try again.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let varianceData = [];

    // Generate report
    $('#generateBtn').click(function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Loading...');

        $.ajax({
            url: "{{ route('supervisor.stock-adjustments.variance-report') }}",
            type: 'GET',
            data: {
                generate: true,
                depot_id: $('#depot_id').val(),
                adjustment_type: $('#adjustment_type').val(),
                date_from: $('#date_from').val(),
                date_to: $('#date_to').val()
            },
            success: function(response) {
                btn.prop('disabled', false).html('<i class="bi bi-bar-chart me-1"></i>Generate Report');
                
                if (response.success) {
                    varianceData = response.data;
                    renderReport(varianceData);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-bar-chart me-1"></i>Generate Report');
                Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
            }
        });
    });

    // Render report
    function renderReport(data) {
        if (data.length === 0) {
            $('#summaryCards').hide();
            $('#resultsCard').hide();
            $('#noResults').show();
            $('#exportBtn').hide();
            return;
        }

        $('#noResults').hide();
        $('#summaryCards').show();
        $('#resultsCard').show();
        $('#exportBtn').show();

        // Calculate summaries
        let totalVariances = data.length;
        let positiveQty = 0;
        let negativeQty = 0;
        let totalValue = 0;

        data.forEach(item => {
            let variance = parseFloat(item.physical_quantity) - parseFloat(item.system_quantity);
            if (variance > 0) positiveQty += variance;
            if (variance < 0) negativeQty += Math.abs(variance);
            if (item.variance_value) totalValue += parseFloat(item.variance_value);
        });

        // Update summary cards
        $('#totalVariances').text(totalVariances);
        $('#positiveQty').text(positiveQty.toFixed(2));
        $('#negativeQty').text(negativeQty.toFixed(2));
        $('#totalValue').text(totalValue.toFixed(2));
        $('#resultCount').text(totalVariances + ' records');

        // Render table
        let tbody = $('#varianceTableBody');
        tbody.empty();

        data.forEach((item, index) => {
            let variance = parseFloat(item.physical_quantity) - parseFloat(item.system_quantity);
            let varianceClass = variance > 0 ? 'text-success fw-bold' : (variance < 0 ? 'text-danger fw-bold' : '');
            let valueClass = item.variance_value > 0 ? 'text-success' : (item.variance_value < 0 ? 'text-danger' : '');

            let row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>${item.stock_adjustment?.adjustment_no || 'N/A'}</td>
                    <td>${formatDate(item.stock_adjustment?.adjustment_date)}</td>
                    <td><span class="badge bg-info">${formatType(item.stock_adjustment?.adjustment_type)}</span></td>
                    <td>${item.stock_adjustment?.depot?.depot_name || 'N/A'}</td>
                    <td>${item.model?.model_name || 'N/A'}</td>
                    <td>${item.serial_no || '-'}</td>
                    <td class="text-end">${parseFloat(item.system_quantity).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(item.physical_quantity).toFixed(2)}</td>
                    <td class="text-end ${varianceClass}">${variance.toFixed(2)}</td>
                    <td class="text-end ${valueClass}">${item.variance_value ? parseFloat(item.variance_value).toFixed(2) : '-'}</td>
                    <td>${item.remarks || '-'}</td>
                    <td><span class="badge bg-${getStatusColor(item.stock_adjustment?.status)}">${formatStatus(item.stock_adjustment?.status)}</span></td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Helper functions
    function formatDate(date) {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-GB');
    }

    function formatType(type) {
        if (!type) return 'N/A';
        return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    function formatStatus(status) {
        if (!status) return 'N/A';
        return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    function getStatusColor(status) {
        const colors = {
            'draft': 'secondary',
            'pending_approval': 'warning',
            'approved': 'success',
            'rejected': 'danger'
        };
        return colors[status] || 'secondary';
    }

    // Reset filters
    $('#resetFilters').click(function() {
        $('#filterForm')[0].reset();
        $('#summaryCards').hide();
        $('#resultsCard').hide();
        $('#noResults').hide();
        $('#exportBtn').hide();
    });

    // Export to Excel
    $('#exportBtn').click(function() {
        let params = new URLSearchParams({
            depot_id: $('#depot_id').val(),
            adjustment_type: $('#adjustment_type').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val()
        });
        window.location.href = "{{ route('supervisor.stock-adjustments.export-variance') }}?" + params.toString();
    });
});
</script>
@endpush
