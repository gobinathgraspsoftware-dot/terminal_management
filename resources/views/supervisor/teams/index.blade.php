@extends('layouts.app')

@section('title', 'My Team')

@section('page-header')
<div>
    <h4 class="mb-1">My Team</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">My Team</li>
        </ol>
    </nav>
</div>
@endsection

@section('content')
{{-- Statistics Cards --}}
<div class="row mb-4">
    <div class="col-md-2 col-6 mb-3">
        <div class="card h-100 border-start border-primary border-4">
            <div class="card-body text-center py-3">
                <h3 class="text-primary mb-0">{{ $teamStats['total_members'] }}</h3>
                <small class="text-muted">Total Members</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card h-100 border-start border-success border-4">
            <div class="card-body text-center py-3">
                <h3 class="text-success mb-0">{{ $teamStats['active_members'] }}</h3>
                <small class="text-muted">Active</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card h-100 border-start border-info border-4">
            <div class="card-body text-center py-3">
                <h3 class="text-info mb-0">{{ $teamStats['todays_jobs'] }}</h3>
                <small class="text-muted">Today's Jobs</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card h-100 border-start border-warning border-4">
            <div class="card-body text-center py-3">
                <h3 class="text-warning mb-0">{{ $teamStats['pending_jobs'] }}</h3>
                <small class="text-muted">Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card h-100 border-start border-secondary border-4">
            <div class="card-body text-center py-3">
                <h3 class="mb-0">{{ $teamStats['completed_this_month'] }}</h3>
                <small class="text-muted">MTD Completed</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card h-100 border-start {{ $teamStats['sla_compliance']['rate'] >= 90 ? 'border-success' : 'border-danger' }} border-4">
            <div class="card-body text-center py-3">
                <h3 class="{{ $teamStats['sla_compliance']['rate'] >= 90 ? 'text-success' : 'text-danger' }} mb-0">{{ $teamStats['sla_compliance']['rate'] }}%</h3>
                <small class="text-muted">SLA Rate</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        @if(!empty($teamStats['coverage_states']) && is_array($teamStats['coverage_states']) && count($teamStats['coverage_states']) > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    <i class="bi bi-geo-alt-fill me-2 text-primary"></i>Coverage Areas
                </h6>
            </div>
            <div class="card-body">
                @foreach($teamStats['coverage_states'] as $state)
                    <span class="badge bg-light text-dark border me-1 mb-1 px-3 py-2">{{ $state }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Team Members Table (Table View Only) --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    <i class="bi bi-people-fill me-2 text-info"></i>Team Members ({{ $teamMembers->count() }})
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Member</th>
                                <th>Employee ID</th>
                                <th>Phone</th>
                                <th>Coverage</th>
                                <th>Skills</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($teamMembers as $member)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $member->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($member->name) . '&background=random&color=fff' }}"
                                             class="rounded-circle me-2"
                                             style="width:40px; height:40px; object-fit:cover;"
                                             alt="{{ $member->name }}">
                                        <div>
                                            <div class="fw-medium">{{ $member->name }}</div>
                                            <small class="text-muted">{{ $member->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $member->employee_id }}</span>
                                </td>
                                <td>
                                    @if($member->phone)
                                        <a href="tel:{{ $member->phone }}" class="text-decoration-none">
                                            <i class="bi bi-telephone-fill me-1 text-primary"></i>
                                            {{ $member->phone }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $mCoverage = is_array($member->coverage_states) ? $member->coverage_states : (is_string($member->coverage_states) && !empty($member->coverage_states) ? json_decode($member->coverage_states, true) : []);
                                        if (!is_array($mCoverage)) $mCoverage = [];
                                    @endphp
                                    @if(count($mCoverage) > 0)
                                        @foreach(array_slice($mCoverage, 0, 2) as $state)
                                            <span class="badge bg-light text-dark border me-1 mb-1">{{ $state }}</span>
                                        @endforeach
                                        @if(count($mCoverage) > 2)
                                            <span class="badge bg-secondary">+{{ count($mCoverage) - 2 }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $mSkills = is_array($member->skill_tags) ? $member->skill_tags : (is_string($member->skill_tags) && !empty($member->skill_tags) ? json_decode($member->skill_tags, true) : []);
                                        if (!is_array($mSkills)) $mSkills = [];
                                    @endphp
                                    @if(count($mSkills) > 0)
                                        @foreach(array_slice($mSkills, 0, 2) as $skill)
                                            <span class="badge bg-info me-1 mb-1">{{ $skill }}</span>
                                        @endforeach
                                        @if(count($mSkills) > 2)
                                            <span class="badge bg-dark">+{{ count($mSkills) - 2 }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($member->status) {
                                            'active' => 'success',
                                            'inactive' => 'secondary',
                                            'suspended' => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ ucfirst($member->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info view-member"
                                            data-id="{{ $member->id }}"
                                            title="View Details">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-people fs-1 mb-2 opacity-50"></i>
                                    <p class="mb-0">No team members found</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Weekly Jobs
                </h6>
            </div>
            <div class="card-body">
                <canvas id="weeklyJobsChart" height="200"></canvas>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    <i class="bi bi-trophy-fill me-2 text-warning"></i>Top Performers
                </h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($teamPerformance['top_performers'] ?? [] as $index => $performer)
                        <li class="list-group-item d-flex align-items-center">
                            <span class="badge bg-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'dark') }} rounded-circle me-3"
                                  style="width:24px; height:24px; line-height:24px; padding:0;">
                                {{ $index + 1 }}
                            </span>
                            <div class="flex-grow-1">
                                <p class="mb-0 fw-medium">{{ $performer->name }}</p>
                                <small class="text-muted">{{ $performer->jobs_completed }} jobs</small>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">No data available</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Member Detail Modal --}}
<div class="modal fade" id="memberDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-circle me-2"></i>Member Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th width="40%">Name</th><td id="memberName">-</td></tr>
                            <tr><th>Employee ID</th><td id="memberEmployeeId">-</td></tr>
                            <tr><th>Email</th><td id="memberEmail">-</td></tr>
                            <tr><th>Phone</th><td id="memberPhone">-</td></tr>
                            <tr><th>Status</th><td id="memberStatus">-</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6>Performance</h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4 id="memberTotalJobs" class="text-primary mb-0">0</h4>
                                        <small>Total</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 id="memberCompletedJobs" class="text-success mb-0">0</h4>
                                        <small>Completed</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 id="memberPendingJobs" class="text-warning mb-0">0</h4>
                                        <small>Pending</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <h6><i class="bi bi-geo-alt-fill me-2"></i>Coverage</h6>
                <div id="memberCoverage"></div>
                <hr>
                <h6><i class="bi bi-tools me-2"></i>Skills</h6>
                <div id="memberSkills"></div>
            </div>
            <div class="modal-footer">
                <a href="#" id="memberViewFullLink" class="btn btn-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i> View Full Profile
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    // Weekly Jobs Chart
    new Chart(document.getElementById('weeklyJobsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($teamPerformance['jobs_this_week']['labels'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) !!},
            datasets: [{
                label: 'Jobs',
                data: {!! json_encode($teamPerformance['jobs_this_week']['data'] ?? [0,0,0,0,0,0,0]) !!},
                backgroundColor: 'rgba(13, 110, 253, 0.8)',
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

    // View Member Modal
    $(document).on('click', '.view-member', function() {
        var id = $(this).data('id');

        $.ajax({
            url: '/supervisor/teams/' + id,
            method: 'GET',
            success: function(response) {
                var user = response.user;
                var stats = response.statistics;

                // Populate modal data
                $('#memberName').text(user.name);
                $('#memberEmployeeId').text(user.employee_id);
                $('#memberEmail').text(user.email);
                $('#memberPhone').text(user.phone || '-');

                // Status badge
                var statusBadge = '<span class="badge bg-' +
                    (user.status === 'active' ? 'success' : (user.status === 'suspended' ? 'danger' : 'secondary')) +
                    '">' + user.status.charAt(0).toUpperCase() + user.status.slice(1) + '</span>';
                $('#memberStatus').html(statusBadge);

                // Statistics
                $('#memberTotalJobs').text(stats.total_jobs);
                $('#memberCompletedJobs').text(stats.completed_jobs);
                $('#memberPendingJobs').text(stats.pending_jobs);

                // Coverage states
                var coverageHtml = '';
                if (user.coverage_states && user.coverage_states.length > 0) {
                    user.coverage_states.forEach(function(state) {
                        coverageHtml += '<span class="badge bg-light text-dark border me-1 mb-1">' + state + '</span>';
                    });
                } else {
                    coverageHtml = '<span class="text-muted">Not assigned</span>';
                }
                $('#memberCoverage').html(coverageHtml);

                // Skills
                var skillsHtml = '';
                if (user.skill_tags && user.skill_tags.length > 0) {
                    user.skill_tags.forEach(function(skill) {
                        skillsHtml += '<span class="badge bg-info me-1 mb-1">' + skill + '</span>';
                    });
                } else {
                    skillsHtml = '<span class="text-muted">Not tagged</span>';
                }
                $('#memberSkills').html(skillsHtml);

                // Set view full profile link
                $('#memberViewFullLink').attr('href', '/supervisor/teams/' + id);

                // Show modal
                $('#memberDetailModal').modal('show');
            },
            error: function(xhr) {
                alert('Error loading member details: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });
});
</script>
@endpush
