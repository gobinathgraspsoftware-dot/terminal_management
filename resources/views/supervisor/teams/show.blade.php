@extends('layouts.app')

@section('title', 'Team Member - ' . $user->name . ' - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-person me-2"></i>{{ $user->name }}
                <span class="badge bg-success ms-2">Technician</span>
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.teams.index') }}">My Team</a></li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('supervisor.teams.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row g-4">
        {{-- Left Column - Profile --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                         class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                    <h5>{{ $user->name }}</h5>
                    <p class="text-muted">{{ $user->employee_id }}</p>
                    <p>{!! $user->status_badge !!}</p>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Email</span>
                        <span>{{ $user->email }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Phone</span>
                        <span>{{ $user->phone ?? '-' }}</span>
                    </li>
                    @if($user->coverage_states)
                        <li class="list-group-item">
                            <span class="text-muted d-block mb-1">Coverage</span>
                            @foreach($user->coverage_states as $state)
                                <span class="badge bg-light text-dark me-1">{{ $state }}</span>
                            @endforeach
                        </li>
                    @endif
                    @if($user->skill_tags)
                        <li class="list-group-item">
                            <span class="text-muted d-block mb-1">Skills</span>
                            @foreach($user->skill_tags as $tag)
                                <span class="badge bg-info text-dark me-1">{{ $tag }}</span>
                            @endforeach
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="col-md-8">
            {{-- Stats --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body py-3">
                            <h4 class="mb-0 text-primary">{{ $statistics['total_jobs'] }}</h4>
                            <small class="text-muted">Total Jobs</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body py-3">
                            <h4 class="mb-0 text-success">{{ $statistics['completed_jobs'] }}</h4>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body py-3">
                            <h4 class="mb-0 text-warning">{{ $statistics['pending_jobs'] }}</h4>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body py-3">
                            <h4 class="mb-0 text-info">{{ $statistics['sla_compliance']['rate'] }}%</h4>
                            <small class="text-muted">SLA Rate</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Chart --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Weekly Performance</h6>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" height="200"></canvas>
                </div>
            </div>

            {{-- Recent Jobs --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-briefcase me-2"></i>Recent Jobs</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Job #</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentJobs as $job)
                                    <tr>
                                        <td>{{ $job->job_number }}</td>
                                        <td>{{ $job->client_name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($job->scheduled_date)->format('d M Y') }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $job->status)) }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No jobs found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
    var ctx = document.getElementById('performanceChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartData['labels']) !!},
                datasets: [{
                    label: 'Completed Jobs',
                    data: {!! json_encode($chartData['data']) !!},
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
@endpush
