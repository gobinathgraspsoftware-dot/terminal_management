@extends('layouts.app')

@section('title', 'Role Management')

@section('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
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
    .table-actions .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .permission-badge {
        font-size: 0.75rem;
        margin: 2px;
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
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card stat-card h-100 border-primary">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-1">{{ $stats['total_roles'] }}</h3>
                    <small class="text-muted">Total Roles</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card stat-card h-100 border-secondary">
                <div class="card-body text-center">
                    <h3 class="text-secondary mb-1">{{ $stats['system_roles'] }}</h3>
                    <small class="text-muted">System Roles</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card stat-card h-100 border-success">
                <div class="card-body text-center">
                    <h3 class="text-success mb-1">{{ $stats['custom_roles'] }}</h3>
                    <small class="text-muted">Custom Roles</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card stat-card h-100 border-info">
                <div class="card-body text-center">
                    <h3 class="text-info mb-1">{{ $stats['total_permissions'] }}</h3>
                    <small class="text-muted">Permissions</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card stat-card h-100 border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-1">{{ $stats['roles_with_users'] }}</h3>
                    <small class="text-muted">Roles In Use</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
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
        <div class="card-header bg-white">
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the role "<strong id="delete-role-name"></strong>"?</p>
                <div class="alert alert-warning mb-0" id="delete-warning" style="display: none;">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    This role has <strong id="delete-users-count"></strong> user(s) assigned. Please reassign them before deleting.
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

<!-- Clone Role Modal -->
<div class="modal fade" id="cloneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-copy me-2"></i>Clone Role
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="clone-form">
                <div class="modal-body">
                    <p>Create a copy of "<strong id="clone-source-name"></strong>" with all its permissions.</p>
                    <div class="mb-3">
                        <label for="clone-name" class="form-label">New Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="clone-name" name="name" required
                            pattern="[a-z][a-z0-9_-]*" 
                            placeholder="e.g., senior_technician">
                        <div class="form-text">Lowercase letters, numbers, underscores, and hyphens only. Must start with a letter.</div>
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
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#roles-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("admin.roles.index") }}',
            type: 'GET'
        },
        columns: [
            { data: 'id', name: 'id', width: '5%' },
            { 
                data: 'name', 
                name: 'name',
                render: function(data, type, row) {
                    return '<strong>' + row.display_name + '</strong><br><small class="text-muted">' + data + '</small>';
                }
            },
            { 
                data: 'is_system', 
                name: 'is_system',
                render: function(data, type, row) {
                    if (data) {
                        return '<span class="badge badge-system"><i class="bi bi-lock me-1"></i>System</span>';
                    }
                    return '<span class="badge badge-custom"><i class="bi bi-person-gear me-1"></i>Custom</span>';
                }
            },
            { 
                data: 'permissions_count', 
                name: 'permissions_count',
                render: function(data, type, row) {
                    var badgeClass = data > 0 ? 'bg-info' : 'bg-secondary';
                    return '<span class="badge ' + badgeClass + '">' + data + ' permissions</span>';
                }
            },
            { 
                data: 'users_count', 
                name: 'users_count',
                render: function(data, type, row) {
                    var badgeClass = data > 0 ? 'bg-success' : 'bg-warning';
                    return '<span class="badge ' + badgeClass + '">' + data + ' users</span>';
                }
            },
            { data: 'created_at', name: 'created_at' },
            { 
                data: 'actions', 
                name: 'actions', 
                orderable: false, 
                searchable: false,
                className: 'table-actions'
            }
        ],
        order: [[1, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading...',
            emptyTable: 'No roles found',
            zeroRecords: 'No matching roles found'
        }
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
            url: '/admin/roles/' + deleteRoleId,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#deleteModal').modal('hide');
                table.ajax.reload();
                showAlert('success', response.message || 'Role deleted successfully.');
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to delete role.';
                showAlert('danger', message);
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
            url: '/admin/roles/' + cloneRoleId + '/clone',
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
                table.ajax.reload();
                showAlert('success', response.message || 'Role cloned successfully.');
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to clone role.';
                if (xhr.responseJSON?.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                showAlert('danger', message);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-copy me-1"></i>Clone Role');
                cloneRoleId = null;
            }
        });
    });

    // Show alert helper
    function showAlert(type, message) {
        var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            '<i class="bi bi-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + ' me-2"></i>' + 
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>';
        
        $('.container-fluid').find('.alert').remove();
        $('.container-fluid .row.mb-4').first().before(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.container-fluid').find('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endsection
