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
        @if(count($teamStats['coverage_states']) > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Coverage Areas</h6></div>
            <div class="card-body">
                @foreach($teamStats['coverage_states'] as $state)<span class="badge bg-light text-dark border me-1 mb-1 px-3 py-2">{{ $state }}</span>@endforeach
            </div>
        </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-users me-2 text-info"></i>Team Members ({{ $teamMembers->count() }})</h6>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary active" id="cardViewBtn"><i class="fas fa-th-large"></i></button>
                    <button type="button" class="btn btn-outline-secondary" id="listViewBtn"><i class="fas fa-list"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div id="cardView" class="row">
                    @forelse($teamMembers as $member)
                        @include('supervisor.teams._partials.member-card', ['member' => $member])
                    @empty
                        <div class="col-12 text-center py-5"><i class="fas fa-users fa-3x text-muted mb-3 opacity-50"></i><p class="text-muted">No team members</p></div>
                    @endforelse
                </div>

                <div id="listView" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>Name</th><th>Employee ID</th><th>Phone</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                @foreach($teamMembers as $member)
                                <tr>
                                    <td><div class="d-flex align-items-center"><img src="{{ $member->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($member->name) }}" class="rounded-circle me-2" style="width:32px;height:32px;">{{ $member->name }}</div></td>
                                    <td>{{ $member->employee_id }}</td><td>{{ $member->phone ?? '-' }}</td>
                                    <td><span class="badge bg-{{ $member->status == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($member->status) }}</span></td>
                                    <td><button class="btn btn-sm btn-info view-member" data-id="{{ $member->id }}"><i class="fas fa-eye"></i></button></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Weekly Jobs</h6></div>
            <div class="card-body"><canvas id="weeklyJobsChart" height="200"></canvas></div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Top Performers</h6></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($teamPerformance['top_performers'] ?? [] as $index => $performer)
                        <li class="list-group-item d-flex align-items-center">
                            <span class="badge bg-{{ $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'dark') }} rounded-circle me-3" style="width:24px;height:24px;line-height:24px;padding:0;">{{ $index + 1 }}</span>
                            <div class="flex-grow-1"><p class="mb-0">{{ $performer->name }}</p><small class="text-muted">{{ $performer->jobs_completed }} jobs</small></div>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">No data</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Member Detail Modal --}}
<div class="modal fade" id="memberDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-user me-2"></i>Member Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
                    <div class="card bg-light"><div class="card-body">
                        <h6>Performance</h6>
                        <div class="row text-center">
                            <div class="col-4"><h4 id="memberTotalJobs" class="text-primary mb-0">0</h4><small>Total</small></div>
                            <div class="col-4"><h4 id="memberCompletedJobs" class="text-success mb-0">0</h4><small>Completed</small></div>
                            <div class="col-4"><h4 id="memberPendingJobs" class="text-warning mb-0">0</h4><small>Pending</small></div>
                        </div>
                    </div></div>
                </div>
            </div>
            <hr><h6>Coverage</h6><div id="memberCoverage"></div>
            <hr><h6>Skills</h6><div id="memberSkills"></div>
        </div>
        <div class="modal-footer"><a href="#" id="memberViewFullLink" class="btn btn-primary"><i class="fas fa-external-link-alt me-1"></i> View Full</a><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    $('#cardViewBtn').on('click', function() { $(this).addClass('active'); $('#listViewBtn').removeClass('active'); $('#cardView').show(); $('#listView').hide(); });
    $('#listViewBtn').on('click', function() { $(this).addClass('active'); $('#cardViewBtn').removeClass('active'); $('#listView').show(); $('#cardView').hide(); });

    new Chart(document.getElementById('weeklyJobsChart').getContext('2d'), { type: 'bar', data: { labels: {!! json_encode($teamPerformance['jobs_this_week']['labels'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) !!}, datasets: [{ label: 'Jobs', data: {!! json_encode($teamPerformance['jobs_this_week']['data'] ?? [0,0,0,0,0,0,0]) !!}, backgroundColor: 'rgba(13, 110, 253, 0.8)', borderRadius: 4 }] }, options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } } });

    $(document).on('click', '.view-member', function() {
        var id = $(this).data('id');
        $.ajax({ url: '/supervisor/teams/' + id, method: 'GET', success: function(response) {
            var user = response.user, stats = response.statistics;
            $('#memberName').text(user.name); $('#memberEmployeeId').text(user.employee_id); $('#memberEmail').text(user.email);
            $('#memberPhone').text(user.phone || '-'); $('#memberStatus').html(user.status_badge);
            $('#memberTotalJobs').text(stats.total_jobs); $('#memberCompletedJobs').text(stats.completed_jobs); $('#memberPendingJobs').text(stats.pending_jobs);

            var coverageHtml = '', skillsHtml = '';
            if (user.coverage_states && user.coverage_states.length > 0) { user.coverage_states.forEach(function(s) { coverageHtml += '<span class="badge bg-light text-dark me-1 mb-1">' + s + '</span>'; }); } else { coverageHtml = '<span class="text-muted">Not assigned</span>'; }
            if (user.skill_tags && user.skill_tags.length > 0) { user.skill_tags.forEach(function(s) { skillsHtml += '<span class="badge bg-info me-1 mb-1">' + s + '</span>'; }); } else { skillsHtml = '<span class="text-muted">Not tagged</span>'; }
            $('#memberCoverage').html(coverageHtml); $('#memberSkills').html(skillsHtml);
            $('#memberViewFullLink').attr('href', '/supervisor/teams/' + id);
            $('#memberDetailModal').modal('show');
        }, error: function(xhr) { showToast('error', xhr.responseJSON?.message || 'Failed'); } });
    });
});
</script>
@endpush
