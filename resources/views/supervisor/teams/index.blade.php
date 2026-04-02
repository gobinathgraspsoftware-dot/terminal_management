@extends('layouts.app')

@section('title', $isInternal ? 'My Team' : 'My Overview')

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1">
                @if($isInternal)
                    <i class="bi bi-people-fill me-2"></i>My Team
                @else
                    <i class="bi bi-person-badge me-2"></i>My Overview
                    <span class="badge bg-warning text-dark fs-6 ms-2">External</span>
                @endif
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">{{ $isInternal ? 'My Team' : 'My Overview' }}</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- External Supervisor Notice --}}
    @if($isExternal)
    <div class="alert alert-info d-flex align-items-start mb-4">
        <i class="bi bi-info-circle-fill me-2 fs-5 mt-1"></i>
        <div>
            <strong>External Supervisor</strong> — You operate independently without a technician team.
            Jobs are assigned directly to you. The statistics below show your own job performance.
        </div>
    </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        @if($isInternal)
        {{-- Internal: Team member count --}}
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-primary bg-opacity-10 me-3">
                            <i class="bi bi-people fs-4 text-primary"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Team Members</div>
                            <h4 class="mb-0">{{ $teamStats['total_members'] ?? 0 }}</h4>
                            <div class="small text-success">{{ $teamStats['active_members'] ?? 0 }} active</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <div class="{{ $isInternal ? 'col-xl-3' : 'col-xl-4' }} col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-info bg-opacity-10 me-3">
                            <i class="bi bi-calendar-day fs-4 text-info"></i>
                        </div>
                        <div>
                            <div class="text-muted small">{{ $isInternal ? "Today's Jobs" : "My Jobs Today" }}</div>
                            <h4 class="mb-0">{{ $teamStats['todays_jobs'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="{{ $isInternal ? 'col-xl-3' : 'col-xl-4' }} col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-warning bg-opacity-10 me-3">
                            <i class="bi bi-hourglass-split fs-4 text-warning"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Pending Jobs</div>
                            <h4 class="mb-0">{{ $teamStats['pending_jobs'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="{{ $isInternal ? 'col-xl-3' : 'col-xl-4' }} col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-success bg-opacity-10 me-3">
                            <i class="bi bi-check-circle fs-4 text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Completed This Month</div>
                            <h4 class="mb-0">{{ $teamStats['completed_this_month'] ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SLA Compliance --}}
    <div class="row g-3 mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><i class="bi bi-speedometer2 me-2"></i>SLA Compliance</h6>
                    @php
                        $sla = $teamStats['sla_compliance'] ?? ['rate' => 100, 'on_time' => 0, 'total' => 0];
                        $slaColor = $sla['rate'] >= 90 ? 'success' : ($sla['rate'] >= 70 ? 'warning' : 'danger');
                    @endphp
                    <div class="d-flex align-items-center mb-2">
                        <div class="fs-1 fw-bold text-{{ $slaColor }} me-3">{{ $sla['rate'] }}%</div>
                        <div class="small text-muted">
                            {{ $sla['on_time'] }} / {{ $sla['total'] }} jobs completed on time
                        </div>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-{{ $slaColor }}" style="width: {{ $sla['rate'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Weekly Performance Chart --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h6 class="mb-0">
                <i class="bi bi-graph-up me-2"></i>
                {{ $isInternal ? 'Team Performance (Last 7 Days)' : 'My Performance (Last 7 Days)' }}
            </h6>
        </div>
        <div class="card-body">
            <canvas id="performanceChart" height="200"></canvas>
        </div>
    </div>

    {{-- Team Members DataTable (Internal Supervisors Only) --}}
    @if($isInternal)
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
            <h6 class="mb-0"><i class="bi bi-people me-2"></i>Team Members</h6>
            <div class="d-flex gap-2">
                <select id="filterStatus" class="form-select form-select-sm" style="width:auto;">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="teamTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="50"></th>
                            <th>Name</th>
                            <th>Employee ID</th>
                            <th>Email</th>
                            <th>Skills</th>
                            <th>Status</th>
                            <th width="80" class="text-center">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Performance Chart
    const perfData = @json($teamPerformance['jobs_this_week'] ?? ['labels' => [], 'data' => []]);
    const ctx = document.getElementById('performanceChart');
    if (ctx && perfData.labels) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: perfData.labels,
                datasets: [{
                    label: 'Completed Jobs',
                    data: perfData.data,
                    borderColor: 'rgba(13, 110, 253, 1)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(13, 110, 253, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
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

    @if($isInternal)
    // Team Members DataTable
    const table = $('#teamTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.teams.datatable") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'avatar', name: 'avatar', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'employee_id', name: 'employee_id' },
            { data: 'email', name: 'email' },
            { data: 'skills', name: 'skills', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' },
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        language: { emptyTable: 'No team members found' },
        drawCallback: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    $('#filterStatus').on('change', function() {
        table.ajax.reload();
    });
    @endif
});
</script>
@endpush
