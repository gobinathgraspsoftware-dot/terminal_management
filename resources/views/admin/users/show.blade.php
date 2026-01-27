@extends('layouts.app')

@section('title', 'User Profile - ' . $user->name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user"></i> User Profile
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @can('update', $user)
            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit User
            </a>
            @endcan
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <!-- Avatar -->
                    <div class="mb-3">
                        @if($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;" alt="Avatar">
                        @else
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px; font-size: 60px;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <!-- Name & Role -->
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <p class="text-muted mb-2">{{ $user->email }}</p>
                    
                    <!-- Role Badges -->
                    <div class="mb-3">
                        @foreach($user->roles as $role)
                            @php
                                $badgeClass = match($role->name) {
                                    'admin' => 'danger',
                                    'supervisor' => 'primary',
                                    'technician' => 'success',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $badgeClass }} fs-6">{{ ucfirst($role->name) }}</span>
                        @endforeach
                    </div>

                    <!-- Status Badge -->
                    <div class="mb-3">
                        @php
                            $statusBadgeClass = match($user->status) {
                                'active' => 'success',
                                'inactive' => 'warning',
                                'suspended' => 'danger',
                                default => 'secondary'
                            };
                        @endphp
                        <span class="badge bg-{{ $statusBadgeClass }} fs-6">{{ ucfirst($user->status) }}</span>
                    </div>

                    <!-- Quick Stats -->
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h5 class="mb-0 text-primary">{{ $user->created_at->format('M Y') }}</h5>
                                <small class="text-muted">Joined</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div>
                                <h5 class="mb-0 text-success">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</h5>
                                <small class="text-muted">Last Login</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-address-card"></i> Contact Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Email</small>
                        <strong>{{ $user->email }}</strong>
                    </div>
                    @if($user->phone)
                    <div class="mb-3">
                        <small class="text-muted d-block">Phone</small>
                        <strong>{{ $user->phone }}</strong>
                    </div>
                    @endif
                    @if($user->address)
                    <div class="mb-0">
                        <small class="text-muted d-block">Address</small>
                        <strong>{{ $user->address }}</strong>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Supervisor/Team Info (for technicians) -->
            @if($user->hasRole('technician') && $user->supervisor)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-user-tie"></i> Supervisor</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        @if($user->supervisor->avatar)
                            <img src="{{ asset('storage/' . $user->supervisor->avatar) }}" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;" alt="Supervisor">
                        @else
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; font-size: 20px;">
                                {{ strtoupper(substr($user->supervisor->name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <strong class="d-block">{{ $user->supervisor->name }}</strong>
                            <small class="text-muted">{{ $user->supervisor->email }}</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Team Members (for supervisors) -->
            @if($user->hasRole('supervisor'))
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-users"></i> Team Members</h6>
                    <span class="badge bg-primary">{{ $user->technicians->count() }}</span>
                </div>
                <div class="card-body">
                    @if($user->technicians->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($user->technicians as $technician)
                            <div class="list-group-item px-0 py-2">
                                <div class="d-flex align-items-center">
                                    @if($technician->avatar)
                                        <img src="{{ asset('storage/' . $technician->avatar) }}" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;" alt="Avatar">
                                    @else
                                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 12px;">
                                            {{ strtoupper(substr($technician->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="flex-grow-1">
                                        <small class="d-block fw-bold">{{ $technician->name }}</small>
                                        <small class="text-muted">{{ $technician->employee_id }}</small>
                                    </div>
                                    <span class="badge bg-{{ $technician->status === 'active' ? 'success' : 'warning' }} badge-sm">
                                        {{ ucfirst($technician->status) }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center mb-0">No team members assigned</p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Details Section -->
        <div class="col-md-8">
            <!-- Basic Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Basic Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tbody>
                            <tr>
                                <td width="30%" class="text-muted">Employee ID:</td>
                                <td><strong>{{ $user->employee_id ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Full Name:</td>
                                <td><strong>{{ $user->name }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Email:</td>
                                <td><strong>{{ $user->email }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Phone:</td>
                                <td><strong>{{ $user->phone ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Role:</td>
                                <td>
                                    @foreach($user->roles as $role)
                                        @php
                                            $badgeClass = match($role->name) {
                                                'admin' => 'danger',
                                                'supervisor' => 'primary',
                                                'technician' => 'success',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $badgeClass }}">{{ ucfirst($role->name) }}</span>
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status:</td>
                                <td>
                                    @php
                                        $statusBadgeClass = match($user->status) {
                                            'active' => 'success',
                                            'inactive' => 'warning',
                                            'suspended' => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusBadgeClass }}">{{ ucfirst($user->status) }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Member Since:</td>
                                <td><strong>{{ $user->created_at->format('F d, Y') }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Last Login:</td>
                                <td><strong>{{ $user->last_login_at ? $user->last_login_at->format('F d, Y H:i A') : 'Never logged in' }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Technician Specific Information -->
            @if($user->hasRole('technician'))
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-tools"></i> Technician Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tbody>
                            <tr>
                                <td width="30%" class="text-muted">Supervisor:</td>
                                <td>
                                    @if($user->supervisor)
                                        <strong>{{ $user->supervisor->name }}</strong>
                                    @else
                                        <span class="badge bg-info">Independent Technician</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Coverage States:</td>
                                <td>
                                    @if($user->coverage_states)
                                        @php
                                            $states = json_decode($user->coverage_states, true);
                                        @endphp
                                        @foreach($states as $state)
                                            <span class="badge bg-secondary me-1">{{ $state }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Skills:</td>
                                <td>
                                    @if($user->skill_tags)
                                        @php
                                            $skills = json_decode($user->skill_tags, true);
                                        @endphp
                                        @foreach($skills as $skill)
                                            <span class="badge bg-info text-dark me-1">{{ $skill }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bank Details -->
            @if($user->bank_name || $user->bank_account_no || $user->bank_account_name)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-university"></i> Bank Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tbody>
                            <tr>
                                <td width="30%" class="text-muted">Bank Name:</td>
                                <td><strong>{{ $user->bank_name ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Account Number:</td>
                                <td><strong>{{ $user->bank_account_no ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Account Name:</td>
                                <td><strong>{{ $user->bank_account_name ?? '-' }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            @endif

            <!-- Activity Timeline -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <!-- Account Created -->
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <p class="mb-0"><strong>Account Created</strong></p>
                                <small class="text-muted">{{ $user->created_at->format('F d, Y H:i A') }}</small>
                            </div>
                        </div>

                        <!-- Last Updated -->
                        @if($user->updated_at && $user->updated_at != $user->created_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <p class="mb-0"><strong>Profile Updated</strong></p>
                                <small class="text-muted">{{ $user->updated_at->format('F d, Y H:i A') }}</small>
                            </div>
                        </div>
                        @endif

                        <!-- Last Login -->
                        @if($user->last_login_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <p class="mb-0"><strong>Last Login</strong></p>
                                <small class="text-muted">{{ $user->last_login_at->format('F d, Y H:i A') }} ({{ $user->last_login_at->diffForHumans() }})</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid #fff;
}

.timeline-content {
    padding-left: 15px;
}
</style>
@endpush