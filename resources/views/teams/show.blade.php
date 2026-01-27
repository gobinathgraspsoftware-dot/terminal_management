@extends('layouts.app')

@section('title', $user->name . ' - Team Member Details')

@section('content')
<div class="container-fluid">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="{{ auth()->user()->hasRole('admin') ? route('teams.admin.index') : route('teams.index') }}" 
           class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Teams
        </a>
    </div>

    <!-- User Profile Card -->
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <!-- Avatar -->
                    <div class="mb-3">
                        @if($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" 
                                 class="rounded-circle" 
                                 width="120" 
                                 height="120" 
                                 alt="Avatar">
                        @else
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                                 style="width: 120px; height: 120px; font-size: 3rem;">
                                <strong>{{ substr($user->name, 0, 2) }}</strong>
                            </div>
                        @endif
                    </div>

                    <!-- User Info -->
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <p class="text-muted mb-2">{{ $user->employee_id }}</p>
                    <span class="badge bg-primary mb-3">{{ ucfirst($user->roles->first()->name ?? 'N/A') }}</span>

                    <!-- Status Badge -->
                    <div class="mb-3">
                        @if($user->is_active)
                            <span class="badge bg-success fs-6">
                                <i class="fas fa-check-circle"></i> Active
                            </span>
                        @else
                            <span class="badge bg-secondary fs-6">
                                <i class="fas fa-times-circle"></i> Inactive
                            </span>
                        @endif
                    </div>

                    <!-- Contact Info -->
                    <div class="text-start mt-4">
                        <h6 class="text-muted mb-3">Contact Information</h6>
                        <p class="mb-2">
                            <i class="fas fa-envelope text-primary me-2"></i>
                            <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                        </p>
                        @if($user->phone)
                            <p class="mb-2">
                                <i class="fas fa-phone text-primary me-2"></i>
                                <a href="tel:{{ $user->phone }}">{{ $user->phone }}</a>
                            </p>
                        @endif
                    </div>

                    <!-- Supervisor Info -->
                    @if($user->supervisor)
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="text-muted mb-2">Supervisor</h6>
                            <div class="d-flex align-items-center justify-content-center">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                     style="width: 30px; height: 30px; font-size: 12px;">
                                    <strong>{{ substr($user->supervisor->name, 0, 2) }}</strong>
                                </div>
                                <span>{{ $user->supervisor->name }}</span>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 pt-3 border-top">
                            <span class="badge bg-warning">
                                <i class="fas fa-user-circle"></i> Independent Technician
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions (Admin Only) -->
            @can('manageTeams', App\Models\User::class)
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-cog me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="button" 
                                    class="btn btn-outline-primary" 
                                    onclick="reassignTechnician({{ $user->id }}, '{{ $user->name }}')">
                                <i class="fas fa-exchange-alt"></i> Reassign Technician
                            </button>
                            @if($user->supervisor_id)
                                <button type="button" 
                                        class="btn btn-outline-warning" 
                                        onclick="removeFromTeam({{ $user->id }}, '{{ $user->name }}')">
                                    <i class="fas fa-user-slash"></i> Make Independent
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endcan
        </div>

        <div class="col-lg-8">
            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-briefcase fa-2x text-primary mb-2"></i>
                            <h3 class="mb-0">{{ $stats['total_jobs'] }}</h3>
                            <small class="text-muted">Total Jobs</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-tasks fa-2x text-warning mb-2"></i>
                            <h3 class="mb-0">{{ $stats['active_jobs'] }}</h3>
                            <small class="text-muted">Active Jobs</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <h3 class="mb-0">{{ $stats['completed_jobs'] }}</h3>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-percentage fa-2x text-info mb-2"></i>
                            <h3 class="mb-0">{{ $stats['completion_rate'] }}%</h3>
                            <small class="text-muted">Completion Rate</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Job Orders -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Recent Job Orders
                    </h5>
                </div>
                <div class="card-body">
                    @if($user->jobOrders->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No job orders yet</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Job #</th>
                                        <th>Customer</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($user->jobOrders->take(10) as $job)
                                        <tr>
                                            <td>
                                                <strong>#{{ $job->job_number }}</strong>
                                            </td>
                                            <td>{{ $job->customer_name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ ucfirst($job->job_type ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td>
                                                @switch($job->status)
                                                    @case('completed')
                                                        <span class="badge bg-success">Completed</span>
                                                        @break
                                                    @case('in_progress')
                                                        <span class="badge bg-warning">In Progress</span>
                                                        @break
                                                    @case('pending')
                                                        <span class="badge bg-secondary">Pending</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-light text-dark">{{ $job->status }}</span>
                                                @endswitch
                                            </td>
                                            <td>{{ $job->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Activity Log -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    @if($user->activityLogs->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent activity</p>
                        </div>
                    @else
                        <div class="timeline">
                            @foreach($user->activityLogs->take(10) as $activity)
                                <div class="timeline-item mb-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px;">
                                                <i class="fas fa-circle-notch fa-sm"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <p class="mb-1">{{ $activity->description }}</p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $activity->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Reassign function (for admins)
function reassignTechnician(technicianId, technicianName) {
    // This would typically open a modal or redirect
    // For now, redirect to admin page
    window.location.href = '{{ route('teams.admin.index') }}';
}

// Remove from team function (for admins)
function removeFromTeam(technicianId, technicianName) {
    if (!confirm('Make ' + technicianName + ' an independent technician?')) {
        return;
    }

    $.ajax({
        url: '/teams/' + technicianId + '/remove',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            alert(response.message);
            location.reload();
        },
        error: function(xhr) {
            alert(xhr.responseJSON?.message || 'Failed to remove from team');
        }
    });
}
</script>
@endpush
@endsection
