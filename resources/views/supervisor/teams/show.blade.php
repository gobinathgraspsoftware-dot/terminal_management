@extends('layouts.app')

@section('title', 'Team Member - ' . $user->name)

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-person-lines-fill me-2"></i>{{ $user->name }}
                <span class="badge bg-success fs-6 ms-2">Technician</span>
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
            <i class="bi bi-arrow-left me-1"></i> Back to Team
        </a>
    </div>

    <div class="row g-4">
        {{-- Profile Card --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-4">
                    @php
                        $avatarUrl = $user->avatar
                            ? asset('storage/' . $user->avatar)
                            : 'https://ui-avatars.com/api/?name=' . urlencode(substr($user->name, 0, 1)) . '&size=120&background=random&color=fff';
                    @endphp
                    <img src="{{ $avatarUrl }}" class="rounded-circle mb-3" style="width:100px;height:100px;object-fit:cover;" alt="{{ $user->name }}">

                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-2">{{ $user->employee_id ?? '-' }}</p>
                    <span class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'suspended' ? 'danger' : 'secondary') }}">{{ ucfirst($user->status) }}</span>

                    <hr>

                    <div class="text-start">
                        <div class="mb-2">
                            <i class="bi bi-envelope me-2 text-muted"></i>
                            <span>{{ $user->email }}</span>
                        </div>
                        @if($user->phone)
                        <div class="mb-2">
                            <i class="bi bi-telephone me-2 text-muted"></i>
                            <span>{{ $user->phone }}</span>
                        </div>
                        @endif
                        @if($user->state)
                        <div class="mb-2">
                            <i class="bi bi-geo-alt me-2 text-muted"></i>
                            <span>{{ $user->state->name }}{{ $user->city ? ', ' . $user->city->name : '' }}</span>
                        </div>
                        @endif

                        {{-- Coverage States --}}
                        @php
                            $states = is_array($user->coverage_states) ? $user->coverage_states : [];
                        @endphp
                        @if(!empty($states))
                        <div class="mb-2">
                            <i class="bi bi-map me-2 text-muted"></i>
                            @foreach($states as $s)
                                <span class="badge bg-light text-dark">{{ $s }}</span>
                            @endforeach
                        </div>
                        @endif

                        {{-- Skill Tags --}}
                        @php
                            $skills = is_array($user->skill_tags) ? $user->skill_tags : [];
                        @endphp
                        @if(!empty($skills))
                        <div class="mb-2">
                            <i class="bi bi-tags me-2 text-muted"></i>
                            @foreach($skills as $t)
                                <span class="badge bg-info bg-opacity-10 text-info">{{ $t }}</span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats + Chart --}}
        <div class="col-lg-8">

            {{-- Stats Cards --}}
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="card border-0 bg-primary bg-opacity-10">
                        <div class="card-body text-center py-3">
                            <div class="fs-3 fw-bold text-primary">{{ $statistics['total_jobs'] }}</div>
                            <div class="small text-muted">Total Jobs</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card border-0 bg-success bg-opacity-10">
                        <div class="card-body text-center py-3">
                            <div class="fs-3 fw-bold text-success">{{ $statistics['completed_jobs'] }}</div>
                            <div class="small text-muted">Completed</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card border-0 bg-warning bg-opacity-10">
                        <div class="card-body text-center py-3">
                            <div class="fs-3 fw-bold text-warning">{{ $statistics['pending_jobs'] }}</div>
                            <div class="small text-muted">Pending</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SLA + Monthly --}}
            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">SLA Compliance</h6>
                            @php
                                $sla = $statistics['sla_compliance'] ?? ['rate' => 100, 'on_time' => 0, 'total' => 0];
                                $slaColor = $sla['rate'] >= 90 ? 'success' : ($sla['rate'] >= 70 ? 'warning' : 'danger');
                            @endphp
                            <div class="d-flex align-items-center">
                                <div class="fs-2 fw-bold text-{{ $slaColor }} me-3">{{ $sla['rate'] }}%</div>
                                <div class="small text-muted">{{ $sla['on_time'] }} / {{ $sla['total'] }} on time</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">This Month</h6>
                            <div class="d-flex align-items-center">
                                <div class="fs-2 fw-bold text-info me-3">{{ $statistics['completed_this_month'] }}</div>
                                <div class="small text-muted">Jobs completed this month</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Weekly Performance Chart --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Weekly Performance</h6>
                </div>
                <div class="card-body">
                    <canvas id="weeklyChart" height="200"></canvas>
                </div>
            </div>

            {{-- Recent Jobs --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Jobs</h6>
                </div>
                <div class="card-body p-0">
                    @if($recentJobs->count() > 0)
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
                                @foreach($recentJobs as $job)
                                <tr>
                                    <td><code>{{ $job->job_number }}</code></td>
                                    <td>{{ $job->client_name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($job->scheduled_date)->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ match($job->status) {
                                            'completed' => 'success',
                                            'in_progress' => 'primary',
                                            'assigned' => 'info',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        } }}">{{ ucfirst(str_replace('_', ' ', $job->status)) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No recent jobs found.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(function() {
    const chartData = @json($chartData);
    const ctx = document.getElementById('weeklyChart');
    if (ctx && chartData.labels) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Completed Jobs',
                    data: chartData.data,
                    backgroundColor: 'rgba(13, 110, 253, 0.3)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }
});
</script>
@endpush
