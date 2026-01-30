{{-- Team Job History Tab Content - Supervisor --}}

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5>Team Job History</h5>
    <span class="badge bg-primary">{{ $teamJobs->count() }} Jobs</span>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Showing jobs assigned to your team technicians only.
</div>

@forelse($teamJobs as $job)
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-9">
                <h6 class="mb-1">
                    {{ $job->job_no }}
                    @if($job->status === 'completed')
                        <span class="badge bg-success ms-2">Completed</span>
                    @elseif($job->status === 'in_progress')
                        <span class="badge bg-primary ms-2">In Progress</span>
                    @elseif($job->status === 'assigned')
                        <span class="badge bg-info ms-2">Assigned</span>
                    @elseif($job->status === 'pending')
                        <span class="badge bg-warning ms-2">Pending</span>
                    @elseif($job->status === 'cancelled')
                        <span class="badge bg-danger ms-2">Cancelled</span>
                    @else
                        <span class="badge bg-secondary ms-2">{{ ucfirst($job->status) }}</span>
                    @endif
                </h6>
                
                @if($job->job_type)
                <p class="text-muted small mb-2">
                    <strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $job->job_type)) }}
                </p>
                @endif

                <div class="row small">
                    <div class="col-md-6">
                        <i class="bi bi-calendar text-primary me-1"></i>
                        <strong>Scheduled:</strong> {{ $job->scheduled_date ? \Carbon\Carbon::parse($job->scheduled_date)->format('d M Y') : '-' }}
                    </div>
                    @if($job->completed_at)
                    <div class="col-md-6">
                        <i class="bi bi-check-circle text-success me-1"></i>
                        <strong>Completed:</strong> {{ \Carbon\Carbon::parse($job->completed_at)->format('d M Y H:i') }}
                    </div>
                    @endif
                </div>

                @if($job->jobAssignments && $job->jobAssignments->count() > 0)
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="bi bi-people me-1"></i>
                        <strong>Team Members:</strong>
                        @foreach($job->jobAssignments as $assignment)
                            <span class="badge bg-secondary">{{ $assignment->technician->name ?? 'Unknown' }}</span>
                        @endforeach
                    </small>
                </div>
                @endif

                @if($job->description)
                <div class="mt-2">
                    <small class="text-muted">{{ Str::limit($job->description, 100) }}</small>
                </div>
                @endif
            </div>

            <div class="col-md-3 text-end">
                @if($job->status === 'assigned' || $job->status === 'in_progress')
                <button class="btn btn-sm btn-outline-primary d-block" 
                        onclick="alert('Job tracking available in full system')">
                    <i class="bi bi-eye me-1"></i> Track Progress
                </button>
                @endif
            </div>
        </div>

        @if($job->sla_due_date)
        <div class="mt-2">
            <small>
                <i class="bi bi-clock-history text-warning me-1"></i>
                <strong>SLA Due:</strong> {{ \Carbon\Carbon::parse($job->sla_due_date)->format('d M Y H:i') }}
                @if(\Carbon\Carbon::parse($job->sla_due_date)->isPast() && $job->status !== 'completed')
                    <span class="badge bg-danger ms-1">OVERDUE</span>
                @elseif(\Carbon\Carbon::parse($job->sla_due_date)->diffInHours(now()) < 4)
                    <span class="badge bg-warning ms-1">DUE SOON</span>
                @endif
            </small>
        </div>
        @endif
    </div>
</div>
@empty
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No team job history found for this site.
</div>
@endforelse
