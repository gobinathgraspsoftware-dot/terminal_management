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
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-people text-primary fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $teamStats['total_members'] }}</h4>
                        <small class="text-muted">Total Members</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-check-circle text-success fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $teamStats['active_members'] }}</h4>
                        <small class="text-muted">Active Members</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-3 me-3">
                        <i class="bi bi-briefcase text-warning fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $teamStats['pending_jobs'] }}</h4>
                        <small class="text-muted">Pending Jobs</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 bg-info bg-opacity-10 p-3 me-3">
                        <i class="bi bi-check2-all text-info fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $teamStats['completed_this_month'] }}</h4>
                        <small class="text-muted">Completed This Month</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-people me-2 text-primary"></i>Team Members</h5>
                        <select id="filterStatus" class="form-select form-select-sm" style="width: 130px;">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="teamTable" class="table table-hover align-middle mb-0" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px;"></th>
                                    <th>Name</th>
                                    <th>Employee ID</th>
                                    <th>Coverage</th>
                                    <th>Skills</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:80px;">Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="row g-4">
        <div class="col-lg-4">
            {{-- SLA Compliance --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>SLA Compliance</h6>
                </div>
                <div class="card-body text-center">
                    @php
                        $slaRate = $teamStats['sla_compliance']['rate'];
                        $slaColor = $slaRate >= 90 ? 'success' : ($slaRate >= 70 ? 'warning' : 'danger');
                    @endphp
                    <h2 class="mb-1 text-{{ $slaColor }}">{{ $slaRate }}%</h2>
                    <small class="text-muted">
                        {{ $teamStats['sla_compliance']['on_time'] }} / {{ $teamStats['sla_compliance']['total'] }} on time
                    </small>
                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar bg-{{ $slaColor }}" style="width: {{ $slaRate }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            {{-- Coverage Areas --}}
            @if(!empty($teamStats['coverage_states']))
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Coverage Areas</h6>
                    </div>
                    <div class="card-body">
                        @foreach($teamStats['coverage_states'] as $state)
                            <span class="badge bg-light text-dark border me-1 mb-1 py-2 px-2">
                                <i class="bi bi-geo-alt-fill text-primary me-1"></i>{{ $state }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="col-lg-4">
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
            { data: 'avatar', name: 'avatar', orderable: false, searchable: false, className: 'text-center pe-0' },
            { data: 'name', name: 'name' },
            { data: 'employee_id', name: 'employee_id' },
            { data: 'coverage', name: 'coverage', orderable: false, searchable: false },
            { data: 'skills', name: 'skills', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        dom: '<"row align-items-center mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            emptyTable: 'No team members found.',
            processing: '<div class="d-flex align-items-center justify-content-center py-3"><span class="spinner-border spinner-border-sm me-2"></span> Loading...</div>'
        },
        drawCallback: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    $('#filterStatus').on('change', function() { table.ajax.reload(); });

    // Weekly Performance Chart
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
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)'
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
