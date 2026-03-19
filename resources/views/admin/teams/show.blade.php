@extends('layouts.app')

@section('title', 'Team Member - ' . $user->name)

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-person-lines-fill me-2"></i>{{ $user->name }}
                @if($user->hasRole('supervisor'))
                    @if($user->supervisor_type === 'internal')
                        <span class="badge bg-info fs-6 ms-2">Internal Supervisor</span>
                    @elseif($user->supervisor_type === 'external')
                        <span class="badge bg-warning text-dark fs-6 ms-2">External Supervisor</span>
                    @endif
                @elseif($user->hasRole('technician'))
                    <span class="badge bg-success fs-6 ms-2">Technician</span>
                @endif
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.teams.index') }}">Teams</a></li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.teams.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row g-4">
        {{-- User Profile Card --}}
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

                    <div class="mb-3">
                        @php $roleName = $user->roles->first()?->name ?? 'unknown'; @endphp
                        <span class="badge bg-{{ $roleName === 'supervisor' ? 'primary' : ($roleName === 'admin' ? 'danger' : 'success') }} me-1">{{ ucfirst($roleName) }}</span>
                        @if($user->hasRole('supervisor') && $user->supervisor_type)
                            <span class="badge bg-{{ $user->supervisor_type === 'internal' ? 'info' : 'warning' }}">{{ ucfirst($user->supervisor_type) }}</span>
                        @endif
                        <span class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'suspended' ? 'danger' : 'secondary') }}">{{ ucfirst($user->status) }}</span>
                    </div>

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

                        {{-- Supervisor info for technicians --}}
                        @if($user->hasRole('technician') && $user->supervisor)
                        <div class="mb-2">
                            <i class="bi bi-person-badge me-2 text-muted"></i>
                            <span>Supervisor: <strong>{{ $user->supervisor->name }}</strong></span>
                            @if($user->supervisor->supervisor_type)
                                <span class="badge bg-{{ $user->supervisor->supervisor_type === 'internal' ? 'info' : 'warning' }} ms-1" style="font-size:0.7em;">{{ ucfirst($user->supervisor->supervisor_type) }}</span>
                            @endif
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

                    {{-- Reassign button for technicians --}}
                    @if($user->hasRole('technician'))
                    <hr>
                    <button class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#reassignModal">
                        <i class="bi bi-arrow-left-right me-1"></i> Reassign Supervisor
                    </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Statistics + Charts --}}
        <div class="col-lg-8">
            {{-- External Supervisor Notice --}}
            @if($user->hasRole('supervisor') && $user->isExternalSupervisor())
            <div class="alert alert-warning d-flex align-items-center mb-3">
                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                <div>
                    <strong>External Supervisor</strong> — This supervisor operates independently without a technician team.
                    Jobs are assigned directly to this supervisor.
                </div>
            </div>
            @endif

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

            {{-- SLA & Monthly Stats --}}
            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">SLA Compliance</h6>
                            <div class="d-flex align-items-center">
                                <div class="fs-2 fw-bold me-3 {{ $statistics['sla_compliance']['rate'] >= 90 ? 'text-success' : ($statistics['sla_compliance']['rate'] >= 70 ? 'text-warning' : 'text-danger') }}">
                                    {{ $statistics['sla_compliance']['rate'] }}%
                                </div>
                                <div class="small text-muted">
                                    {{ $statistics['sla_compliance']['on_time'] }} / {{ $statistics['sla_compliance']['total'] }} on time
                                </div>
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

            {{-- Team Members (for internal supervisors only) --}}
            @if($user->hasRole('supervisor') && $user->isInternalSupervisor())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                    <h6 class="mb-0"><i class="bi bi-people me-2"></i>Team Members</h6>
                    <span class="badge bg-primary">{{ $user->technicians->count() }} members</span>
                </div>
                <div class="card-body p-0">
                    @if($user->technicians->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Employee ID</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->technicians as $tech)
                                <tr>
                                    <td>
                                        @php
                                            $techAvatar = $tech->avatar
                                                ? asset('storage/' . $tech->avatar)
                                                : 'https://ui-avatars.com/api/?name=' . urlencode(substr($tech->name, 0, 1)) . '&size=30&background=random&color=fff';
                                        @endphp
                                        <img src="{{ $techAvatar }}" class="rounded-circle me-2" style="width:30px;height:30px;object-fit:cover;">
                                        {{ $tech->name }}
                                    </td>
                                    <td>{{ $tech->employee_id ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $tech->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($tech->status) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.teams.show', $tech->id) }}" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                        No technicians assigned to this supervisor yet.
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Recent Jobs --}}
            <div class="card border-0 shadow-sm mb-4">
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

            {{-- Assignment History --}}
            @if($user->hasRole('technician') && !empty($assignmentHistory))
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Assignment History</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Changed By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assignmentHistory as $history)
                                <tr>
                                    <td>{{ $history['date'] }}</td>
                                    <td>{{ $history['from'] }}</td>
                                    <td>{{ $history['to'] }}</td>
                                    <td>{{ $history['changed_by'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Reassign Modal (for technicians) --}}
    @if($user->hasRole('technician'))
    <div class="modal fade" id="reassignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Reassign Supervisor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Technician</label>
                        <input type="text" class="form-control" value="{{ $user->name }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assign to Supervisor <span class="text-danger">*</span></label>
                        <select id="reassignSupervisorId" class="form-select">
                            <option value="">Select Internal Supervisor...</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}" {{ $user->supervisor_id == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-info">
                            <i class="bi bi-info-circle me-1"></i>Only internal supervisors are listed.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="btnReassign" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Reassign
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Weekly Performance Chart
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

    // Reassign handler
    @if($user->hasRole('technician'))
    $('#btnReassign').on('click', function() {
        const supId = $('#reassignSupervisorId').val();
        if (!supId) {
            Swal.fire('Error', 'Please select an internal supervisor.', 'warning');
            return;
        }
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Reassigning...');

        $.ajax({
            url: '{{ route("admin.teams.assign") }}',
            method: 'POST',
            data: {
                technician_id: {{ $user->id }},
                supervisor_id: supId,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if (res.success) {
                    Swal.fire('Success', res.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Reassignment failed', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Reassign');
            }
        });
    });
    @endif
});
</script>
@endpush
