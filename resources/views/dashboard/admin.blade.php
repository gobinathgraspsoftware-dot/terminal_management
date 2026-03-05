@extends('layouts.app')

@section('title', 'Admin Dashboard - TMS')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-speedometer2 me-2"></i> Admin Dashboard</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
        </div>
        <div>
            <span class="text-muted">Last updated: <span id="lastUpdated">{{ now()->format('H:i') }}</span></span>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.purchase-orders.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> Create PO
                    </a>
                    <a href="{{ route('admin.grns.create') }}" class="btn btn-success">
                        <i class="bi bi-receipt me-2"></i> Create GRN
                    </a>
                    <a href="{{ route('admin.inventory-serials.create') }}" class="btn btn-info">
                        <i class="bi bi-box-seam me-2"></i> Add Stock
                    </a>
                    <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i> View Pending POs
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Row 1 - Job Stats -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <x-dashboard-widget
            icon="clipboard-check"
            iconBg="primary"
            :value="$stats['pending_jobs']"
            label="Pending Jobs"
            :refreshable="true"
            widgetId="pending_jobs"
        />
    </div>

    <div class="col-lg-3 col-md-6">
        <x-dashboard-widget
            icon="calendar-check"
            iconBg="success"
            :value="array_sum($stats['today_jobs'])"
            label="Today's Jobs"
            :refreshable="true"
            widgetId="today_jobs"
        />
    </div>

    <div class="col-lg-3 col-md-6">
        <x-dashboard-widget
            icon="exclamation-triangle"
            iconBg="danger"
            :value="$stats['sla_breaches']"
            label="SLA Breaches"
            :refreshable="true"
            widgetId="sla_breaches"
        />
    </div>

    <div class="col-lg-3 col-md-6">
        <x-dashboard-widget
            icon="box-seam"
            iconBg="warning"
            :value="$stats['low_stock_items']"
            label="Low Stock Alerts"
            :link="route('admin.inventory-dashboard.index')"
        />
    </div>
</div>

<!-- Stats Row 2 - Pending Approvals -->
<div class="row g-3 mb-4">
    <div class="col-lg-4 col-md-6">
        <x-dashboard-widget
            title="Purchase Order Approvals"
            icon="cart-check"
            iconBg="info"
            :value="$stats['pending_po_approvals']"
            label="Pending PO Approvals"
            :link="route('admin.purchase-orders.index')"
        />
    </div>

    <div class="col-lg-4 col-md-6">
        <x-dashboard-widget
            title="Claim Approvals"
            icon="file-earmark-check"
            iconBg="warning"
            :value="$stats['pending_claim_approvals']"
            label="Pending Claim Approvals"
        />
    </div>

    <div class="col-lg-4 col-md-6">
        <x-dashboard-widget
            title="Payout Approvals"
            icon="wallet2"
            iconBg="success"
            :value="$stats['pending_payout_approvals']"
            label="Pending Payout Approvals"
        />
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <!-- Jobs This Week Chart -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-bar-chart me-2"></i> Jobs This Week
            </div>
            <div class="card-body">
                <canvas id="jobsThisWeekChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Revenue This Month Chart -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-graph-up me-2"></i> Revenue This Month
            </div>
            <div class="card-body">
                <canvas id="revenueThisMonthChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Today's Jobs Breakdown -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pie-chart me-2"></i> Today's Jobs by Status
            </div>
            <div class="card-body">
                @if(count($stats['today_jobs']) > 0)
                    <canvas id="todayJobsStatusChart" height="250"></canvas>
                @else
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-3">No jobs created today</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-activity me-2"></i> Recent Activities</span>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($recent_activities as $activity)
                        <div class="list-group-item">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 35px; height: 35px;">
                                        <i class="bi bi-person"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-semibold">{{ $activity->user_name }}</div>
                                    <small class="text-muted">{{ $activity->description }}</small>
                                    <div>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            {{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">
                            No recent activities
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Health (Optional) -->
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-heart-pulse me-2"></i> System Health
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="text-success fs-2">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div class="fw-semibold mt-2">Database</div>
                            <small class="text-muted">Connected</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="text-success fs-2">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div class="fw-semibold mt-2">Storage</div>
                            <small class="text-muted">65% Used</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="text-success fs-2">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div class="fw-semibold mt-2">Cache</div>
                            <small class="text-muted">Operational</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="text-success fs-2">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div class="fw-semibold mt-2">Queue</div>
                            <small class="text-muted">0 pending</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Jobs This Week Chart
    const jobsWeekCtx = document.getElementById('jobsThisWeekChart').getContext('2d');
    new Chart(jobsWeekCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($charts['jobs_this_week']['labels']) !!},
            datasets: [{
                label: 'Jobs Created',
                data: {!! json_encode($charts['jobs_this_week']['data']) !!},
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Revenue This Month Chart
    const revenueMonthCtx = document.getElementById('revenueThisMonthChart').getContext('2d');
    new Chart(revenueMonthCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($charts['revenue_this_month']['labels']) !!},
            datasets: [{
                label: 'Revenue (RM)',
                data: {!! json_encode($charts['revenue_this_month']['data']) !!},
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
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

    @if(count($stats['today_jobs']) > 0)
    // Today's Jobs Status Chart
    const todayJobsCtx = document.getElementById('todayJobsStatusChart').getContext('2d');
    const todayJobsData = @json($stats['today_jobs']);
    
    new Chart(todayJobsCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(todayJobsData).map(key => key.replace('_', ' ').toUpperCase()),
            datasets: [{
                data: Object.values(todayJobsData),
                backgroundColor: [
                    'rgba(102, 126, 234, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(251, 191, 36, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(156, 163, 175, 0.8)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    @endif

    // Auto-refresh dashboard every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000); // 5 minutes

    // Update last updated time
    function updateTime() {
        const now = new Date();
        $('#lastUpdated').text(now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit' 
        }));
    }
    setInterval(updateTime, 60000); // Update every minute
});
</script>
@endpush