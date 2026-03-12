@extends('layouts.app')

@section('title', 'View User - TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><i class="bi bi-person me-2"></i>User Details</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('edit_users')
            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-2"></i>Edit User
            </a>
            @endcan
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center">
                    @if($user->avatar)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                             class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;"
                             onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 120px; height: 120px; font-size: 48px; display: none;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @else
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 120px; height: 120px; font-size: 48px;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif

                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <p class="text-muted mb-3">{{ $user->email }}</p>

                    @if($user->roles->isNotEmpty())
                        @foreach($user->roles as $role)
                            <span class="badge bg-{{ $role->name === 'admin' ? 'danger' : ($role->name === 'supervisor' ? 'primary' : 'success') }} me-1">
                                {{ ucfirst($role->name) }}
                            </span>
                        @endforeach
                    @endif

                    <hr>

                    <div class="d-flex justify-content-around text-center">
                        <div>
                            <h6 class="text-muted mb-0">Status</h6>
                            <span class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'inactive' ? 'warning' : 'danger') }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">Employee ID</h6>
                            <p class="mb-0"><strong>{{ $user->employee_id }}</strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-telephone me-2"></i>Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Phone</small>
                        <strong>{{ $user->phone ?? 'Not set' }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Email</small>
                        <strong>{{ $user->email }}</strong>
                        @if($user->email_verified_at)
                            <i class="bi bi-check-circle-fill text-success ms-1" title="Email Verified"></i>
                        @endif
                    </div>
                    @if($user->address)
                    <div class="mb-0">
                        <small class="text-muted d-block">Address</small>
                        <p class="mb-0">{{ $user->address }}</p>
                    </div>
                    @endif
                </div>
            </div>

            @if($user->hasRole('technician') && ($user->bank_name || $user->bank_account_no))
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-bank me-2"></i>Bank Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Bank Name</small>
                        <strong>{{ $user->bank_name ?? 'Not set' }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Account Number</small>
                        <strong>{{ $user->bank_account_no ?? 'Not set' }}</strong>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Account Name</small>
                        <strong>{{ $user->bank_account_name ?? 'Not set' }}</strong>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Full Name</small>
                            <strong>{{ $user->name }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Employee ID</small>
                            <strong>{{ $user->employee_id }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Email</small>
                            <strong>{{ $user->email }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Phone</small>
                            <strong>{{ $user->phone ?? 'Not set' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Date of Birth</small>
                            <strong>{{ $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('d M Y') : 'Not set' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Gender</small>
                            <strong>{{ $user->gender ? ucfirst($user->gender) : 'Not set' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-briefcase me-2"></i>Employment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Role</small>
                            @if($user->roles->isNotEmpty())
                                @foreach($user->roles as $role)
                                    <span class="badge bg-{{ $role->name === 'admin' ? 'danger' : ($role->name === 'supervisor' ? 'primary' : 'success') }}">
                                        {{ ucfirst($role->name) }}
                                    </span>
                                @endforeach
                            @else
                                <strong>No role assigned</strong>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'inactive' ? 'warning' : 'danger') }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </div>
                        @if($user->supervisor)
                        <div class="col-md-6">
                            <small class="text-muted d-block">Supervisor</small>
                            <strong>{{ $user->supervisor->name }}</strong>
                        </div>
                        @endif
                        @if($user->hasRole('technician'))
                        <div class="col-md-6">
                            <small class="text-muted d-block">Rate Card</small>
                            <strong>{{ $user->default_rate_card_id ? 'Assigned' : 'Not assigned' }}</strong>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Location & Mileage Rate Section (Supervisor & Technician) --}}
            @if($user->hasRole('supervisor') || $user->hasRole('technician'))
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location & Mileage Rate</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">State</small>
                            @if($user->state)
                                <span class="badge bg-info">{{ $user->state->name }}</span>
                            @else
                                <strong>Not set</strong>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">District / City</small>
                            @if($user->city)
                                <span class="badge bg-info">{{ $user->city->name }} ({{ $user->city->postcode }})</span>
                            @else
                                <strong>Not set</strong>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Mileage Rate</small>
                            @if($user->hasRole('technician') && $user->supervisor_id)
                                <strong>RM {{ $user->supervisor?->mileage_rate ? number_format($user->supervisor->mileage_rate, 2) : '0.00' }} /KM</strong>
                                <br><small class="text-muted"><i class="bi bi-arrow-repeat"></i> Inherited from {{ $user->supervisor->name }}</small>
                            @elseif($user->mileage_rate)
                                <strong>RM {{ number_format($user->mileage_rate, 2) }} /KM</strong>
                            @else
                                <strong>Not set</strong>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($user->hasRole('technician'))
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Technician Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <small class="text-muted d-block">Skill Tags</small>
                            @if($user->skill_tags && count($user->skill_tags) > 0)
                                @foreach($user->skill_tags as $skill)
                                    <span class="badge bg-secondary me-1">{{ $skill }}</span>
                                @endforeach
                            @else
                                <strong>Not set</strong>
                            @endif
                        </div>
                        @if($user->emergency_contact_name)
                        <div class="col-md-6">
                            <small class="text-muted d-block">Emergency Contact</small>
                            <strong>{{ $user->emergency_contact_name }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Emergency Phone</small>
                            <strong>{{ $user->emergency_contact_phone }}</strong>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            @if($user->hasRole('supervisor') && $user->technicians && $user->technicians->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Team Members ({{ $user->technicians->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Employee ID</th>
                                    <th>State</th>
                                    <th>City</th>
                                    <th>Mileage Rate</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->technicians as $member)
                                <tr>
                                    <td>{{ $member->name }}</td>
                                    <td>{{ $member->employee_id }}</td>
                                    <td>{{ $member->state?->name ?? '-' }}</td>
                                    <td>{{ $member->city?->name ?? '-' }}</td>
                                    <td>{{ $member->mileage_rate ? 'RM ' . number_format($member->mileage_rate, 2) : '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $member->status === 'active' ? 'success' : 'warning' }}">
                                            {{ ucfirst($member->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.users.show', $member->id) }}" class="btn btn-sm btn-info">
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

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Activity Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Last Login</small>
                            <strong>{{ $user->last_login_at ? $user->last_login_at->format('d M Y, h:i A') : 'Never' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Last IP Address</small>
                            <strong>{{ $user->last_login_ip ?? 'N/A' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Created At</small>
                            <strong>{{ $user->created_at->format('d M Y, h:i A') }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Updated At</small>
                            <strong>{{ $user->updated_at->format('d M Y, h:i A') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
