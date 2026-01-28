@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">My Profile</h1>
        <div class="btn-group">
            <a href="{{ route('technician.profile.edit') }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit Profile
            </a>
            <a href="{{ route('technician.profile.password') }}" class="btn btn-outline-primary">
                <i class="bi bi-key"></i> Change Password
            </a>
            <a href="{{ route('technician.profile.avatar') }}" class="btn btn-outline-primary">
                <i class="bi bi-camera"></i> Change Avatar
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Profile Overview Card -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <!-- Avatar with cache busting -->
                    <div class="mb-3">
                        @if($user->avatar)
                            {{-- Cache busting with timestamp to force browser reload --}}
                            <img src="{{ asset('storage/' . $user->avatar) }}?v={{ $user->updated_at->timestamp }}"
                                 alt="Avatar"
                                 class="rounded-circle img-thumbnail"
                                 id="profileAvatar"
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                                 style="width: 150px; height: 150px; font-size: 4rem;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <!-- User Info -->
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-1">{{ $user->email }}</p>
                    <p class="text-muted mb-3">
                        <span class="badge bg-danger">{{ $user->role_name }}</span>
                    </p>

                    <!-- Profile Completion -->
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Profile Completion</small>
                            <small class="text-muted">{{ $completionPercentage }}%</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar
                                @if($completionPercentage < 50) bg-danger
                                @elseif($completionPercentage < 80) bg-warning
                                @else bg-success
                                @endif"
                                 role="progressbar"
                                 style="width: {{ $completionPercentage }}%"
                                 aria-valuenow="{{ $completionPercentage }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Statistics Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-graph-up"></i> Login Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="border rounded p-2">
                                <h4 class="mb-0 text-primary">{{ $loginStats['total_logins'] }}</h4>
                                <small class="text-muted">Total Logins</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="border rounded p-2">
                                <h4 class="mb-0 text-danger">{{ $loginStats['failed_logins'] }}</h4>
                                <small class="text-muted">Failed Attempts</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <h4 class="mb-0 text-info">{{ $loginStats['avg_session_duration'] }}m</h4>
                                <small class="text-muted">Avg Session</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <h4 class="mb-0 text-success">
                                    @if($loginStats['last_login'])
                                        {{ $loginStats['last_login']->login_at->diffForHumans() }}
                                    @else
                                        Never
                                    @endif
                                </h4>
                                <small class="text-muted">Last Login</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Details Card -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-person-circle"></i> Profile Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Full Name</label>
                            <p class="mb-0"><strong>{{ $user->name }}</strong></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Email Address</label>
                            <p class="mb-0"><strong>{{ $user->email }}</strong></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Phone Number</label>
                            <p class="mb-0">{{ $user->phone ?? 'Not provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Date of Birth</label>
                            <p class="mb-0">{{ $user->date_of_birth ? $user->date_of_birth->format('M d, Y') : 'Not provided' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Gender</label>
                            <p class="mb-0">{{ $user->gender ? ucfirst($user->gender) : 'Not provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Employee ID</label>
                            <p class="mb-0">{{ $user->employee_id }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="text-muted small">Address</label>
                            <p class="mb-0">{{ $user->address ?? 'Not provided' }}</p>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3">Emergency Contact</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="text-muted small">Contact Name</label>
                            <p class="mb-0">{{ $user->emergency_contact_name ?? 'Not provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Contact Phone</label>
                            <p class="mb-0">{{ $user->emergency_contact_phone ?? 'Not provided' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Login History Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Login History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="loginHistoryTable">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>IP Address</th>
                                    <th>Browser</th>
                                    <th>Platform</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($loginHistory as $history)
                                <tr>
                                    <td>{{ $history->login_at->format('M d, Y H:i') }}</td>
                                    <td><code>{{ $history->ip_address }}</code></td>
                                    <td>{{ $history->browser }}</td>
                                    <td>{{ $history->platform }}</td>
                                    <td>{{ $history->formatted_duration }}</td>
                                    <td>{!! $history->status_badge !!}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No login history available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($loginHistory->count() >= 10)
                    <div class="text-center mt-3">
                        <button class="btn btn-sm btn-outline-primary" id="loadMoreHistory">
                            <i class="bi bi-arrow-clockwise"></i> Load More
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    let historyLimit = 10;

    // Load more login history
    $('#loadMoreHistory').on('click', function() {
        historyLimit += 10;
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Loading...');

        $.ajax({
            url: '{{ route("technician.profile.login-history") }}',
            method: 'GET',
            data: { limit: historyLimit },
            success: function(response) {
                // Update table with new data
                let tbody = $('#loginHistoryTable tbody');
                tbody.empty();

                if (response.data.length > 0) {
                    response.data.forEach(function(history) {
                        let row = `
                            <tr>
                                <td>${new Date(history.login_at).toLocaleString()}</td>
                                <td><code>${history.ip_address}</code></td>
                                <td>${history.browser}</td>
                                <td>${history.platform}</td>
                                <td>${history.session_duration || '0'} min</td>
                                <td><span class="badge bg-${history.login_status === 'success' ? 'success' : 'danger'}">${history.login_status}</span></td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                } else {
                    tbody.html('<tr><td colspan="6" class="text-center text-muted">No more login history</td></tr>');
                    btn.hide();
                }

                btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise"></i> Load More');
            },
            error: function() {
                alert('Failed to load login history');
                btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise"></i> Load More');
            }
        });
    });
});
</script>
@endpush
@endsection
