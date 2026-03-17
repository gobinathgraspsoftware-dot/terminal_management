@extends('layouts.app')

@section('title', 'My Team - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-people-fill me-2"></i>My Team</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Team</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-primary mb-2"><i class="bi bi-people fs-3"></i></div>
                    <h3 class="mb-1">{{ $teamStats['total_members'] }}</h3>
                    <small class="text-muted">Total Members</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-success mb-2"><i class="bi bi-check-circle fs-3"></i></div>
                    <h3 class="mb-1">{{ $teamStats['active_members'] }}</h3>
                    <small class="text-muted">Active Members</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-warning mb-2"><i class="bi bi-briefcase fs-3"></i></div>
                    <h3 class="mb-1">{{ $teamStats['pending_jobs'] }}</h3>
                    <small class="text-muted">Pending Jobs</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-info mb-2"><i class="bi bi-graph-up fs-3"></i></div>
                    <h3 class="mb-1">{{ $teamStats['completed_this_month'] }}</h3>
                    <small class="text-muted">Completed This Month</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Team Members Card View --}}
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Team Members</h5>
                    <span class="badge bg-primary">{{ $teamMembers->count() }}</span>
                </div>
                <div class="card-body">
                    @if($teamMembers->count() > 0)
                        <div class="row g-3">
                            @foreach($teamMembers as $member)
                                @include('components.member-card', ['member' => $member])
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-people fs-1 opacity-50"></i>
                            <p class="mt-2">No team members assigned yet.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- DataTable view --}}
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-table me-2"></i>Team List</h5>
                    <select id="filterStatus" class="form-select form-select-sm" style="width: 140px;">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="teamTable" class="table table-hover table-striped align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Coverage</th>
                                    <th>Skills</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column - Performance --}}
        <div class="col-md-4">
            {{-- Coverage States --}}
            @if(!empty($teamStats['coverage_states']))
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Coverage Areas</h6>
                    </div>
                    <div class="card-body">
                        @foreach($teamStats['coverage_states'] as $state)
                            <span class="badge bg-light text-dark me-1 mb-1">{{ $state }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- SLA Compliance --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>SLA Compliance</h6>
                </div>
                <div class="card-body text-center">
                    <h2 class="mb-1 {{ $teamStats['sla_compliance']['rate'] >= 90 ? 'text-success' : ($teamStats['sla_compliance']['rate'] >= 70 ? 'text-warning' : 'text-danger') }}">
                        {{ $teamStats['sla_compliance']['rate'] }}%
                    </h2>
                    <small class="text-muted">{{ $teamStats['sla_compliance']['on_time'] }} / {{ $teamStats['sla_compliance']['total'] }} on time</small>
                </div>
            </div>

            {{-- Weekly Performance Chart --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Jobs This Week</h6>
                </div>
                <div class="card-body">
                    <canvas id="weeklyChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // DataTable
    var table = $('#teamTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.teams.datatable") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'employee_id', name: 'employee_id' },
            { data: 'name', name: 'name' },
            { data: 'coverage', name: 'coverage', orderable: false, searchable: false },
            { data: 'skills', name: 'skills', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        responsive: true
    });

    $('#filterStatus').on('change', function() { table.ajax.reload(); });

    // Weekly Chart
    var ctx = document.getElementById('weeklyChart');
    if (ctx) {
        var perfData = @json($teamPerformance);
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: perfData.jobs_this_week ? perfData.jobs_this_week.labels : [],
                datasets: [{
                    label: 'Jobs Completed',
                    data: perfData.jobs_this_week ? perfData.jobs_this_week.data : [],
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
@endpush
