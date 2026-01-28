@extends('layouts.app')

@section('title', 'Edit Role - ' . ucwords(str_replace(['_', '-'], ' ', $role->name)))

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
    .changed-indicator {
        display: none;
    }
    .permission-checkbox.changed + label .changed-indicator {
        display: inline-block;
        color: #ffc107;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Role: {{ ucwords(str_replace(['_', '-'], ' ', $role->name)) }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-outline-info me-2">
                <i class="bi bi-eye me-1"></i> View Role
            </a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Roles
            </a>
        </div>
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

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.roles.update', $role) }}" method="POST" id="role-form">
        @csrf
        @method('PUT')
        
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
                                value="{{ old('name', $role->name) }}"
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
                                value="{{ old('display_name', ucwords(str_replace(['_', '-'], ' ', $role->name))) }}"
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

                        <div class="mb-3">
                            <label class="form-label">Role Info</label>
                            <ul class="list-unstyled mb-0">
                                <li><small class="text-muted">Guard:</small> <code>{{ $role->guard_name }}</code></li>
                                <li><small class="text-muted">Created:</small> {{ $role->created_at->format('M d, Y H:i') }}</li>
                                <li><small class="text-muted">Updated:</small> {{ $role->updated_at->format('M d, Y H:i') }}</li>
                                <li><small class="text-muted">Users:</small> <span class="badge bg-info">{{ $role->users()->count() }}</span></li>
                            </ul>
                        </div>

                        <input type="hidden" name="guard_name" value="{{ $role->guard_name }}">
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
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="reset-permissions">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Original
                            </button>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Selected:</span>
                            <span class="badge bg-primary" id="selected-count">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Changed:</span>
                            <span class="badge bg-warning" id="changed-count">0</span>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Update Role
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
                            <i class="bi bi-key me-2"></i>Manage Permissions
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
                                        <span class="badge bg-success ms-1 group-selected-count" id="group-count-{{ $group }}">0</span>
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
                                            @php
                                                $isChecked = in_array($permission['id'], old('permissions', $rolePermissions));
                                                $wasOriginallyChecked = in_array($permission['id'], $rolePermissions);
                                            @endphp
                                            <div class="col-md-6 col-lg-4 permission-item" data-permission-name="{{ strtolower($permission['name']) }}">
                                                <div class="form-check">
                                                    <input class="form-check-input permission-checkbox" 
                                                        type="checkbox" 
                                                        name="permissions[]" 
                                                        value="{{ $permission['id'] }}" 
                                                        id="permission-{{ $permission['id'] }}"
                                                        data-group="{{ $group }}"
                                                        data-action="{{ $permission['action'] }}"
                                                        data-original="{{ $wasOriginallyChecked ? '1' : '0' }}"
                                                        {{ $isChecked ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="permission-{{ $permission['id'] }}">
                                                        {{ $permission['display_name'] }}
                                                        <i class="bi bi-circle-fill changed-indicator" title="Changed"></i>
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
    // Store original permission states
    var originalPermissions = [];
    $('.permission-checkbox').each(function() {
        if ($(this).data('original') === 1) {
            originalPermissions.push($(this).val());
        }
    });

    // Update selected count
    function updateSelectedCount() {
        var count = $('.permission-checkbox:checked').length;
        $('#selected-count').text(count);
    }

    // Update changed count
    function updateChangedCount() {
        var changedCount = 0;
        $('.permission-checkbox').each(function() {
            var isChecked = $(this).is(':checked');
            var wasOriginal = $(this).data('original') === 1;
            
            if (isChecked !== wasOriginal) {
                $(this).addClass('changed');
                changedCount++;
            } else {
                $(this).removeClass('changed');
            }
        });
        $('#changed-count').text(changedCount);
    }

    // Update group counts
    function updateGroupCounts() {
        $('.permission-group').each(function() {
            var group = $(this).data('group');
            var checkedCount = $(this).find('.permission-checkbox:checked').length;
            $('#group-count-' + group).text(checkedCount);
        });
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
        updateChangedCount();
        updateGroupCounts();
    });

    // Group select all change
    $('.group-select-all').on('change', function() {
        var group = $(this).data('group');
        var isChecked = $(this).is(':checked');
        $('.permission-checkbox[data-group="' + group + '"]').prop('checked', isChecked);
        updateSelectedCount();
        updateChangedCount();
        updateGroupCounts();
    });

    // Select all permissions
    $('#select-all').on('click', function() {
        $('.permission-checkbox').prop('checked', true);
        $('.group-select-all').prop('checked', true).prop('indeterminate', false);
        updateSelectedCount();
        updateChangedCount();
        updateGroupCounts();
    });

    // Deselect all permissions
    $('#deselect-all').on('click', function() {
        $('.permission-checkbox').prop('checked', false);
        $('.group-select-all').prop('checked', false).prop('indeterminate', false);
        updateSelectedCount();
        updateChangedCount();
        updateGroupCounts();
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
        updateChangedCount();
        updateGroupCounts();
    });

    // Reset to original permissions
    $('#reset-permissions').on('click', function() {
        $('.permission-checkbox').each(function() {
            var wasOriginal = $(this).data('original') === 1;
            $(this).prop('checked', wasOriginal);
        });
        
        // Update all group select all checkboxes
        $('.group-select-all').each(function() {
            var group = $(this).data('group');
            updateGroupSelectAll(group);
        });
        
        updateSelectedCount();
        updateChangedCount();
        updateGroupCounts();
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

    // Collapse icon rotation
    $('.permission-group-header').on('click', function() {
        var icon = $(this).find('.collapse-icon');
        icon.toggleClass('bi-chevron-down bi-chevron-right');
    });

    // Initialize counts
    updateSelectedCount();
    updateChangedCount();
    updateGroupCounts();

    // Initialize group select all states
    $('.group-select-all').each(function() {
        var group = $(this).data('group');
        updateGroupSelectAll(group);
    });

    // Warn before leaving with unsaved changes
    var formChanged = false;
    $('#role-form').on('change', function() {
        formChanged = true;
    });

    $(window).on('beforeunload', function() {
        if (formChanged && parseInt($('#changed-count').text()) > 0) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });

    $('#role-form').on('submit', function() {
        formChanged = false;
    });
});
</script>
@endsection
