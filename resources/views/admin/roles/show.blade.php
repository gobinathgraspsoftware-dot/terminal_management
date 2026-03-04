@extends('layouts.app')

@section('title', 'Role Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Role Details: {{ ucfirst($role->name) }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active">{{ $role->name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            @if(!$isSystemRole)
                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i> Edit Role
                </a>
            @endif
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Role Information Card -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-shield-lock me-2"></i>Role Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Role Name</label>
                        <div class="fw-bold">{{ ucfirst($role->name) }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small">Type</label>
                        <div>
                            @if($isSystemRole)
                                <span class="badge bg-secondary">
                                    <i class="bi bi-lock me-1"></i>System Role
                                </span>
                            @else
                                <span class="badge bg-success">Custom Role</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small">Guard</label>
                        <div>{{ $role->guard_name }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small">Permissions</label>
                        <div class="fw-bold text-primary">{{ $role->permissions->count() }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small">Users</label>
                        <div class="fw-bold text-info">{{ $role->users->count() }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small">Created</label>
                        <div>{{ $role->created_at->format('M d, Y') }}</div>
                    </div>

                    @if($role->updated_at)
                    <div class="mb-3">
                        <label class="text-muted small">Last Updated</label>
                        <div>{{ $role->updated_at->format('M d, Y H:i') }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Assigned Users Card -->
            @if($role->users->count() > 0)
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>Assigned Users ({{ $role->users->count() }})
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($role->users->take(10) as $user)
                        <div class="list-group-item px-0">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    @if($user->avatar)
                                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="rounded-circle" width="32">
                                    @else
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                    <small class="text-muted">{{ $user->email }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($role->users->count() > 10)
                        <div class="text-center mt-3">
                            <small class="text-muted">And {{ $role->users->count() - 10 }} more users...</small>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Permissions Card -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-key me-2"></i>Assigned Permissions ({{ $role->permissions->count() }})
                    </h5>
                    @if($isSystemRole)
                        <span class="badge bg-warning">
                            <i class="bi bi-lock me-1"></i>System roles cannot be modified
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    @if($role->permissions->count() > 0)
                        <!-- Search Box -->
                        <div class="mb-3">
                            <input type="text"
                                   class="form-control"
                                   id="search-permission"
                                   placeholder="Search permissions...">
                        </div>

                        <!-- Permissions by Module -->
                        <div class="accordion" id="permissionsAccordion">
                            @foreach($permissions as $group => $data)
                                @php
                                    $groupPermissions = collect($data['permissions'])->filter(function($permission) use ($rolePermissions) {
                                        return in_array($permission['id'], $rolePermissions);
                                    });
                                @endphp

                                @if($groupPermissions->count() > 0)
                                <div class="accordion-item permission-group" data-group="{{ $group }}">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#collapse-{{ $group }}">
                                            <i class="bi bi-folder me-2"></i>
                                            <strong>{{ $data['label'] }}</strong>
                                            <span class="badge bg-primary ms-2">{{ $groupPermissions->count() }}</span>
                                        </button>
                                    </h2>
                                    <div id="collapse-{{ $group }}"
                                         class="accordion-collapse collapse"
                                         data-bs-parent="#permissionsAccordion">
                                        <div class="accordion-body">
                                            <div class="row">
                                                @foreach($groupPermissions as $permission)
                                                <div class="col-md-6 mb-2 permission-item" data-permission="{{ $permission['name'] }}">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                        <div>
                                                            <code class="text-primary">{{ $permission['name'] }}</code>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>

                        <!-- No Results Message -->
                        <div id="no-results" class="alert alert-info mt-3" style="display: none;">
                            <i class="bi bi-info-circle me-2"></i>No permissions found matching your search.
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            This role has no permissions assigned.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Search permissions
    $('#search-permission').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();

        if (searchTerm === '') {
            $('.permission-group').show();
            $('.permission-item').show();
            $('#no-results').hide();
            return;
        }

        var foundAny = false;

        $('.permission-group').each(function() {
            var $group = $(this);
            var $items = $group.find('.permission-item');
            var groupHasMatch = false;

            $items.each(function() {
                var $item = $(this);
                var permName = ($item.data('permission') || '').toString().toLowerCase();

                if (permName.indexOf(searchTerm) > -1) {
                    $item.show();
                    groupHasMatch = true;
                    foundAny = true;
                } else {
                    $item.hide();
                }
            });

            if (groupHasMatch) {
                $group.show();
                $group.find('.accordion-collapse').addClass('show');
            } else {
                $group.hide();
            }
        });

        $('#no-results').toggle(!foundAny);
    });
});
</script>
@endpush
