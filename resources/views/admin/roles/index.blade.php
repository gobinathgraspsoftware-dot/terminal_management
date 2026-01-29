@extends('layouts.app')

@section('title', 'Role Management')

@section('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .stat-card {
        transition: transform 0.2s ease-in-out;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .badge-system {
        background-color: #6c757d;
        color: white;
    }
    .badge-custom {
        background-color: #198754;
        color: white;
    }
    .table-actions .btn-group {
        white-space: nowrap;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Role Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Roles</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.permissions.matrix') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-grid-3x3-gap me-1"></i> Permission Matrix
            </a>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Create Role
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card stat-card h-100 border-primary">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-1">{{ $stats['total_roles'] }}</h3>
                    <small class="text-muted">Total Roles</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card stat-card h-100 border-secondary">
                <div class="card-body text-center">
                    <h3 class="text-secondary mb-1">{{ $stats['system_roles'] }}</h3>
                    <small class="text-muted">System Roles</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card stat-card h-100 border-success">
                <div class="card-body text-center">
                    <h3 class="text-success mb-1">{{ $stats['custom_roles'] }}</h3>
                    <small class="text-muted">Custom Roles</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card stat-card h-100 border-info">
                <div class="card-body text-center">
                    <h3 class="text-info mb-1">{{ $stats['total_permissions'] }}</h3>
                    <small class="text-muted">Permissions</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card stat-card h-100 border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-1">{{ $stats['roles_with_users'] }}</h3>
                    <small class="text-muted">Roles In Use</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card stat-card h-100 border-danger">
                <div class="card-body text-center">
                    <h3 class="text-danger mb-1">{{ $stats['unused_roles'] }}</h3>
                    <small class="text-muted">Unused Roles</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Roles Table -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-shield-lock me-2"></i>All Roles
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="roles-table" class="table table-striped table-hover" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Role Name</th>
                            <th>Type</th>
                            <th>Permissions</th>
                            <th>Users</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            @php
                                $isSystem = in_array($role->name, ['admin', 'supervisor', 'technician']);
                            @endphp
                            <tr>
                                <td>{{ $role->id }}</td>
                                <td>
                                    <strong>{{ ucwords(str_replace(['_', '-'], ' ', $role->name)) }}</strong>
                                    <br><small class="text-muted">{{ $role->name }}</small>
                                </td>
                                <td>
                                    @if($isSystem)
                                        <span class="badge badge-system"><i class="bi bi-lock me-1"></i>System</span>
                                    @else
                                        <span class="badge badge-custom"><i class="bi bi-person-gear me-1"></i>Custom</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $role->permissions_count > 0 ? 'bg-info' : 'bg-secondary' }}">
                                        {{ $role->permissions_count }} permissions
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $role->users_count > 0 ? 'bg-success' : 'bg-warning' }}">
                                        {{ $role->users_count }} users
                                    </span>
                                </td>
                                <td>{{ $role->created_at ? $role->created_at->format('M d, Y') : '-' }}</td>
                                <td class="table-actions">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($isSystem)
                                            <button type="button" class="btn btn-secondary" disabled title="System Role">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        @else
                                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-delete" 
                                                data-id="{{ $role->id }}" 
                                                data-name="{{ $role->name }}" 
                                                data-users="{{ $role->users_count }}"
                                                title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <button type="button" class="btn btn-success btn-clone" 
                                                data-id="{{ $role->id }}" 
                                                data-name="{{ $role->name }}"
                                                title="Clone">
                                                <i class="bi bi-copy"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Delete Role</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the role <strong id="delete-role-name"></strong>?</p>
                <div class="alert alert-warning" id="delete-warning" style="display: none;">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    This role has <strong id="delete-users-count"></strong> user(s) assigned. 
                    Please reassign them before deleting.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">
                    <i class="bi bi-trash me-1"></i>Delete Role
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Clone Modal -->
<div class="modal fade" id="cloneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-copy me-2"></i>Clone Role</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="clone-form">
                <div class="modal-body">
                    <p>Clone all permissions from <strong id="clone-source-name"></strong> to a new role.</p>
                    <div class="mb-3">
                        <label for="clone-name" class="form-label">New Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="clone-name" name="name" required
                            placeholder="Enter new role name">
                    </div>
                    <div class="mb-3">
                        <label for="clone-description" class="form-label">Description</label>
                        <textarea class="form-control" id="clone-description" name="description" rows="2" 
                            placeholder="Optional description for this role"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-copy me-1"></i>Clone Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable (client-side)
    var table = $('#roles-table').DataTable({
        responsive: true,
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        language: {
            emptyTable: 'No roles found',
            zeroRecords: 'No matching roles found'
        },
        columnDefs: [
            { orderable: false, targets: [6] }
        ]
    });

    // Delete role
    var deleteRoleId = null;
    
    $(document).on('click', '.btn-delete', function() {
        deleteRoleId = $(this).data('id');
        var roleName = $(this).data('name');
        var usersCount = $(this).data('users');
        
        $('#delete-role-name').text(roleName);
        
        if (usersCount > 0) {
            $('#delete-warning').show();
            $('#delete-users-count').text(usersCount);
            $('#confirm-delete').prop('disabled', true);
        } else {
            $('#delete-warning').hide();
            $('#confirm-delete').prop('disabled', false);
        }
        
        $('#deleteModal').modal('show');
    });

    $('#confirm-delete').on('click', function() {
        if (!deleteRoleId) return;
        
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Deleting...');
        
        $.ajax({
            url: '{{ url("admin/roles") }}/' + deleteRoleId,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#deleteModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to delete role.';
                alert(message);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i>Delete Role');
                deleteRoleId = null;
            }
        });
    });

    // Clone role
    var cloneRoleId = null;
    
    $(document).on('click', '.btn-clone', function() {
        cloneRoleId = $(this).data('id');
        var roleName = $(this).data('name');
        
        $('#clone-source-name').text(roleName);
        $('#clone-name').val('');
        $('#clone-description').val('');
        
        $('#cloneModal').modal('show');
    });

    $('#clone-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!cloneRoleId) return;
        
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Cloning...');
        
        $.ajax({
            url: '{{ url("admin/roles") }}/' + cloneRoleId + '/clone',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                name: $('#clone-name').val(),
                description: $('#clone-description').val()
            },
            success: function(response) {
                $('#cloneModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to clone role.';
                if (xhr.responseJSON?.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join('\n');
                }
                alert(message);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-copy me-1"></i>Clone Role');
            }
        });
    });
});
</script>
@endsection
