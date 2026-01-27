@extends('layouts.app')

@section('title', 'Technician Dashboard - TMS')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1><i class="bi bi-briefcase me-2"></i> My Dashboard</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
        </div>
        <div>
            <button class="btn btn-primary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-2"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row g-3 mb-4">
    <div class="col-lg-4 col-md-6">
        <x-dashboard-widget
            icon="calendar-check"
            iconBg="primary"
            :value="$stats['today_jobs']"
            label="Jobs Today"
            link="#"
            :refreshable="true"
            widgetId="tech_today_jobs"
        />
    </div>

    <div class="col-lg-4 col-md-6">
        <x-dashboard-widget
            icon="hourglass-split"
            iconBg="warning"
            :value="$stats['jobs_by_status']['in_progress'] ?? 0"
            label="Jobs In Progress"
            link="#"
        />
    </div>

    <div class="col-lg-4 col-md-6">
        <x-dashboard-widget
            icon="check-circle"
            iconBg="success"
            :value="$stats['jobs_by_status']['completed'] ?? 0"
            label="Completed Jobs"
            link="#"
        />
    </div>
</div>

<!-- Commission Summary -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-cash-coin me-2"></i> Commission Summary
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small text-uppercase mb-2">This Month</div>
                            <div class="h3 text-success mb-0">
                                RM {{ number_format($stats['commission_summary']['this_month'], 2) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small text-uppercase mb-2">Pending Payout</div>
                            <div class="h3 text-warning mb-0">
                                RM {{ number_format($stats['commission_summary']['pending'], 2) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small text-uppercase mb-2">Paid (YTD)</div>
                            <div class="h3 text-primary mb-0">
                                RM {{ number_format($stats['commission_summary']['paid_ytd'], 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Today's Jobs -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check me-2"></i> Today's Jobs</span>
                <span class="badge bg-primary">{{ count($today_jobs_list) }}</span>
            </div>
            <div class="card-body">
                @forelse($today_jobs_list as $job)
                    <!-- Job Card -->
                    <div class="job-card mb-3 p-3 border rounded {{ $job->priority === 'high' ? 'border-danger' : ($job->priority === 'medium' ? 'border-warning' : 'border-secondary') }}">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Job Header -->
                                <div class="d-flex align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <strong>{{ $job->job_number }}</strong>
                                            @if($job->priority === 'high')
                                                <span class="badge bg-danger ms-2">HIGH PRIORITY</span>
                                            @elseif($job->priority === 'medium')
                                                <span class="badge bg-warning ms-2">MEDIUM</span>
                                            @endif
                                        </h6>
                                        <div class="text-muted small">
                                            <i class="bi bi-building me-1"></i> {{ $job->client_name }}
                                            @if($job->site_name)
                                                • {{ $job->site_name }}
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <span class="badge bg-{{ 
                                            $job->status === 'assigned' ? 'info' : 
                                            ($job->status === 'in_progress' ? 'primary' : 
                                            ($job->status === 'completed' ? 'success' : 'secondary'))
                                        }}">
                                            {{ strtoupper(str_replace('_', ' ', $job->status)) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Job Details -->
                                <div class="small mb-2">
                                    <div class="mb-1">
                                        <i class="bi bi-tag me-1 text-muted"></i>
                                        <strong>Type:</strong> {{ ucfirst($job->job_type) }}
                                    </div>
                                    @if($job->site_address)
                                        <div class="mb-1">
                                            <i class="bi bi-geo-alt me-1 text-muted"></i>
                                            <strong>Location:</strong> {{ $job->site_address }}
                                        </div>
                                    @endif
                                    @if($job->scheduled_time)
                                        <div class="mb-1">
                                            <i class="bi bi-clock me-1 text-muted"></i>
                                            <strong>Time:</strong> {{ \Carbon\Carbon::parse($job->scheduled_time)->format('h:i A') }}
                                        </div>
                                    @endif
                                </div>

                                <!-- SLA Indicator -->
                                @if($job->sla_deadline)
                                    @php
                                        $deadline = \Carbon\Carbon::parse($job->sla_deadline);
                                        $now = \Carbon\Carbon::now();
                                        $hoursLeft = $now->diffInHours($deadline, false);
                                    @endphp
                                    <div class="small">
                                        <i class="bi bi-alarm me-1 text-{{ $hoursLeft < 2 ? 'danger' : ($hoursLeft < 4 ? 'warning' : 'success') }}"></i>
                                        <strong>SLA:</strong>
                                        <span class="text-{{ $hoursLeft < 2 ? 'danger' : ($hoursLeft < 4 ? 'warning' : 'success') }}">
                                            {{ $hoursLeft > 0 ? $hoursLeft . ' hours left' : 'OVERDUE' }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-4 d-flex align-items-center justify-content-end">
                                <div class="d-grid gap-2 w-100">
                                    @if($job->status === 'assigned')
                                        <a href="#" class="btn btn-success btn-sm">
                                            <i class="bi bi-play-fill me-1"></i> Start Job
                                        </a>
                                    @elseif($job->status === 'in_progress')
                                        <a href="#" class="btn btn-primary btn-sm">
                                            <i class="bi bi-arrow-right-circle me-1"></i> Continue
                                        </a>
                                    @endif
                                    <a href="#" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-calendar-x fs-1"></i>
                        <p class="mt-3">No jobs scheduled for today</p>
                        <a href="#" class="btn btn-outline-primary">
                            <i class="bi bi-clock-history me-2"></i> View All Jobs
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Recent Jobs -->
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i> Recently Completed Jobs
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Job Number</th>
                                <th>Client</th>
                                <th>Site</th>
                                <th>Type</th>
                                <th>Completed</th>
                                <th>Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_jobs as $job)
                                <tr>
                                    <td>
                                        <strong>{{ $job->job_number }}</strong>
                                    </td>
                                    <td>{{ $job->client_name }}</td>
                                    <td>{{ $job->site_name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($job->job_type) }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($job->completed_at)->diffForHumans() }}
                                        </small>
                                    </td>
                                    <td>
                                        <strong class="text-success">
                                            RM {{ number_format($job->commission_amount ?? 0, 2) }}
                                        </strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No recent jobs
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
@endsection

@push('styles')
<style>
    /* Mobile-optimized styles */
    .job-card {
        transition: all 0.2s;
        background: white;
    }

    .job-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .job-card {
            margin-bottom: 1rem !important;
        }

        .job-card .row > div:last-child {
            margin-top: 1rem;
        }

        .stats-card {
            margin-bottom: 1rem;
        }

        .page-header h1 {
            font-size: 1.5rem;
        }

        .card-header {
            font-size: 0.95rem;
        }
    }

    /* Pull to refresh indicator */
    .pull-to-refresh {
        text-align: center;
        padding: 20px;
        color: #666;
        display: none;
    }

    .pull-to-refresh.show {
        display: block;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Mobile: Pull to refresh functionality
    let touchStartY = 0;
    let touchEndY = 0;

    $(window).on('touchstart', function(e) {
        touchStartY = e.originalEvent.touches[0].clientY;
    });

    $(window).on('touchend', function(e) {
        touchEndY = e.originalEvent.changedTouches[0].clientY;
        
        // If pulled down from top
        if (touchEndY - touchStartY > 100 && $(window).scrollTop() === 0) {
            $('.pull-to-refresh').addClass('show');
            setTimeout(function() {
                location.reload();
            }, 500);
        }
    });

    // Auto-refresh every 3 minutes for technicians (more frequent than admin)
    setInterval(function() {
        // Only auto-refresh if not viewing a specific job
        if (window.location.pathname.includes('/dashboard')) {
            location.reload();
        }
    }, 180000); // 3 minutes

    // Add notification badge animation
    function animateBadge() {
        $('.badge').addClass('animate__animated animate__pulse');
        setTimeout(function() {
            $('.badge').removeClass('animate__animated animate__pulse');
        }, 1000);
    }

    // Animate badge on page load if there are pending jobs
    @if(count($today_jobs_list) > 0)
        setTimeout(animateBadge, 1000);
    @endif

    // SLA countdown timer (update every minute)
    function updateSLACountdowns() {
        $('.job-card').each(function() {
            const deadline = $(this).data('sla-deadline');
            if (deadline) {
                const now = new Date();
                const deadlineDate = new Date(deadline);
                const hoursLeft = Math.floor((deadlineDate - now) / (1000 * 60 * 60));
                
                const slaElement = $(this).find('.sla-countdown');
                if (slaElement.length) {
                    if (hoursLeft < 0) {
                        slaElement.html('<span class="text-danger">OVERDUE</span>');
                    } else {
                        slaElement.html(hoursLeft + ' hours left');
                    }
                }
            }
        });
    }

    // Update SLA countdowns every minute
    setInterval(updateSLACountdowns, 60000);
});
</script>
@endpush