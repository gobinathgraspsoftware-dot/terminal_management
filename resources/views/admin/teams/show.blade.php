@extends('layouts.app')

@section('title', 'Team Member - ' . $user->name . ' - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-person me-2"></i>{{ $user->name }}
                @if($user->hasRole('supervisor'))
                    <span class="badge bg-primary ms-2">Supervisor</span>
                @else
                    <span class="badge bg-success ms-2">Technician</span>
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
        <div class="d-flex gap-2">
            @if($user->hasRole('technician'))
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reassignModal">
                    <i class="bi bi-arrow-left-right me-1"></i> Reassign
                </button>
            @endif
            <a href="{{ route('admin.teams.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
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
                    @if($user->hasRole('technician') && $user->supervisor)
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Supervisor</span>
                            <a href="{{ route('admin.teams.show', $user->supervisor->id) }}">{{ $user->supervisor->name }}</a>
                        </li>
                    @endif
                    @if($user->coverage_states)
                        <li class="list-group-item">
                            <span class="text-muted d-block mb-1">Coverage States</span>
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

            {{-- Assignment History --}}
            @if($user->hasRole('technician') && !empty($assignmentHistory))
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Assignment History</h6>
                    </div>
                    <div class="card-body">
                        @foreach($assignmentHistory as $history)
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                        <i class="bi bi-arrow-left-right text-primary"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-0 small">
                                        From <strong>{{ $history['from'] }}</strong> to <strong>{{ $history['to'] }}</strong>
                                    </p>
                                    <small class="text-muted">{{ $history['date'] }} &middot; by {{ $history['changed_by'] }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Column - Statistics & Charts --}}
        <div class="col-md-8">
            {{-- Stats Cards --}}
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

            {{-- Supervisor: Team Members --}}
            @if($user->hasRole('supervisor') && $user->technicians->count() > 0)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-people me-2"></i>Team Members ({{ $user->technicians->count() }})</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Employee ID</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($user->technicians as $member)
                                        <tr>
                                            <td>{{ $member->name }}</td>
                                            <td>{{ $member->employee_id }}</td>
                                            <td>{!! $member->status_badge !!}</td>
                                            <td>
                                                <a href="{{ route('admin.teams.show', $member->id) }}" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

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

{{-- Reassign Modal --}}
@if($user->hasRole('technician'))
<div class="modal fade" id="reassignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Reassign Technician</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reassignForm">
                @csrf
                <input type="hidden" name="technician_id" value="{{ $user->id }}">
                <div class="modal-body">
                    <p>Reassigning: <strong>{{ $user->name }}</strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assign to Supervisor <span class="text-danger">*</span></label>
                        <select name="supervisor_id" class="form-select" required>
                            <option value="">-- Select Supervisor --</option>
                            @foreach($supervisors ?? [] as $supervisor)
                                <option value="{{ $supervisor->id }}"
                                    {{ $user->supervisor_id == $supervisor->id ? 'selected' : '' }}>
                                    {{ $supervisor->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">All technicians must have a supervisor assigned.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Reassign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Performance Chart
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
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    // Reassign form
    $('#reassignForm').on('submit', function(e) {
        e.preventDefault();

        var supervisorId = $(this).find('select[name="supervisor_id"]').val();
        if (!supervisorId) {
            showToast('error', 'Please select a supervisor.');
            return;
        }

        $.ajax({
            url: '{{ route("admin.teams.assign") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#reassignModal').modal('hide');
                    showToast('success', response.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast('error', response.message);
                }
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'An error occurred');
            }
        });
    });
});
</script>
@endpush
