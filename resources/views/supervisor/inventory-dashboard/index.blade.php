@extends('layouts.app')

@section('title', 'Team Inventory Dashboard - TMS')

@section('content')
    {{-- Page Header --}}
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="bi bi-speedometer2 me-2"></i>Team Inventory Dashboard</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Team Inventory</li>
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
                        <div class="stats-value">{{ number_format($overallTotals->first()?->total_quantity ?? 0) }}</div>
                        <div class="stats-label">Team Total Stock</div>
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
                        <div class="stats-value">{{ number_format($overallTotals->first()?->total_available ?? 0) }}</div>
                        <div class="stats-label">Available</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stats-value">{{ number_format($overallTotals->first()?->unique_models ?? 0) }}</div>
                        <div class="stats-label">Unique Models</div>
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
                            Team Alerts
                            @if($lowStockCounts->out_of_stock > 0)
                                <span class="badge bg-danger ms-1">{{ $lowStockCounts->out_of_stock }} critical</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 1: Stock by Technician + Category Distribution --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <x-widgets.inventory-widget
                id="stockByTechnician"
                title="Stock by Technician"
                icon="bi-people"
                :ajaxUrl="route('supervisor.inventory-dashboard.stock-by-technician')"
                height="320px"
            >
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <div class="mt-2"><small>Loading team stock...</small></div>
                </div>
            </x-widgets.inventory-widget>
        </div>
        <div class="col-lg-5">
            <x-widgets.inventory-widget
                id="categoryChart"
                title="Category Distribution"
                icon="bi-pie-chart"
                :ajaxUrl="route('supervisor.inventory-dashboard.category-distribution')"
                height="320px"
            >
                <canvas id="categoryPieChart" height="250"></canvas>
            </x-widgets.inventory-widget>
        </div>
    </div>

    {{-- Row 2: Stock by Category + Movement Trend --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <x-widgets.inventory-widget
                id="stockByCategory"
                title="Team Stock by Category"
                icon="bi-grid-3x3-gap"
                :ajaxUrl="route('supervisor.inventory-dashboard.stock-by-category')"
                height="320px"
            >
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </div>
            </x-widgets.inventory-widget>
        </div>
        <div class="col-lg-5">
            <x-widgets.inventory-widget
                id="movementTrend"
                title="Movement Trend (7 Days)"
                icon="bi-graph-up"
                :ajaxUrl="route('supervisor.inventory-dashboard.movement-trend')"
                height="320px"
            >
                <canvas id="movementTrendChart" height="250"></canvas>
            </x-widgets.inventory-widget>
        </div>
    </div>

    {{-- Row 3: Low Stock Alerts --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <x-widgets.inventory-widget
                id="lowStockAlerts"
                title="Team Low Stock Alerts"
                icon="bi-exclamation-triangle"
                color="danger"
                :ajaxUrl="route('supervisor.inventory-dashboard.low-stock-alerts')"
                height="320px"
            >
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </div>
            </x-widgets.inventory-widget>
        </div>
    </div>

    {{-- Row 4: Recent Movements + Stock Aging --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <x-widgets.inventory-widget
                id="recentMovements"
                title="Team Recent Movements"
                icon="bi-arrow-left-right"
                :ajaxUrl="route('supervisor.inventory-dashboard.recent-movements')"
                height="380px"
            >
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </div>
            </x-widgets.inventory-widget>
        </div>
        <div class="col-lg-5">
            <x-widgets.inventory-widget
                id="stockAging"
                title="Stock Aging"
                icon="bi-hourglass-split"
                color="warning"
                :ajaxUrl="route('supervisor.inventory-dashboard.stock-aging')"
                height="380px"
            >
                <canvas id="agingChart" height="200"></canvas>
                <div id="agingDetails" class="mt-2"></div>
            </x-widgets.inventory-widget>
        </div>
    </div>

    {{-- Row 5: Top Models --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <x-widgets.inventory-widget
                id="topModels"
                title="Top Models in Team"
                icon="bi-trophy"
                color="success"
                :ajaxUrl="route('supervisor.inventory-dashboard.top-models')"
                height="350px"
            >
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </div>
            </x-widgets.inventory-widget>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let categoryPieChart = null;
    let movementTrendChart = null;
    let agingChart = null;

    function loadWidget(widgetId, url, renderCallback) {
        const $loading = $(`#${widgetId}-loading`);
        const $error   = $(`#${widgetId}-error`);

        $loading.removeClass('d-none');
        $error.addClass('d-none');

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $loading.addClass('d-none');
                if (response.success) renderCallback(response);
                else $error.removeClass('d-none');
            },
            error: function() {
                $loading.addClass('d-none');
                $error.removeClass('d-none');
            }
        });
    }

    // Stock by Technician
    function loadStockByTechnician() {
        loadWidget('stockByTechnician', '{{ route("supervisor.inventory-dashboard.stock-by-technician") }}', function(response) {
            let html = '<table class="table table-sm table-hover mb-0"><thead><tr>' +
                '<th>Technician</th><th class="text-end">Models</th><th class="text-end">On Hand</th>' +
                '<th class="text-end">Available</th></tr></thead><tbody>';

            if (response.data.length === 0) {
                html += '<tr><td colspan="4" class="text-center text-muted py-3">No technician stock found</td></tr>';
            } else {
                response.data.forEach(function(item) {
                    html += `<tr>
                        <td><i class="bi bi-person me-1 text-info"></i><strong>${item.technician_name}</strong></td>
                        <td class="text-end">${parseInt(item.unique_models)}</td>
                        <td class="text-end">${parseFloat(item.total_on_hand).toLocaleString()}</td>
                        <td class="text-end fw-bold">${parseFloat(item.total_available).toLocaleString()}</td>
                    </tr>`;
                });
            }

            html += '</tbody></table>';
            $('#stockByTechnician-content').html(html);
        });
    }

    // Stock by Category
    function loadStockByCategory() {
        loadWidget('stockByCategory', '{{ route("supervisor.inventory-dashboard.stock-by-category") }}', function(response) {
            let html = '<table class="table table-sm table-hover mb-0"><thead><tr>' +
                '<th>Category</th><th class="text-end">Models</th><th class="text-end">On Hand</th>' +
                '<th class="text-end">Available</th></tr></thead><tbody>';

            response.data.forEach(function(item) {
                html += `<tr>
                    <td><strong>${item.category_name}</strong></td>
                    <td class="text-end">${parseInt(item.unique_models)}</td>
                    <td class="text-end">${parseFloat(item.total_on_hand).toLocaleString()}</td>
                    <td class="text-end fw-bold">${parseFloat(item.total_available).toLocaleString()}</td>
                </tr>`;
            });

            html += '</tbody></table>';
            $('#stockByCategory-content').html(html);
        });
    }

    // Low Stock Alerts
    function loadLowStockAlerts() {
        loadWidget('lowStockAlerts', '{{ route("supervisor.inventory-dashboard.low-stock-alerts") }}', function(response) {
            let html = '<table class="table table-sm table-hover mb-0"><thead><tr>' +
                '<th>Model</th><th>Technician</th><th class="text-end">Available</th>' +
                '<th class="text-end">Threshold</th><th>Status</th></tr></thead><tbody>';

            if (response.data.length === 0) {
                html += '<tr><td colspan="5" class="text-center py-3"><i class="bi bi-check-circle text-success fs-4 d-block mb-2"></i><span class="text-muted">All team stock levels healthy!</span></td></tr>';
            } else {
                response.data.forEach(function(item) {
                    let rowClass = item.severity === 'critical' ? 'table-danger' : 'table-warning';
                    html += `<tr class="${rowClass}">
                        <td><strong>${item.model_name}</strong><br><small class="text-muted">${item.model_code}</small></td>
                        <td>${item.technician_name}</td>
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

    // Recent Movements
    function loadRecentMovements() {
        loadWidget('recentMovements', '{{ route("supervisor.inventory-dashboard.recent-movements") }}', function(response) {
            let html = '<div class="list-group list-group-flush">';

            response.data.forEach(function(item) {
                html += `<div class="list-group-item px-0 py-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-${item.type_color}"><i class="bi ${item.type_icon} me-1"></i>${item.type_label}</span>
                            <div class="mt-1"><strong>${item.model_name}</strong>
                            ${item.serial_no ? '<small class="text-muted ms-1">S/N: ' + item.serial_no + '</small>' : ''}</div>
                            <small class="text-muted">${item.from_location_name} → ${item.to_location_name}</small>
                        </div>
                        <small class="text-muted">${item.time_ago}</small>
                    </div>
                </div>`;
            });

            html += '</div>';
            $('#recentMovements-content').html(html);
        });
    }

    // Movement Trend
    function loadMovementTrend() {
        loadWidget('movementTrend', '{{ route("supervisor.inventory-dashboard.movement-trend") }}', function(response) {
            const ctx = document.getElementById('movementTrendChart');
            if (!ctx) return;
            if (movementTrendChart) movementTrendChart.destroy();

            movementTrendChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: response.data.labels,
                    datasets: [
                        { label: 'Inbound', data: response.data.inbound, backgroundColor: 'rgba(25, 135, 84, 0.7)', borderRadius: 4 },
                        { label: 'Outbound', data: response.data.outbound, backgroundColor: 'rgba(220, 53, 69, 0.7)', borderRadius: 4 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            });
        });
    }

    // Category Distribution
    function loadCategoryDistribution() {
        loadWidget('categoryChart', '{{ route("supervisor.inventory-dashboard.category-distribution") }}', function(response) {
            const ctx = document.getElementById('categoryPieChart');
            if (!ctx) return;
            if (categoryPieChart) categoryPieChart.destroy();

            categoryPieChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: { labels: response.data.labels, datasets: [{ data: response.data.data, backgroundColor: response.data.colors, borderWidth: 2, borderColor: '#fff' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
            });
        });
    }

    // Stock Aging
    function loadStockAging() {
        loadWidget('stockAging', '{{ route("supervisor.inventory-dashboard.stock-aging") }}', function(response) {
            const ctx = document.getElementById('agingChart');
            if (!ctx) return;
            if (agingChart) agingChart.destroy();

            let brackets = response.aging.brackets;
            agingChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: { labels: brackets.map(b => b.label), datasets: [{ label: 'Items', data: brackets.map(b => b.count), backgroundColor: brackets.map(b => b.color), borderRadius: 4 }] },
                options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
            });

            $('#agingDetails').html(`<div class="text-center"><small class="text-muted">Total in stock: <strong>${response.aging.total_in_stock}</strong></small></div>`);
        });
    }

    // Top Models
    function loadTopModels() {
        loadWidget('topModels', '{{ route("supervisor.inventory-dashboard.top-models") }}', function(response) {
            let html = '<table class="table table-sm table-hover mb-0"><thead><tr>' +
                '<th>#</th><th>Model</th><th>Category</th><th class="text-end">On Hand</th>' +
                '<th class="text-end">Available</th><th class="text-end">Locations</th></tr></thead><tbody>';

            response.data.forEach(function(item, index) {
                let rank = index < 3 ? `<span class="badge bg-${['warning','secondary','info'][index]} text-dark">${index+1}</span>` : `<span class="text-muted">${index+1}</span>`;
                html += `<tr><td>${rank}</td><td><strong>${item.model_name}</strong><br><small class="text-muted">${item.model_code}</small></td>
                    <td><small>${item.category_name}</small></td><td class="text-end">${parseFloat(item.total_on_hand).toLocaleString()}</td>
                    <td class="text-end fw-bold">${parseFloat(item.total_available).toLocaleString()}</td><td class="text-end">${parseInt(item.location_count)}</td></tr>`;
            });

            html += '</tbody></table>';
            $('#topModels-content').html(html);
        });
    }

    function loadAllWidgets() {
        loadStockByTechnician();
        loadStockByCategory();
        loadCategoryDistribution();
        loadLowStockAlerts();
        loadRecentMovements();
        loadMovementTrend();
        loadStockAging();
        loadTopModels();
        $('#lastRefreshed span').text(new Date().toLocaleTimeString());
    }

    loadAllWidgets();

    $('#btnRefreshAll').on('click', function() {
        loadAllWidgets();
        showToast('All widgets refreshed', 'success');
    });

    $(document).on('click', '.widget-refresh-btn', function() {
        let widgetId = $(this).data('widget-id');
        let map = {
            'stockByTechnician': loadStockByTechnician, 'stockByCategory': loadStockByCategory,
            'categoryChart': loadCategoryDistribution, 'lowStockAlerts': loadLowStockAlerts,
            'recentMovements': loadRecentMovements, 'movementTrend': loadMovementTrend,
            'stockAging': loadStockAging, 'topModels': loadTopModels,
        };
        if (map[widgetId]) map[widgetId]();
    });

    setInterval(loadAllWidgets, 300000);
});
</script>
@endpush
