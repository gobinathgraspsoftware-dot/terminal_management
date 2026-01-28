@extends('layouts.admin')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">My Profile</h1>
        <div>
            <a href="{{ route('admin.profile.edit') }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
        </div>
    </div>

    <!-- Profile Completion Alert -->
    @if($completionPercentage < 100)
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle"></i>
        Your profile is <strong>{{ $completionPercentage }}% complete</strong>. 
        Please complete your profile for better experience.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <!-- Profile Information Card -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <!-- Avatar -->
                    <div class="mb-3">
                        @if($user->avatar)
                            <img src="{{ Storage::url($user->avatar) }}" 
                                 alt="Avatar" 
                                 class="rounded-circle img-thumbnail"
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        @else
                            <img src="{{ asset('images/default-avatar.png') }}" 
                                 alt="Default Avatar" 
                                 class="rounded-circle img-thumbnail"
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        @endif
                    </div>

                    <!-- User Info -->
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-1">{{ $user->email }}</p>
                    <p class="text-muted mb-3">
                        <span class="badge bg-primary">{{ $user->getRoleNames()->first() }}</span>
                    </p>

                    <!-- Quick Actions -->
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.profile.avatar') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-camera"></i> Change Avatar
                        </a>
                        <a href="{{ route('admin.profile.password') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>

            <!-- Profile Completion Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Profile Completion</h6>
                </div>
                <div class="card-body">
                    <div class="progress mb-2" style="height: 25px;">
                        <div class="progress-bar" 
                             role="progressbar" 
                             style="width: {{ $completionPercentage }}%;"
                             aria-valuenow="{{ $completionPercentage }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            {{ $completionPercentage }}%
                        </div>
                    </div>
                    <small class="text-muted">
                        Complete your profile to unlock all features
                    </small>
                </div>
            </div>
        </div>

        <!-- Profile Details & Login History -->
        <div class="col-lg-8">
            <!-- Profile Details Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Profile Details</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Full Name:</strong>
                        </div>
                        <div class="col-sm-8">
                            {{ $user->name }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Email:</strong>
                        </div>
                        <div class="col-sm-8">
                            {{ $user->email }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Phone:</strong>
                        </div>
                        <div class="col-sm-8">
                            {{ $user->phone ?? 'Not provided' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Address:</strong>
                        </div>
                        <div class="col-sm-8">
                            {{ $user->address ?? 'Not provided' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Date of Birth:</strong>
                        </div>
                        <div class="col-sm-8">
                            {{ $user->date_of_birth ? $user->date_of_birth->format('d M Y') : 'Not provided' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Gender:</strong>
                        </div>
                        <div class="col-sm-8">
                            {{ $user->gender ? ucfirst($user->gender) : 'Not provided' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Emergency Contact:</strong>
                        </div>
                        <div class="col-sm-8">
                            @if($user->emergency_contact_name)
                                {{ $user->emergency_contact_name }}
                                @if($user->emergency_contact_phone)
                                    ({{ $user->emergency_contact_phone }})
                                @endif
                            @else
                                Not provided
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-4">
                            <strong>Member Since:</strong>
                        </div>
                        <div class="col-sm-8">
                            {{ $user->created_at->format('d M Y') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Statistics Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Login Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <h4 class="mb-1">{{ $loginStats['total_logins'] }}</h4>
                                <p class="text-muted mb-0 small">Total Logins</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h4 class="mb-1 text-danger">{{ $loginStats['failed_logins'] }}</h4>
                                <p class="text-muted mb-0 small">Failed Attempts</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h4 class="mb-1">{{ $loginStats['avg_session_duration'] }}m</h4>
                                <p class="text-muted mb-0 small">Avg Session</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h4 class="mb-1">
                                    @if($loginStats['last_login'])
                                        {{ $loginStats['last_login']->login_at->diffForHumans() }}
                                    @else
                                        N/A
                                    @endif
                                </h4>
                                <p class="text-muted mb-0 small">Last Login</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login History Card -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Recent Login History</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="loadMoreHistory">
                        Load More
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>IP Address</th>
                                    <th>Browser</th>
                                    <th>Platform</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="loginHistoryTable">
                                @forelse($loginHistory as $history)
                                <tr>
                                    <td>{{ $history->login_at->format('d M Y H:i') }}</td>
                                    <td>
                                        <small class="text-muted">{{ $history->ip_address }}</small>
                                    </td>
                                    <td>{{ $history->browser ?? 'Unknown' }}</td>
                                    <td>{{ $history->platform ?? 'Unknown' }}</td>
                                    <td>{{ $history->formatted_duration }}</td>
                                    <td>
                                        <span class="badge bg-{{ $history->status_badge }}">
                                            {{ ucfirst($history->login_status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        No login history found
                                    </td>
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

@push('scripts')
<script>
$(document).ready(function() {
    let currentLimit = 10;

    $('#loadMoreHistory').on('click', function() {
        currentLimit += 10;
        loadLoginHistory(currentLimit);
    });

    function loadLoginHistory(limit) {
        $.ajax({
            url: '{{ route("admin.profile.login-history") }}',
            method: 'GET',
            data: { limit: limit },
            success: function(response) {
                updateHistoryTable(response.data);
                if (response.data.length < limit) {
                    $('#loadMoreHistory').hide();
                }
            },
            error: function() {
                alert('Failed to load login history');
            }
        });
    }

    function updateHistoryTable(data) {
        let html = '';
        if (data.length === 0) {
            html = '<tr><td colspan="6" class="text-center text-muted">No login history found</td></tr>';
        } else {
            data.forEach(function(history) {
                html += `
                    <tr>
                        <td>${formatDate(history.login_at)}</td>
                        <td><small class="text-muted">${history.ip_address}</small></td>
                        <td>${history.browser || 'Unknown'}</td>
                        <td>${history.platform || 'Unknown'}</td>
                        <td>${history.formatted_duration}</td>
                        <td>
                            <span class="badge bg-${history.status_badge}">
                                ${capitalize(history.login_status)}
                            </span>
                        </td>
                    </tr>
                `;
            });
        }
        $('#loginHistoryTable').html(html);
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
});
</script>
@endpush
@endsection
