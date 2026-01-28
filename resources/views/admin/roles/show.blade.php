@extends('layouts.app')

@section('title', 'View Role - ' . ucwords(str_replace(['_', '-'], ' ', $role->name)))

@section('styles')
<style>
    .permission-group {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        margin-bottom: 1rem;
    }
    .permission-group-header {
        background-color: #f8f9fa;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #dee2e6;
    }
    .permission-group-body {
        padding: 1rem;
    }
    .permission-badge {
        font-size: 0.8rem;
        margin: 3px;
        padding: 0.4em 0.8em;
    }
    .permission-badge.granted {
        background-color: #198754;
        color: white;
    }
    .permission-badge.not-granted {
        background-color: #e9ecef;
        color: #6c757d;
        text-decoration: line-through;
    }
    .user-card {
        transition: transform 0.2s ease;
    }
    .user-card:hover {
        transform: translateY(-2px);
    }
    .info-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-item:last-child {
        border-bottom: none;
    }
    .badge-action {
        font-size: 0.65rem;
        padding: 0.15em 0.4em;
        margin-left: 3px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                {{ ucwords(str_replace(['_', '-'], ' ', $role->name)) }}
                @if($isSystemRole)
                    <span class="badge bg-secondary ms-2"><i class="bi bi-lock me-1"></i>System Role</span>
                @else
                    <span class="badge bg-success ms-2"><i class="bi bi-person-gear me-1"></i>Custom Role</span>
                @endif
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active">{{ $role->name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            @if(!$isSystemRole)
                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i> Edit Role
                </a>
            @endif
            <a href="{{ route('admin.permissions.matrix') }}" class="btn btn-outline-primary">
                <i class="bi bi-grid-3x3-gap me-1"></i> Permission Matrix
            </a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Roles
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Left Column - Role Information -->
        <div class="col-lg-4">
            <!-- Role Details Card -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Role Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <small class="text-muted d-block">Role Name</small>
                        <strong>{{ $role->name }}</strong>
                    </div>
                    <div class="info-item">
                        <small class="text-muted d-block">Display Name</small>
                        <strong>{{ ucwords(str_replace(['_', '-'], ' ', $role->name)) }}</strong>
                    </div>
                    <div class="info-item">
                        <small class="text-muted d-block">Guard</small>
                        <code>{{ $role->guard_name }}</code>
                    </div>
                    <div class="info-item">
                        <small class="text-muted d-block">Type</small>
                        @if($isSystemRole)
                            <span class="badge bg-secondary"><i class="bi bi-lock me-1"></i>System Role</span>
                        @else
                            <span class="badge bg-success"><i class="bi bi-person-gear me-1"></i>Custom Role</span>
                        @endif
                    </div>
                    <div class="info-item">
                        <small class="text-muted d-block">Created</small>
                        {{ $role->created_at->format('M d, Y H:i') }}
                    </div>
                    <div class="info-item">
                        <small class="text-muted d-block">Last Updated</small>
                        {{ $role->updated_at->format('M d, Y H:i') }}
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bar-chart me-2"></i>Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="border rounded p-3">
                                <h3 class="text-primary mb-0">{{ $role->permissions->count() }}</h3>
                                <small class="text-muted">Permissions</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="border rounded p-3">
                                <h3 class="text-success mb-0">{{ $role->users->count() }}</h3>
                                <small class="text-muted">Users</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users with this Role -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>Users with this Role
                    </h5>
                    <span class="badge bg-primary">{{ $role->users->count() }}</span>
                </div>
                <div class="card-body">
                    @if($role->users->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($role->users->take(10) as $user)
                                <div class="list-group-item px-0 user-card">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-primary text-white me-3" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <strong>{{ $user->name }}</strong>
                                            <br><small class="text-muted">{{ $user->email }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($role->users->count() > 10)
                            <div class="text-center mt-3">
                                <a href="{{ route('admin.users.index', ['role' => $role->name]) }}" class="btn btn-outline-primary btn-sm">
                                    View all {{ $role->users->count() }} users
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-person-x display-6"></i>
                            <p class="mt-2 mb-0">No users assigned to this role.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column - Permissions -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-key me-2"></i>Assigned Permissions
                    </h5>
                    <div>
                        <span class="badge bg-success me-2">{{ $role->permissions->count() }} granted</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" id="expand-all">
                                <i class="bi bi-arrows-expand"></i> Expand
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="collapse-all">
                                <i class="bi bi-arrows-collapse"></i> Collapse
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($groupedPermissions as $group => $data)
                        @php
                            $groupPermissionIds = collect($data['permissions'])->pluck('id')->toArray();
                            $grantedInGroup = count(array_intersect($groupPermissionIds, $rolePermissions));
                            $totalInGroup = count($data['permissions']);
                        @endphp
                        <div class="permission-group">
                            <div class="permission-group-header d-flex justify-content-between align-items-center" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#group-{{ $group }}"
                                style="cursor: pointer;">
                                <div>
                                    <i class="bi bi-chevron-down me-2 collapse-icon"></i>
                                    <strong>{{ $data['label'] }}</strong>
                                </div>
                                <div>
                                    <span class="badge {{ $grantedInGroup > 0 ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $grantedInGroup }}/{{ $totalInGroup }} granted
                                    </span>
                                </div>
                            </div>
                            <div class="collapse show permission-group-body" id="group-{{ $group }}">
                                @foreach($data['permissions'] as $permission)
                                    @php
                                        $isGranted = in_array($permission['id'], $rolePermissions);
                                        $actionColors = [
                                            'view' => 'info',
                                            'create' => 'success',
                                            'edit' => 'warning',
                                            'update' => 'warning',
                                            'delete' => 'danger',
                                            'manage' => 'primary',
                                            'export' => 'secondary',
                                            'import' => 'secondary',
                                            'approve' => 'success',
                                            'reject' => 'danger',
                                        ];
                                        $actionColor = $actionColors[$permission['action']] ?? 'secondary';
                                    @endphp
                                    <span class="badge permission-badge {{ $isGranted ? 'granted' : 'not-granted' }}">
                                        @if($isGranted)
                                            <i class="bi bi-check-circle me-1"></i>
                                        @else
                                            <i class="bi bi-x-circle me-1"></i>
                                        @endif
                                        {{ $permission['display_name'] }}
                                        <span class="badge bg-{{ $actionColor }} badge-action">{{ $permission['action'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="bi bi-key display-4 text-muted"></i>
                            <p class="text-muted mt-2">No permissions defined in the system.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            @if($isSystemRole)
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>System Role:</strong> This is a system-defined role that cannot be modified or deleted. 
                    System roles are essential for the proper functioning of the application.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Collapse icon rotation
    $('.permission-group-header').on('click', function() {
        var icon = $(this).find('.collapse-icon');
        setTimeout(function() {
            if ($(this).next('.permission-group-body').hasClass('show')) {
                icon.removeClass('bi-chevron-right').addClass('bi-chevron-down');
            } else {
                icon.removeClass('bi-chevron-down').addClass('bi-chevron-right');
            }
        }.bind(this), 350);
    });

    // Expand all groups
    $('#expand-all').on('click', function() {
        $('.permission-group-body').collapse('show');
        $('.collapse-icon').removeClass('bi-chevron-right').addClass('bi-chevron-down');
    });

    // Collapse all groups
    $('#collapse-all').on('click', function() {
        $('.permission-group-body').collapse('hide');
        $('.collapse-icon').removeClass('bi-chevron-down').addClass('bi-chevron-right');
    });
});
</script>
@endsection
