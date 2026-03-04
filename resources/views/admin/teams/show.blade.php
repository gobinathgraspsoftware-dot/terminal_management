@extends('layouts.app')

@section('title', 'User Profile - ' . $user->name)

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">User Profile</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary me-2">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Profile Card --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center">
                    <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=200&background=random' }}"
                         class="rounded-circle mb-3 border border-4 border-primary"
                         style="width: 120px; height: 120px; object-fit: cover;">
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <p class="text-muted mb-2">{{ $user->employee_id }}</p>

                    {{-- Role Badges --}}
                    @foreach($user->roles as $role)
                        @php
                            $roleClass = match($role->name) {
                                'admin' => 'danger',
                                'supervisor' => 'primary',
                                'technician' => 'success',
                                default => 'secondary'
                            };
                        @endphp
                        <span class="badge bg-{{ $roleClass }} me-1">{{ ucfirst($role->name) }}</span>
                    @endforeach

                    {{-- Status --}}
                    @php
                        $statusClass = match($user->status) {
                            'active' => 'success',
                            'inactive' => 'secondary',
                            'suspended' => 'danger',
                            default => 'secondary'
                        };
                    @endphp
                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst($user->status) }}</span>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-pencil me-1"></i> Edit User
                    </a>
                    @if($user->hasRole('technician'))
                    <a href="{{ route('admin.teams.show', $user->id) }}" class="btn btn-outline-info w-100 mb-2">
                        <i class="bi bi-people me-1"></i> View Team Details
                    </a>
                    @endif
                    @if($user->hasRole('supervisor'))
                    <a href="{{ route('admin.teams.index') }}?view=all&supervisor={{ $user->id }}" class="btn btn-outline-info w-100 mb-2">
                        <i class="bi bi-people me-1"></i> View Team Members
                    </a>
                    @endif
                    <button type="button" class="btn btn-outline-warning w-100 mb-2" id="btnChangePassword">
                        <i class="bi bi-key me-1"></i> Change Password
                    </button>
                    @if($user->status === 'active')
                    <button type="button" class="btn btn-outline-danger w-100" id="btnToggleStatus" data-status="inactive">
                        <i class="bi bi-pause-circle me-1"></i> Deactivate
                    </button>
                    @else
                    <button type="button" class="btn btn-outline-success w-100" id="btnToggleStatus" data-status="active">
                        <i class="bi bi-play-circle me-1"></i> Activate
                    </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Details --}}
        <div class="col-lg-8">
            {{-- Personal Information --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-person me-2 text-primary"></i>Personal Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Full Name</label>
                            <p class="mb-0 fw-medium">{{ $user->name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Employee ID</label>
                            <p class="mb-0 fw-medium">{{ $user->employee_id }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Email</label>
                            <p class="mb-0 fw-medium">{{ $user->email }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Phone</label>
                            <p class="mb-0 fw-medium">{{ $user->phone ?? '-' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Gender</label>
                            <p class="mb-0 fw-medium">{{ ucfirst($user->gender ?? '-') }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Date of Birth</label>
                            <p class="mb-0 fw-medium">{{ $user->date_of_birth ? $user->date_of_birth->format('d M Y') : '-' }}</p>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="text-muted small">Address</label>
                            <p class="mb-0 fw-medium">{{ $user->address ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Work Information --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-briefcase me-2 text-success"></i>Work Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Role</label>
                            <div>
                                @foreach($user->roles as $role)
                                    @php
                                        $rc = match($role->name) { 'admin' => 'danger', 'supervisor' => 'primary', 'technician' => 'success', default => 'secondary' };
                                    @endphp
                                    <span class="badge bg-{{ $rc }}">{{ ucfirst($role->name) }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Supervisor</label>
                            <p class="mb-0 fw-medium">{{ $user->supervisor?->name ?? 'None (Independent)' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Branch</label>
                            <p class="mb-0 fw-medium">{{ $user->branch_name ?? '-' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Coverage States</label>
                            <div>
                                @if($user->coverage_states && count($user->coverage_states) > 0)
                                    @foreach($user->coverage_states as $state)
                                        <span class="badge bg-light text-dark me-1 mb-1">{{ $state }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Skill Tags</label>
                            <div>
                                @if($user->skill_tags && count($user->skill_tags) > 0)
                                    @foreach($user->skill_tags as $skill)
                                        <span class="badge bg-info me-1 mb-1">{{ $skill }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Emergency Contact</label>
                            <p class="mb-0 fw-medium">
                                {{ $user->emergency_contact_name ?? '-' }}
                                @if($user->emergency_contact_phone)
                                    <br><small class="text-muted">{{ $user->emergency_contact_phone }}</small>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bank Details --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-bank me-2 text-warning"></i>Bank Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Bank Name</label>
                            <p class="mb-0 fw-medium">{{ $user->bank_name ?? '-' }}</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Account Number</label>
                            <p class="mb-0 fw-medium">{{ $user->bank_account_no ?? '-' }}</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Account Name</label>
                            <p class="mb-0 fw-medium">{{ $user->bank_account_name ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Account Info --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-2 text-secondary"></i>Account Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Created At</label>
                            <p class="mb-0 fw-medium">{{ $user->created_at?->format('d M Y H:i') ?? '-' }}</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Last Updated</label>
                            <p class="mb-0 fw-medium">{{ $user->updated_at?->format('d M Y H:i') ?? '-' }}</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Last Login</label>
                            <p class="mb-0 fw-medium">{{ $user->last_login_at?->format('d M Y H:i') ?? 'Never' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Change Password Modal --}}
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-key me-2"></i>Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg me-1"></i> Change</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Change Password
    $('#btnChangePassword').on('click', function() {
        $('#changePasswordModal').modal('show');
    });

    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true);

        $.ajax({
            url: '{{ route("admin.users.change-password", $user->id) }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#changePasswordModal').modal('hide');
                Swal.fire('Success', response.message || 'Password changed successfully', 'success');
                $('#changePasswordForm')[0].reset();
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'Failed to change password';
                if (xhr.responseJSON?.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Toggle Status
    $('#btnToggleStatus').on('click', function() {
        var newStatus = $(this).data('status');
        Swal.fire({
            title: 'Confirm Status Change',
            text: 'Set user status to ' + newStatus + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, change it'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.users.toggle-status", $user->id) }}',
                    method: 'POST',
                    success: function(response) {
                        Swal.fire('Success', response.message || 'Status updated', 'success').then(function() {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to update status', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
