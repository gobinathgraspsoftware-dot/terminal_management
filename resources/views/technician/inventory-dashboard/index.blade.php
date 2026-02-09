@extends('layouts.app')

@section('title', 'My Inventory Dashboard - TMS')

@section('content')
    {{-- Page Header --}}
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1><i class="bi bi-boxes me-2"></i>My Inventory</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Inventory</li>
                    </ol>
                </nav>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnRefreshAll">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
        </div>
    </div>

    {{-- Summary Stats Cards (Mobile-optimized) --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stats-card text-center">
                <div class="stats-icon bg-primary bg-opacity-10 text-primary mx-auto" style="width: 40px; height: 40px; font-size: 1.2rem;">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stats-value" style="font-size: 1.5rem;">{{ number_format($overallTotals->total_on_hand ?? 0) }}</div>
                <div class="stats-label">Total Items</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stats-card text-center">
                <div class="stats-icon bg-success bg-opacity-10 text-success mx-auto" style="width: 40px; height: 40px; font-size: 1.2rem;">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stats-value" style="font-size: 1.5rem;">{{ number_format($overallTotals->total_available ?? 0) }}</div>
                <div class="stats-label">Available</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stats-card text-center">
                <div class="stats-icon bg-info bg-opacity-10 text-info mx-auto" style="width: 40px; height: 40px; font-size: 1.2rem;">
                    <i class="bi bi-upc-scan"></i>
                </div>
                <div class="stats-value" style="font-size: 1.5rem;">{{ number_format($overallTotals->total_serials ?? 0) }}</div>
                <div class="stats-label">Serials</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stats-card text-center {{ $lowStockCounts->total_alerts > 0 ? 'border-start border-danger border-3' : '' }}">
                <div class="stats-icon bg-danger bg-opacity-10 text-danger mx-auto" style="width: 40px; height: 40px; font-size: 1.2rem;">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stats-value" style="font-size: 1.5rem;">{{ $lowStockCounts->total_alerts }}</div>
                <div class="stats-label">Alerts</div>
            </div>
        </div>
    </div>

    {{-- Row 1: Stock by Category + Category Chart --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            @include('components.inventory-widget', [
                'widgetId'      => 'stockByCategory',
                'widgetTitle'   => 'My Stock by Category',
                'widgetIcon'    => 'bi-grid-3x3-gap',
                'widgetColor'   => 'primary',
                'widgetHeight'  => '300px',
                'widgetContent' => '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm" role="status"></div><div class="mt-2"><small>Loading...</small></div></div>',
            ])
        </div>
        <div class="col-lg-5">
            @include('components.inventory-widget', [
                'widgetId'      => 'categoryChart',
                'widgetTitle'   => 'Category Distribution',
                'widgetIcon'    => 'bi-pie-chart',
                'widgetColor'   => 'primary',
                'widgetHeight'  => '300px',
                'widgetContent' => '<canvas id="categoryPieChart" height="220"></canvas>',
            ])
        </div>
    </div>

    {{-- Row 2: Low Stock + Movement Trend --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            @include('components.inventory-widget', [
                'widgetId'      => 'lowStockAlerts',
                'widgetTitle'   => 'Low Stock Alerts',
                'widgetIcon'    => 'bi-exclamation-triangle',
                'widgetColor'   => 'danger',
                'widgetHeight'  => '320px',
                'widgetContent' => '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm" role="status"></div></div>',
            ])
        </div>
        <div class="col-lg-6">
            @include('components.inventory-widget', [
                'widgetId'      => 'movementTrend',
                'widgetTitle'   => 'Movement Trend (7 Days)',
                'widgetIcon'    => 'bi-graph-up',
                'widgetColor'   => 'primary',
                'widgetHeight'  => '320px',
                'widgetContent' => '<canvas id="movementTrendChart" height="250"></canvas>',
            ])
        </div>
    </div>

    {{-- Row 3: Recent Movements --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            @include('components.inventory-widget', [
                'widgetId'      => 'recentMovements',
                'widgetTitle'   => 'My Recent Movements',
                'widgetIcon'    => 'bi-arrow-left-right',
                'widgetColor'   => 'primary',
                'widgetHeight'  => '380px',
                'widgetContent' => '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm" role="status"></div></div>',
            ])
        </div>
    </div>

    {{-- Row 4: Stock Aging + Top Models --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            @include('components.inventory-widget', [
                'widgetId'      => 'stockAging',
                'widgetTitle'   => 'Stock Aging',
                'widgetIcon'    => 'bi-hourglass-split',
                'widgetColor'   => 'warning',
                'widgetHeight'  => '350px',
                'widgetContent' => '<canvas id="agingChart" height="180"></canvas><div id="agingDetails" class="mt-2"></div>',
            ])
        </div>
        <div class="col-lg-7">
            @include('components.inventory-widget', [
                'widgetId'      => 'topModels',
                'widgetTitle'   => 'My Top Models',
                'widgetIcon'    => 'bi-trophy',
                'widgetColor'   => 'success',
                'widgetHeight'  => '350px',
                'widgetContent' => '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm" role="status"></div></div>',
            ])
        </div>
    </div>
@endsection

@push('styles')
<style>
    .inventory-widget { border: none; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: box-shadow 0.2s; }
    .inventory-widget:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .inventory-widget .card-header { background: white; border-bottom: 1px solid #e2e8f0; border-radius: 8px 8px 0 0; }
    .inventory-widget .card-header h6 { font-size: 0.875rem; }
    .inventory-widget .widget-loading { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.85); z-index: 5; display: flex; align-items: center; justify-content: center; }
    .inventory-widget .widget-refresh-btn { padding: 0.15rem 0.4rem; font-size: 0.75rem; line-height: 1; }
</style>
@endpush

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
            url: url, type: 'GET', dataType: 'json',
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

    // Stock by Category
    function loadStockByCategory() {
        loadWidget('stockByCategory', '{{ route("technician.inventory-dashboard.stock-by-category") }}', function(response) {
            let html = '<table class="table table-sm table-hover mb-0"><thead><tr>' +
                '<th>Category</th><th class="text-end">On Hand</th><th class="text-end">Available</th></tr></thead><tbody>';
            if (response.data.length === 0) {
                html += '<tr><td colspan="3" class="text-center text-muted py-3">No stock assigned</td></tr>';
            } else {
                response.data.forEach(function(item) {
                    html += `<tr><td><strong>${item.category_name}</strong></td>
                        <td class="text-end">${parseFloat(item.total_on_hand).toLocaleString()}</td>
                        <td class="text-end fw-bold">${parseFloat(item.total_available).toLocaleString()}</td></tr>`;
                });
            }
            html += '</tbody></table>';
            $('#stockByCategory-content').html(html);
        });
    }

    // Category Distribution
    function loadCategoryDistribution() {
        loadWidget('categoryChart', '{{ route("technician.inventory-dashboard.category-distribution") }}', function(response) {
            const ctx = document.getElementById('categoryPieChart');
            if (!ctx) return;
            if (categoryPieChart) categoryPieChart.destroy();
            categoryPieChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: { labels: response.data.labels, datasets: [{ data: response.data.data, backgroundColor: response.data.colors, borderWidth: 2, borderColor: '#fff' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
            });
        });
    }

    // Low Stock Alerts
    function loadLowStockAlerts() {
        loadWidget('lowStockAlerts', '{{ route("technician.inventory-dashboard.low-stock-alerts") }}', function(response) {
            let html = '';
            if (response.data.length === 0) {
                html = '<div class="text-center py-4"><i class="bi bi-check-circle text-success fs-3 d-block mb-2"></i><span class="text-muted">All stock levels healthy!</span></div>';
            } else {
                html = '<div class="list-group list-group-flush">';
                response.data.forEach(function(item) {
                    html += `<div class="list-group-item px-0 py-2 ${item.severity === 'critical' ? 'list-group-item-danger' : 'list-group-item-warning'}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><strong>${item.model_name}</strong><br><small class="text-muted">${item.category_name}</small></div>
                            <div class="text-end">${item.severity_badge}<br><small>Qty: ${parseFloat(item.quantity_available)} / ${parseInt(item.threshold)}</small></div>
                        </div>
                    </div>`;
                });
                html += '</div>';
            }
            $('#lowStockAlerts-content').html(html);
        });
    }

    // Movement Trend
    function loadMovementTrend() {
        loadWidget('movementTrend', '{{ route("technician.inventory-dashboard.movement-trend") }}', function(response) {
            const ctx = document.getElementById('movementTrendChart');
            if (!ctx) return;
            if (movementTrendChart) movementTrendChart.destroy();
            movementTrendChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: { labels: response.data.labels, datasets: [
                    { label: 'Received', data: response.data.inbound, backgroundColor: 'rgba(25, 135, 84, 0.7)', borderRadius: 4 },
                    { label: 'Issued', data: response.data.outbound, backgroundColor: 'rgba(220, 53, 69, 0.7)', borderRadius: 4 }
                ]},
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            });
        });
    }

    // Recent Movements
    function loadRecentMovements() {
        loadWidget('recentMovements', '{{ route("technician.inventory-dashboard.recent-movements") }}', function(response) {
            let html = '<div class="list-group list-group-flush">';
            if (response.data.length === 0) {
                html += '<div class="text-center text-muted py-3">No recent movements</div>';
            } else {
                response.data.forEach(function(item) {
                    html += `<div class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge bg-${item.type_color}"><i class="bi ${item.type_icon} me-1"></i>${item.type_label}</span>
                                <div class="mt-1"><strong>${item.model_name}</strong>
                                ${item.serial_no ? '<br><small class="text-muted">S/N: ' + item.serial_no + '</small>' : ''}</div>
                            </div>
                            <small class="text-muted text-end">${item.time_ago}</small>
                        </div>
                    </div>`;
                });
            }
            html += '</div>';
            $('#recentMovements-content').html(html);
        });
    }

    // Stock Aging
    function loadStockAging() {
        loadWidget('stockAging', '{{ route("technician.inventory-dashboard.stock-aging") }}', function(response) {
            const ctx = document.getElementById('agingChart');
            if (!ctx) return;
            if (agingChart) agingChart.destroy();
            let brackets = response.aging.brackets;
            agingChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: { labels: brackets.map(b => b.label), datasets: [{ data: brackets.map(b => b.count), backgroundColor: brackets.map(b => b.color), borderRadius: 4 }] },
                options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
            });
            $('#agingDetails').html(`<div class="text-center"><small class="text-muted">Total with me: <strong>${response.aging.total_in_stock}</strong></small></div>`);
        });
    }

    // Top Models
    function loadTopModels() {
        loadWidget('topModels', '{{ route("technician.inventory-dashboard.top-models") }}', function(response) {
            let html = '<table class="table table-sm table-hover mb-0"><thead><tr>' +
                '<th>#</th><th>Model</th><th class="text-end">On Hand</th><th class="text-end">Available</th></tr></thead><tbody>';
            if (response.data.length === 0) {
                html += '<tr><td colspan="4" class="text-center text-muted py-3">No models found</td></tr>';
            } else {
                response.data.forEach(function(item, index) {
                    html += `<tr><td>${index + 1}</td><td><strong>${item.model_name}</strong><br><small class="text-muted">${item.category_name}</small></td>
                        <td class="text-end">${parseFloat(item.total_on_hand).toLocaleString()}</td>
                        <td class="text-end fw-bold">${parseFloat(item.total_available).toLocaleString()}</td></tr>`;
                });
            }
            html += '</tbody></table>';
            $('#topModels-content').html(html);
        });
    }

    function loadAllWidgets() {
        loadStockByCategory();
        loadCategoryDistribution();
        loadLowStockAlerts();
        loadMovementTrend();
        loadRecentMovements();
        loadStockAging();
        loadTopModels();
    }

    loadAllWidgets();

    $('#btnRefreshAll').on('click', function() { loadAllWidgets(); showToast('Dashboard refreshed', 'success'); });

    $(document).on('click', '.widget-refresh-btn', function() {
        let widgetId = $(this).data('widget-id');
        let map = {
            'stockByCategory': loadStockByCategory, 'categoryChart': loadCategoryDistribution,
            'lowStockAlerts': loadLowStockAlerts, 'movementTrend': loadMovementTrend,
            'recentMovements': loadRecentMovements, 'stockAging': loadStockAging, 'topModels': loadTopModels,
        };
        if (map[widgetId]) map[widgetId]();
    });

    setInterval(loadAllWidgets, 300000);
});
</script>
@endpush
