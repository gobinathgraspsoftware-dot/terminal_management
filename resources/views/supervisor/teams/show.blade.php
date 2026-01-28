@extends('layouts.supervisor')

@section('title', 'Team Member - ' . $user->name)

@section('page-header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h4 class="mb-1">Team Member Details</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('supervisor.teams.index') }}">My Team</a></li>
                <li class="breadcrumb-item active">{{ $user->name }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('supervisor.teams.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to Team
    </a>
</div>
@endsection

@section('content')
<div class="row">
    {{-- Profile Card --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center">
                <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}" 
                    class="rounded-circle mb-3 border border-4 border-primary" 
                    style="width: 120px; height: 120px; object-fit: cover;">
                <h4 class="mb-1">{{ $user->name }}</h4>
                <p class="text-muted mb-2">{{ $user->employee_id }}</p>
                
                @php
                    $statusClass = match($user->status) {
                        'active' => 'success',
                        'inactive' => 'secondary',
                        'suspended' => 'danger',
                        default => 'secondary'
                    };
                @endphp
                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($user->status) }}</span>
                
                <hr class="my-3">
                
                <div class="text-start">
                    <div class="mb-3">
                        <small class="text-muted d-block"><i class="fas fa-envelope me-1"></i> Email</small>
                        <span>{{ $user->email }}</span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block"><i class="fas fa-phone me-1"></i> Phone</small>
                        <span>{{ $user->phone ?? '-' }}</span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block"><i class="fas fa-map-marker-alt me-1"></i> Coverage Areas</small>
                        @if($user->coverage_states && count($user->coverage_states) > 0)
                            @foreach($user->coverage_states as $state)
                                <span class="badge bg-light text-dark me-1 mb-1">{{ $state }}</span>
                            @endforeach
                        @else
                            <span class="text-muted">Not assigned</span>
                        @endif
                    </div>
                    <div>
                        <small class="text-muted d-block"><i class="fas fa-tools me-1"></i> Skills</small>
                        @if($user->skill_tags && count($user->skill_tags) > 0)
                            @foreach($user->skill_tags as $skill)
                                <span class="badge bg-info me-1 mb-1">{{ $skill }}</span>
                            @endforeach
                        @else
                            <span class="text-muted">Not tagged</span>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Quick Contact --}}
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-center gap-2">
                    @if($user->phone)
                        <a href="tel:{{ $user->phone }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-phone"></i>
                        </a>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $user->phone) }}" 
                            target="_blank" class="btn btn-outline-success btn-sm">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    @endif
                    <a href="mailto:{{ $user->email }}" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics & Performance --}}
    <div class="col-lg-8">
        {{-- Stats Cards --}}
        <div class="row mb-4">
            <div class="col-md-4 col-6 mb-3">
                <div class="card h-100 bg-primary bg-opacity-10">
                    <div class="card-body text-center">
                        <h3 class="text-primary mb-1">{{ $statistics['total_jobs'] }}</h3>
                        <small class="text-muted">Total Jobs</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="card h-100 bg-success bg-opacity-10">
                    <div class="card-body text-center">
                        <h3 class="text-success mb-1">{{ $statistics['completed_jobs'] }}</h3>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="card h-100 bg-warning bg-opacity-10">
                    <div class="card-body text-center">
                        <h3 class="text-warning mb-1">{{ $statistics['pending_jobs'] }}</h3>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="card h-100 bg-info bg-opacity-10">
                    <div class="card-body text-center">
                        <h3 class="text-info mb-1">{{ $statistics['completed_this_month'] }}</h3>
                        <small class="text-muted">This Month</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="card h-100 bg-dark bg-opacity-10">
                    <div class="card-body text-center">
                        <h3 class="mb-1">RM {{ $statistics['commission_this_month'] }}</h3>
                        <small class="text-muted">Commission MTD</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="card h-100 {{ $statistics['sla_compliance']['rate'] >= 90 ? 'bg-success' : 'bg-danger' }} bg-opacity-10">
                    <div class="card-body text-center">
                        <h3 class="{{ $statistics['sla_compliance']['rate'] >= 90 ? 'text-success' : 'text-danger' }} mb-1">
                            {{ $statistics['sla_compliance']['rate'] }}%
                        </h3>
                        <small class="text-muted">SLA Compliance</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Weekly Performance Chart --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Weekly Performance</h6>
            </div>
            <div class="card-body">
                <canvas id="weeklyChart" height="100"></canvas>
            </div>
        </div>

        {{-- Recent Jobs --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-clipboard-list me-2 text-info"></i>Recent Jobs</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Job Number</th>
                                <th>Client</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentJobs ?? [] as $job)
                            <tr>
                                <td>
                                    <span class="fw-medium">{{ $job->job_number }}</span>
                                </td>
                                <td>{{ $job->client_name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $job->job_type }}</span>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($job->status) {
                                            'completed' => 'success',
                                            'in_progress' => 'primary',
                                            'assigned' => 'info',
                                            'pending_assignment' => 'warning',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                                    </span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($job->scheduled_date)->format('d M Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-clipboard fa-2x mb-2 opacity-50"></i>
                                    <p class="mb-0">No jobs found</p>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    // Weekly Performance Chart
    new Chart(document.getElementById('weeklyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartData['labels'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) !!},
            datasets: [{
                label: 'Jobs Completed',
                data: {!! json_encode($chartData['data'] ?? [0, 0, 0, 0, 0, 0, 0]) !!},
                backgroundColor: 'rgba(13, 110, 253, 0.8)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
@endpush
