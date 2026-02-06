@extends('layouts.app')

@section('title', 'Inventory Dashboard - TMS')

@section('content')
    {{-- Page Header --}}
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="bi bi-speedometer2 me-2"></i>Inventory Dashboard</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Inventory Dashboard</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnRefreshAll">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh All
                </button>
                <span class="badge bg-light text-dark d-flex align-items-center" id="lastRefreshed">
                    <i class="bi bi-clock me-1"></i> <span>--</span>
                </span>
            </div>
        </div>
    </div>

    {{-- Summary Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-value">{{ number_format($overallTotals->total_on_hand ?? 0) }}</div>
                        <div class="stats-label">Total On Hand</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-value">{{ number_format($overallTotals->total_available ?? 0) }}</div>
                        <div class="stats-label">Available</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-upc-scan"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-value">{{ number_format($overallTotals->total_serials ?? 0) }}</div>
                        <div class="stats-label">Total Serials</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card {{ $lowStockCounts->total_alerts > 0 ? 'border-start border-danger border-3' : '' }}">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-value">{{ $lowStockCounts->total_alerts }}</div>
                        <div class="stats-label">
                            Stock Alerts
                            @if($lowStockCounts->out_of_stock > 0)
                                <span class="badge bg-danger ms-1">{{ $lowStockCounts->out_of_stock }} critical</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 1: Stock by Category + Category Distribution Chart --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <x-widgets.inventory-widget
                id="stockByCategory"
                title="Stock Summary by Category"
                icon="bi-grid-3x3-gap"
                :ajaxUrl="route('admin.inventory-dashboard.stock-by-category')"
                height="320px"
            >
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <div class="mt-2"><small>Loading categories...</small></div>
                </div>
            </x-widgets.inventory-widget>
        </div>
        <div class="col-lg-5">
            <x-widgets.inventory-widget
                id="categoryChart"
                title="Category Distribution"
                icon="bi-pie-chart"
                :ajaxUrl="route('admin.inventory-dashboard.category-distribution')"
                height="320px"
            >
                <canvas id="categoryPieChart" height="250"></canvas>
            </x-widgets.inventory-widget>
        </div>
    </div>

    {{-- Row 2: Stock by Status + Stock by Depot --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <x-widgets.inventory-widget
                id="stockByStatus"
                title="Stock by Status"
                icon="bi-tags"
                :ajaxUrl="route('admin.inventory-dashboard.stock-by-status')"
                height="320px"
            >
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <div class="mt-2"><small>Loading statuses...</small></div>
                </div>
            </x-widgets.inventory-widget>
        </div>
        <div class="col-lg-7">
            <x-widgets.inventory-widget
                id="stockByDepot"
                title="Stock by Depot"
                icon="bi-building"
                :ajaxUrl="route('admin.inventory-dashboard.stock-by-depot')"
                height="320px"
            >
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <div class="mt-2"><small>Loading depots...</small></div>
                </div>
            </x-widgets.inventory-widget>
        </div>
    </div>

    {{-- Row 3: Low Stock Alerts --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <x-widgets.inventory-widget
                id="lowStockAlerts"
                title="Low Stock Alerts"
                icon="bi-exclamation-triangle"
                color="danger"
                :ajaxUrl="route('admin.inventory-dashboard.low-stock-alerts')"
                height="350px"
            >
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <div class="mt-2"><small>Loading alerts...</small></div>
                </div>
            </x-widgets.inventory-widget>
        </div>
    </div>

    {{-- Row 4: Recent Movements + Movement Trend --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <x-widgets.inventory-widget
                id="recentMovements"
                title="Recent Stock Movements"
                icon="bi-arrow-left-right"
                :ajaxUrl="route('admin.inventory-dashboard.recent-movements')"
                height="400px"
            >
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <div class="mt-2"><small>Loading movements...</small></div>
                </div>
            </x-widgets.inventory-widget>
        </div>
        <div class="col-lg-5">
            <x-widgets.inventory-widget
                id="movementTrend"
                title="Movement Trend (7 Days)"
                icon="bi-graph-up"
                :ajaxUrl="route('admin.inventory-dashboard.movement-trend')"
                height="400px"
            >
                <canvas id="movementTrendChart" height="300"></canvas>
            </x-widgets.inventory-widget>
        </div>
    </div>

    {{-- Row 5: Stock Aging + Top Models --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <x-widgets.inventory-widget
                id="stockAging"
                title="Stock Aging Analysis"
                icon="bi-hourglass-split"
                color="warning"
                :ajaxUrl="route('admin.inventory-dashboard.stock-aging')"
                height="380px"
            >
                <canvas id="agingChart" height="200"></canvas>
                <div id="agingDetails" class="mt-2"></div>
            </x-widgets.inventory-widget>
        </div>
        <div class="col-lg-7">
            <x-widgets.inventory-widget
                id="topModels"
                title="Top Models by Quantity"
                icon="bi-trophy"
                color="success"
                :ajaxUrl="route('admin.inventory-dashboard.top-models')"
                height="380px"
            >
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <div class="mt-2"><small>Loading top models...</small></div>
                </div>
            </x-widgets.inventory-widget>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // =========================================================================
    // CHART INSTANCES
    // =========================================================================
    let categoryPieChart = null;
    let movementTrendChart = null;
    let agingChart = null;

    // =========================================================================
    // WIDGET DATA LOADERS
    // =========================================================================

    function loadWidget(widgetId, url, renderCallback) {
        const $widget  = $(`#${widgetId}`);
        const $loading = $(`#${widgetId}-loading`);
        const $error   = $(`#${widgetId}-error`);
        const $content = $(`#${widgetId}-content`);

        $loading.removeClass('d-none');
        $error.addClass('d-none');

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $loading.addClass('d-none');
                if (response.success) {
                    renderCallback(response);
                } else {
                    $error.removeClass('d-none');
                }
            },
            error: function() {
                $loading.addClass('d-none');
                $error.removeClass('d-none');
            }
        });
    }

    // =========================================================================
    // RENDER: Stock by Category Table
    // =========================================================================
    function loadStockByCategory() {
        loadWidget('stockByCategory', '{{ route("admin.inventory-dashboard.stock-by-category") }}', function(response) {
            let html = '<table class="table table-sm table-hover mb-0"><thead><tr>' +
                '<th>Category</th><th>Type</th><th class="text-end">Models</th>' +
                '<th class="text-end">On Hand</th><th class="text-end">Reserved</th>' +
                '<th class="text-end">Available</th></tr></thead><tbody>';

            if (response.data.length === 0) {
                html += '<tr><td colspan="6" class="text-center text-muted py-3">No stock data found</td></tr>';
            } else {
                response.data.forEach(function(item) {
                    html += `<tr>
                        <td><strong>${item.category_name}</strong><br><small class="text-muted">${item.category_code}</small></td>
                        <td><span class="badge bg-secondary">${item.category_type}</span></td>
                        <td class="text-end">${parseInt(item.unique_models)}</td>
                        <td class="text-end">${parseFloat(item.total_on_hand).toLocaleString()}</td>
                        <td class="text-end">${parseFloat(item.total_reserved).toLocaleString()}</td>
                        <td class="text-end fw-bold">${parseFloat(item.total_available).toLocaleString()}</td>
                    </tr>`;
                });
            }

            html += '</tbody></table>';
            $('#stockByCategory-content').html(html);
        });
    }

    // =========================================================================
    // RENDER: Stock by Status
    // =========================================================================
    function loadStockByStatus() {
        loadWidget('stockByStatus', '{{ route("admin.inventory-dashboard.stock-by-status") }}', function(response) {
            let html = '<div class="list-group list-group-flush">';

            if (response.data.length === 0) {
                html += '<div class="text-center text-muted py-3">No data</div>';
            } else {
                let total = response.data.reduce((sum, i) => sum + parseInt(i.total_count), 0);
                response.data.forEach(function(item) {
                    let pct = total > 0 ? ((parseInt(item.total_count) / total) * 100).toFixed(1) : 0;
                    html += `<div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <span class="badge bg-${item.status_color} me-2">${item.status_label}</span>
                        </div>
                        <div class="text-end">
                            <strong>${parseInt(item.total_count).toLocaleString()}</strong>
                            <small class="text-muted ms-1">(${pct}%)</small>
                        </div>
                    </div>`;
                });
            }

            html += '</div>';
            $('#stockByStatus-content').html(html);
        });
    }

    // =========================================================================
    // RENDER: Stock by Depot
    // =========================================================================
    function loadStockByDepot() {
        loadWidget('stockByDepot', '{{ route("admin.inventory-dashboard.stock-by-depot") }}', function(response) {
            let html = '<table class="table table-sm table-hover mb-0"><thead><tr>' +
                '<th>Depot</th><th class="text-end">Models</th><th class="text-end">On Hand</th>' +
                '<th class="text-end">Reserved</th><th class="text-end">Available</th></tr></thead><tbody>';

            if (response.data.length === 0) {
                html += '<tr><td colspan="5" class="text-center text-muted py-3">No depot stock found</td></tr>';
            } else {
                response.data.forEach(function(item) {
                    html += `<tr>
                        <td><i class="bi bi-building me-1 text-primary"></i><strong>${item.depot_name}</strong>
                            <br><small class="text-muted">${item.depot_code}</small></td>
                        <td class="text-end">${parseInt(item.unique_models)}</td>
                        <td class="text-end">${parseFloat(item.total_on_hand).toLocaleString()}</td>
                        <td class="text-end">${parseFloat(item.total_reserved).toLocaleString()}</td>
                        <td class="text-end fw-bold">${parseFloat(item.total_available).toLocaleString()}</td>
                    </tr>`;
                });
            }

            html += '</tbody></table>';
            $('#stockByDepot-content').html(html);
        });
    }

    // =========================================================================
    // RENDER: Low Stock Alerts
    // =========================================================================
    function loadLowStockAlerts() {
        loadWidget('lowStockAlerts', '{{ route("admin.inventory-dashboard.low-stock-alerts") }}', function(response) {
            let html = '<table class="table table-sm table-hover mb-0"><thead><tr>' +
                '<th>Model</th><th>Category</th><th>Location</th>' +
                '<th class="text-end">Available</th><th class="text-end">Threshold</th>' +
                '<th>Severity</th></tr></thead><tbody>';

            if (response.data.length === 0) {
                html += '<tr><td colspan="6" class="text-center py-3">' +
                    '<i class="bi bi-check-circle text-success fs-4 d-block mb-2"></i>' +
                    '<span class="text-muted">No stock alerts - all levels are healthy!</span></td></tr>';
            } else {
                response.data.forEach(function(item) {
                    let rowClass = item.severity === 'critical' ? 'table-danger' : 'table-warning';
                    html += `<tr class="${rowClass}">
                        <td><strong>${item.model_name}</strong><br><small class="text-muted">${item.model_code}</small></td>
                        <td>${item.category_name}</td>
                        <td><small>${item.location_name}</small></td>
                        <td class="text-end fw-bold">${parseFloat(item.quantity_available).toLocaleString()}</td>
                        <td class="text-end">${parseInt(item.threshold)}</td>
                        <td>${item.severity_badge}</td>
                    </tr>`;
                });
            }

            html += '</tbody></table>';
            $('#lowStockAlerts-content').html(html);
        });
    }

    // =========================================================================
    // RENDER: Recent Movements
    // =========================================================================
    function loadRecentMovements() {
        loadWidget('recentMovements', '{{ route("admin.inventory-dashboard.recent-movements") }}', function(response) {
            let html = '<div class="list-group list-group-flush">';

            if (response.data.length === 0) {
                html += '<div class="text-center text-muted py-3">No recent movements</div>';
            } else {
                response.data.forEach(function(item) {
                    let reversed = item.is_reversed ? '<span class="badge bg-danger ms-1">Reversed</span>' : '';
                    html += `<div class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge bg-${item.type_color}">
                                    <i class="bi ${item.type_icon} me-1"></i>${item.type_label}
                                </span>
                                ${reversed}
                                <div class="mt-1">
                                    <strong>${item.model_name}</strong>
                                    ${item.serial_no ? '<small class="text-muted ms-1">S/N: ' + item.serial_no + '</small>' : ''}
                                </div>
                                <small class="text-muted">
                                    ${item.from_location_name} → ${item.to_location_name}
                                    ${item.created_by_name ? '&middot; by ' + item.created_by_name : ''}
                                </small>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">${item.time_ago}</small>
                                <br><small class="text-muted">${item.transaction_no}</small>
                            </div>
                        </div>
                    </div>`;
                });
            }

            html += '</div>';
            $('#recentMovements-content').html(html);
        });
    }

    // =========================================================================
    // RENDER: Movement Trend Chart
    // =========================================================================
    function loadMovementTrend() {
        loadWidget('movementTrend', '{{ route("admin.inventory-dashboard.movement-trend") }}', function(response) {
            const ctx = document.getElementById('movementTrendChart');
            if (!ctx) return;

            if (movementTrendChart) movementTrendChart.destroy();

            movementTrendChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: response.data.labels,
                    datasets: [
                        {
                            label: 'Inbound',
                            data: response.data.inbound,
                            backgroundColor: 'rgba(25, 135, 84, 0.7)',
                            borderColor: '#198754',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                        {
                            label: 'Outbound',
                            data: response.data.outbound,
                            backgroundColor: 'rgba(220, 53, 69, 0.7)',
                            borderColor: '#dc3545',
                            borderWidth: 1,
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', labels: { usePointStyle: true, pointStyle: 'circle' } } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        });
    }

    // =========================================================================
    // RENDER: Category Distribution Pie
    // =========================================================================
    function loadCategoryDistribution() {
        loadWidget('categoryChart', '{{ route("admin.inventory-dashboard.category-distribution") }}', function(response) {
            const ctx = document.getElementById('categoryPieChart');
            if (!ctx) return;

            if (categoryPieChart) categoryPieChart.destroy();

            categoryPieChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: response.data.labels,
                    datasets: [{
                        data: response.data.data,
                        backgroundColor: response.data.colors,
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 12, font: { size: 11 } } }
                    }
                }
            });
        });
    }

    // =========================================================================
    // RENDER: Stock Aging
    // =========================================================================
    function loadStockAging() {
        loadWidget('stockAging', '{{ route("admin.inventory-dashboard.stock-aging") }}', function(response) {
            const ctx = document.getElementById('agingChart');
            if (!ctx) return;

            if (agingChart) agingChart.destroy();

            let brackets = response.aging.brackets;

            agingChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: brackets.map(b => b.label),
                    datasets: [{
                        label: 'Items',
                        data: brackets.map(b => b.count),
                        backgroundColor: brackets.map(b => b.color),
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });

            let detailHtml = `<div class="text-center">
                <small class="text-muted">Total in stock: <strong>${response.aging.total_in_stock}</strong></small>
            </div>`;
            $('#agingDetails').html(detailHtml);
        });
    }

    // =========================================================================
    // RENDER: Top Models
    // =========================================================================
    function loadTopModels() {
        loadWidget('topModels', '{{ route("admin.inventory-dashboard.top-models") }}', function(response) {
            let html = '<table class="table table-sm table-hover mb-0"><thead><tr>' +
                '<th>#</th><th>Model</th><th>Category</th>' +
                '<th class="text-end">On Hand</th><th class="text-end">Available</th>' +
                '<th class="text-end">Locations</th></tr></thead><tbody>';

            if (response.data.length === 0) {
                html += '<tr><td colspan="6" class="text-center text-muted py-3">No models found</td></tr>';
            } else {
                response.data.forEach(function(item, index) {
                    let rankBadge = index < 3
                        ? `<span class="badge bg-${['warning','secondary','info'][index]} text-dark">${index + 1}</span>`
                        : `<span class="text-muted">${index + 1}</span>`;
                    html += `<tr>
                        <td>${rankBadge}</td>
                        <td><strong>${item.model_name}</strong><br><small class="text-muted">${item.model_code}${item.brand ? ' &middot; ' + item.brand : ''}</small></td>
                        <td><small>${item.category_name}</small></td>
                        <td class="text-end">${parseFloat(item.total_on_hand).toLocaleString()}</td>
                        <td class="text-end fw-bold">${parseFloat(item.total_available).toLocaleString()}</td>
                        <td class="text-end">${parseInt(item.location_count)}</td>
                    </tr>`;
                });
            }

            html += '</tbody></table>';
            $('#topModels-content').html(html);
        });
    }

    // =========================================================================
    // INITIAL LOAD
    // =========================================================================
    function loadAllWidgets() {
        loadStockByCategory();
        loadStockByStatus();
        loadStockByDepot();
        loadCategoryDistribution();
        loadLowStockAlerts();
        loadRecentMovements();
        loadMovementTrend();
        loadStockAging();
        loadTopModels();

        $('#lastRefreshed span').text(new Date().toLocaleTimeString());
    }

    loadAllWidgets();

    // =========================================================================
    // REFRESH HANDLERS
    // =========================================================================

    // Refresh All button
    $('#btnRefreshAll').on('click', function() {
        loadAllWidgets();
        showToast('All widgets refreshed', 'success');
    });

    // Individual widget refresh
    $(document).on('click', '.widget-refresh-btn', function() {
        let widgetId = $(this).data('widget-id');
        let loaderMap = {
            'stockByCategory': loadStockByCategory,
            'stockByStatus': loadStockByStatus,
            'stockByDepot': loadStockByDepot,
            'categoryChart': loadCategoryDistribution,
            'lowStockAlerts': loadLowStockAlerts,
            'recentMovements': loadRecentMovements,
            'movementTrend': loadMovementTrend,
            'stockAging': loadStockAging,
            'topModels': loadTopModels,
        };
        if (loaderMap[widgetId]) {
            loaderMap[widgetId]();
        }
    });

    // Auto-refresh every 5 minutes
    setInterval(loadAllWidgets, 300000);
});
</script>
@endpush
