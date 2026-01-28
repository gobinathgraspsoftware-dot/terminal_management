@extends('layouts.app')

@section('title', 'Create Role')

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
        cursor: pointer;
    }
    .permission-group-header:hover {
        background-color: #e9ecef;
    }
    .permission-group-body {
        padding: 1rem;
    }
    .permission-item {
        padding: 0.5rem;
        border-radius: 0.25rem;
        transition: background-color 0.15s ease;
    }
    .permission-item:hover {
        background-color: #f8f9fa;
    }
    .permission-item .form-check-input:checked + .form-check-label {
        color: #198754;
        font-weight: 500;
    }
    .select-all-group {
        font-size: 0.875rem;
    }
    .badge-action {
        font-size: 0.7rem;
        padding: 0.2em 0.5em;
    }
    .quick-actions {
        position: sticky;
        top: 1rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Create New Role</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Roles
        </a>
    </div>

    <!-- Alert Messages -->
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.roles.store') }}" method="POST" id="role-form">
        @csrf
        
        <div class="row">
            <!-- Left Column - Role Details -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Role Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Role Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                class="form-control @error('name') is-invalid @enderror" 
                                id="name" 
                                name="name" 
                                value="{{ old('name') }}"
                                pattern="[a-z][a-z0-9_-]*"
                                placeholder="e.g., senior_technician"
                                required>
                            <div class="form-text">
                                Lowercase letters, numbers, underscores, and hyphens only. Must start with a letter.
                            </div>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="display_name" class="form-label">Display Name</label>
                            <input type="text" 
                                class="form-control @error('display_name') is-invalid @enderror" 
                                id="display_name" 
                                name="display_name" 
                                value="{{ old('display_name') }}"
                                placeholder="e.g., Senior Technician">
                            <div class="form-text">Human-readable name for the role.</div>
                            @error('display_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                id="description" 
                                name="description" 
                                rows="3"
                                placeholder="Describe the purpose of this role...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <input type="hidden" name="guard_name" value="web">
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="card quick-actions">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-lightning me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-success btn-sm" id="select-all">
                                <i class="bi bi-check-all me-1"></i> Select All Permissions
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="deselect-all">
                                <i class="bi bi-x-lg me-1"></i> Deselect All
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="select-view-only">
                                <i class="bi bi-eye me-1"></i> Select View Only
                            </button>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Selected:</span>
                            <span class="badge bg-primary" id="selected-count">0</span>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Create Role
                            </button>
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Permissions -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-key me-2"></i>Assign Permissions
                        </h5>
                        <div class="input-group" style="width: 250px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control form-control-sm" id="search-permissions" placeholder="Search permissions...">
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($permissions as $group => $data)
                            <div class="permission-group" data-group="{{ $group }}">
                                <div class="permission-group-header d-flex justify-content-between align-items-center" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#group-{{ $group }}">
                                    <div>
                                        <i class="bi bi-chevron-down me-2 collapse-icon"></i>
                                        <strong>{{ $data['label'] }}</strong>
                                        <span class="badge bg-secondary ms-2">{{ count($data['permissions']) }}</span>
                                    </div>
                                    <div class="form-check select-all-group" onclick="event.stopPropagation();">
                                        <input class="form-check-input group-select-all" 
                                            type="checkbox" 
                                            id="select-all-{{ $group }}"
                                            data-group="{{ $group }}">
                                        <label class="form-check-label" for="select-all-{{ $group }}">
                                            Select All
                                        </label>
                                    </div>
                                </div>
                                <div class="collapse show permission-group-body" id="group-{{ $group }}">
                                    <div class="row">
                                        @foreach($data['permissions'] as $permission)
                                            <div class="col-md-6 col-lg-4 permission-item" data-permission-name="{{ strtolower($permission['name']) }}">
                                                <div class="form-check">
                                                    <input class="form-check-input permission-checkbox" 
                                                        type="checkbox" 
                                                        name="permissions[]" 
                                                        value="{{ $permission['id'] }}" 
                                                        id="permission-{{ $permission['id'] }}"
                                                        data-group="{{ $group }}"
                                                        data-action="{{ $permission['action'] }}"
                                                        {{ in_array($permission['id'], old('permissions', [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="permission-{{ $permission['id'] }}">
                                                        {{ $permission['display_name'] }}
                                                        @php
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
                                                        <span class="badge bg-{{ $actionColor }} badge-action">
                                                            {{ $permission['action'] }}
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                                <p class="text-muted mt-2">No permissions available. Please run the permission seeder.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Update selected count
    function updateSelectedCount() {
        var count = $('.permission-checkbox:checked').length;
        $('#selected-count').text(count);
    }

    // Update group select all checkbox state
    function updateGroupSelectAll(group) {
        var groupCheckboxes = $('.permission-checkbox[data-group="' + group + '"]');
        var checkedCount = groupCheckboxes.filter(':checked').length;
        var totalCount = groupCheckboxes.length;
        
        var selectAllCheckbox = $('#select-all-' + group);
        selectAllCheckbox.prop('checked', checkedCount === totalCount);
        selectAllCheckbox.prop('indeterminate', checkedCount > 0 && checkedCount < totalCount);
    }

    // Permission checkbox change
    $('.permission-checkbox').on('change', function() {
        var group = $(this).data('group');
        updateGroupSelectAll(group);
        updateSelectedCount();
    });

    // Group select all change
    $('.group-select-all').on('change', function() {
        var group = $(this).data('group');
        var isChecked = $(this).is(':checked');
        $('.permission-checkbox[data-group="' + group + '"]').prop('checked', isChecked);
        updateSelectedCount();
    });

    // Select all permissions
    $('#select-all').on('click', function() {
        $('.permission-checkbox').prop('checked', true);
        $('.group-select-all').prop('checked', true).prop('indeterminate', false);
        updateSelectedCount();
    });

    // Deselect all permissions
    $('#deselect-all').on('click', function() {
        $('.permission-checkbox').prop('checked', false);
        $('.group-select-all').prop('checked', false).prop('indeterminate', false);
        updateSelectedCount();
    });

    // Select view only permissions
    $('#select-view-only').on('click', function() {
        $('.permission-checkbox').prop('checked', false);
        $('.permission-checkbox[data-action="view"]').prop('checked', true);
        
        // Update all group select all checkboxes
        $('.group-select-all').each(function() {
            var group = $(this).data('group');
            updateGroupSelectAll(group);
        });
        
        updateSelectedCount();
    });

    // Search permissions
    $('#search-permissions').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        
        if (searchTerm === '') {
            $('.permission-item').show();
            $('.permission-group').show();
        } else {
            $('.permission-item').each(function() {
                var permissionName = $(this).data('permission-name');
                var matches = permissionName.includes(searchTerm);
                $(this).toggle(matches);
            });
            
            // Hide empty groups
            $('.permission-group').each(function() {
                var visibleItems = $(this).find('.permission-item:visible').length;
                $(this).toggle(visibleItems > 0);
            });
        }
    });

    // Auto-generate display name from role name
    $('#name').on('input', function() {
        var name = $(this).val();
        var displayName = name.replace(/[_-]/g, ' ').replace(/\b\w/g, function(l) {
            return l.toUpperCase();
        });
        
        if ($('#display_name').val() === '' || $('#display_name').data('auto-filled')) {
            $('#display_name').val(displayName).data('auto-filled', true);
        }
    });

    $('#display_name').on('input', function() {
        $(this).data('auto-filled', false);
    });

    // Collapse icon rotation
    $('.permission-group-header').on('click', function() {
        var icon = $(this).find('.collapse-icon');
        icon.toggleClass('bi-chevron-down bi-chevron-right');
    });

    // Initialize selected count
    updateSelectedCount();

    // Initialize group select all states
    $('.group-select-all').each(function() {
        var group = $(this).data('group');
        updateGroupSelectAll(group);
    });
});
</script>
@endsection
