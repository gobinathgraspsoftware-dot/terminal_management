@extends('layouts.app')

@section('title', 'Permission Matrix')

@section('styles')
<style>
    .matrix-container {
        overflow-x: auto;
    }
    .matrix-table {
        min-width: 100%;
    }
    .matrix-table th {
        position: sticky;
        top: 0;
        background: #212529;
        z-index: 10;
    }
    .matrix-table th.role-header {
        min-width: 120px;
        text-align: center;
        vertical-align: bottom;
        padding: 0.75rem 0.5rem;
    }
    .matrix-table th.permission-header {
        position: sticky;
        left: 0;
        background: #212529;
        z-index: 20;
        min-width: 250px;
    }
    .matrix-table td.permission-name {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 5;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .matrix-table tbody tr:nth-child(odd) td.permission-name {
        background: #f8f9fa;
    }
    .matrix-table td.checkbox-cell {
        text-align: center;
        vertical-align: middle;
        padding: 0.25rem;
    }
    .matrix-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    .matrix-checkbox:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }
    .role-badge-system {
        background: #6c757d;
    }
    .role-badge-custom {
        background: #198754;
    }
    .group-header-row td {
        background: #e9ecef !important;
        font-weight: bold;
        padding: 0.5rem 1rem;
    }
    .group-header-row td.permission-name {
        background: #e9ecef !important;
    }
    .saving-indicator {
        display: none;
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 1000;
    }
    .matrix-table .form-check-input {
        margin: 0;
    }
    .quick-filter-btn.active {
        background-color: #0d6efd;
        color: white;
    }
    .role-stats {
        font-size: 0.7rem;
        opacity: 0.8;
    }
    .permission-row.hidden,
    .group-header-row.hidden {
        display: none !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Saving Indicator -->
    <div class="saving-indicator alert alert-info" id="saving-indicator">
        <span class="spinner-border spinner-border-sm me-2"></span>
        Saving changes...
    </div>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Permission Matrix</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
                    <li class="breadcrumb-item active">Matrix</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.permissions.export-matrix') }}" class="btn btn-outline-success me-2">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-shield-lock me-1"></i> Manage Roles
            </a>
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-key me-1"></i> All Permissions
            </a>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-2"></i>
        <strong>How to use:</strong> Click on checkboxes to grant or revoke permissions. 
        System roles (admin, supervisor, technician) are locked and cannot be modified.
        Changes are saved automatically.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <label class="form-label">Filter by Module:</label>
                    <select class="form-select" id="filter-group">
                        <option value="">All Modules</option>
                        @foreach($permissions as $group => $data)
                            <option value="{{ $group }}">{{ $data['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search Permission:</label>
                    <input type="text" class="form-control" id="search-permission" placeholder="Search...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Quick Filters:</label>
                    <div class="btn-group w-100">
                        <button type="button" class="btn btn-outline-secondary quick-filter-btn active" data-filter="all">
                            All
                        </button>
                        <button type="button" class="btn btn-outline-secondary quick-filter-btn" data-filter="granted">
                            Granted
                        </button>
                        <button type="button" class="btn btn-outline-secondary quick-filter-btn" data-filter="not-granted">
                            Not Granted
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permission Matrix -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-grid-3x3-gap me-2"></i>Role vs Permission Matrix
            </h5>
            <div>
                <span class="badge bg-secondary me-2">{{ $roles->count() }} Roles</span>
                <span class="badge bg-primary">{{ collect($permissions)->sum(function($g) { return count($g['permissions']); }) }} Permissions</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="matrix-container">
                <table class="table table-bordered table-hover matrix-table mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="permission-header">Permission</th>
                            @foreach($roles as $role)
                                <th class="role-header">
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="badge {{ in_array($role->name, $systemRoles) ? 'role-badge-system' : 'role-badge-custom' }} mb-1">
                                            @if(in_array($role->name, $systemRoles))
                                                <i class="bi bi-lock me-1"></i>
                                            @endif
                                            {{ ucwords(str_replace(['_', '-'], ' ', $role->name)) }}
                                        </span>
                                        <span class="role-stats">
                                            {{ $role->permissions->count() }} perms
                                        </span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $group => $data)
                            <!-- Group Header -->
                            <tr class="group-header-row" data-group="{{ $group }}">
                                <td class="permission-name" colspan="{{ $roles->count() + 1 }}">
                                    <i class="bi bi-folder me-2"></i>{{ $data['label'] }}
                                    <span class="badge bg-secondary ms-2">{{ count($data['permissions']) }}</span>
                                </td>
                            </tr>
                            
                            @foreach($data['permissions'] as $permission)
                                @php
                                    $actionColors = [
                                        'view' => 'info', 'create' => 'success', 'edit' => 'warning',
                                        'delete' => 'danger', 'manage' => 'primary', 'export' => 'secondary',
                                        'import' => 'secondary', 'approve' => 'success', 'reject' => 'danger',
                                    ];
                                    $actionColor = $actionColors[$permission['action']] ?? 'secondary';
                                @endphp
                                <tr class="permission-row" 
                                    data-group="{{ $group }}" 
                                    data-permission="{{ strtolower($permission['name']) }}"
                                    data-permission-id="{{ $permission['id'] }}">
                                    <td class="permission-name">
                                        <code class="me-2">{{ $permission['name'] }}</code>
                                        <span class="badge bg-{{ $actionColor }}">{{ $permission['action'] }}</span>
                                    </td>
                                    @foreach($roles as $role)
                                        @php
                                            $hasPermission = $role->permissions->contains('id', $permission['id']);
                                            $isSystemRole = in_array($role->name, $systemRoles);
                                        @endphp
                                        <td class="checkbox-cell">
                                            <input type="checkbox" 
                                                class="form-check-input matrix-checkbox"
                                                data-role-id="{{ $role->id }}"
                                                data-permission-id="{{ $permission['id'] }}"
                                                data-role-name="{{ $role->name }}"
                                                data-permission-name="{{ $permission['name'] }}"
                                                {{ $hasPermission ? 'checked' : '' }}
                                                {{ $isSystemRole ? 'disabled' : '' }}
                                                title="{{ $isSystemRole ? 'System role - cannot modify' : 'Click to toggle' }}">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="card mt-4">
        <div class="card-body">
            <h6 class="card-title">Legend</h6>
            <div class="row">
                <div class="col-md-3">
                    <span class="badge role-badge-system me-2"><i class="bi bi-lock me-1"></i>System Role</span>
                    <small class="text-muted">Cannot be modified</small>
                </div>
                <div class="col-md-3">
                    <span class="badge role-badge-custom me-2">Custom Role</span>
                    <small class="text-muted">Can be modified</small>
                </div>
                <div class="col-md-3">
                    <input type="checkbox" class="form-check-input me-2" checked disabled>
                    <small class="text-muted">Permission granted</small>
                </div>
                <div class="col-md-3">
                    <input type="checkbox" class="form-check-input me-2" disabled>
                    <small class="text-muted">Permission not granted</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Handle permission toggle via AJAX
    $('.matrix-checkbox:not(:disabled)').on('change', function() {
        var checkbox = $(this);
        var roleId = checkbox.data('role-id');
        var permissionId = checkbox.data('permission-id');
        var granted = checkbox.is(':checked');
        var permissionName = checkbox.data('permission-name');

        // Show saving indicator
        $('#saving-indicator').fadeIn();

        $.ajax({
            url: '{{ route("admin.permissions.update-matrix") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                role_id: roleId,
                permission_id: permissionId,
                granted: granted ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message);
                } else {
                    // Revert checkbox
                    checkbox.prop('checked', !granted);
                    showToast('danger', response.message || 'Failed to update permission');
                }
            },
            error: function(xhr) {
                // Revert checkbox
                checkbox.prop('checked', !granted);
                var message = xhr.responseJSON?.message || 'Failed to update permission';
                showToast('danger', message);
            },
            complete: function() {
                $('#saving-indicator').fadeOut();
            }
        });
    });

    // Filter by group - FIXED
    $('#filter-group').on('change', function() {
        var selectedGroup = $(this).val();
        
        if (selectedGroup === '') {
            $('.permission-row').removeClass('hidden');
            $('.group-header-row').removeClass('hidden');
        } else {
            $('.permission-row').addClass('hidden');
            $('.group-header-row').addClass('hidden');
            $('.permission-row[data-group="' + selectedGroup + '"]').removeClass('hidden');
            $('.group-header-row[data-group="' + selectedGroup + '"]').removeClass('hidden');
        }
    });

    // Search permission - FIXED
    $('#search-permission').on('input', function() {
        var searchTerm = $(this).val().toLowerCase().trim();
        
        if (searchTerm === '') {
            $('.permission-row').removeClass('hidden');
            $('.group-header-row').removeClass('hidden');
        } else {
            $('.permission-row').each(function() {
                var $row = $(this);
                var permissionName = ($row.data('permission') || '').toString().toLowerCase();
                var matches = permissionName.indexOf(searchTerm) !== -1;
                
                if (matches) {
                    $row.removeClass('hidden');
                } else {
                    $row.addClass('hidden');
                }
            });
            
            // Show/hide group headers based on visible rows
            $('.group-header-row').each(function() {
                var $header = $(this);
                var group = $header.data('group');
                var visibleRows = $('.permission-row[data-group="' + group + '"]:not(.hidden)').length;
                
                if (visibleRows > 0) {
                    $header.removeClass('hidden');
                } else {
                    $header.addClass('hidden');
                }
            });
        }
    });

    // Quick filters - FIXED
    $('.quick-filter-btn').on('click', function() {
        $('.quick-filter-btn').removeClass('active');
        $(this).addClass('active');
        
        var filter = $(this).data('filter');
        applyQuickFilter(filter);
    });

    function applyQuickFilter(filter) {
        // Reset search and group filter
        $('#search-permission').val('');
        $('#filter-group').val('');
        
        if (filter === 'all') {
            $('.permission-row').removeClass('hidden');
            $('.group-header-row').removeClass('hidden');
        } else if (filter === 'granted') {
            $('.permission-row').each(function() {
                var $row = $(this);
                var hasGranted = $row.find('.matrix-checkbox:checked').length > 0;
                
                if (hasGranted) {
                    $row.removeClass('hidden');
                } else {
                    $row.addClass('hidden');
                }
            });
            updateGroupHeaderVisibility();
        } else if (filter === 'not-granted') {
            $('.permission-row').each(function() {
                var $row = $(this);
                var hasNotGranted = $row.find('.matrix-checkbox:not(:checked)').length > 0;
                
                if (hasNotGranted) {
                    $row.removeClass('hidden');
                } else {
                    $row.addClass('hidden');
                }
            });
            updateGroupHeaderVisibility();
        }
    }

    function updateGroupHeaderVisibility() {
        $('.group-header-row').each(function() {
            var $header = $(this);
            var group = $header.data('group');
            var visibleRows = $('.permission-row[data-group="' + group + '"]:not(.hidden)').length;
            
            if (visibleRows > 0) {
                $header.removeClass('hidden');
            } else {
                $header.addClass('hidden');
            }
        });
    }

    // Toast notification helper
    function showToast(type, message) {
        var toastHtml = '<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">' +
            '<div class="toast show" role="alert">' +
            '<div class="toast-header bg-' + type + ' text-white">' +
            '<i class="bi bi-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + ' me-2"></i>' +
            '<strong class="me-auto">' + (type === 'success' ? 'Success' : 'Error') + '</strong>' +
            '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>' +
            '</div>' +
            '<div class="toast-body">' + message + '</div>' +
            '</div></div>';
        
        // Remove existing toasts
        $('.position-fixed.bottom-0.end-0').remove();
        
        // Add new toast
        $('body').append(toastHtml);
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            $('.position-fixed.bottom-0.end-0').fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
});
</script>
@endsection
