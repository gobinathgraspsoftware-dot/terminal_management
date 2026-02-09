@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-users"></i> User Management
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </nav>
        </div>
        @can('create_users')
        <div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New User
            </a>
        </div>
        @endcan
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filters
            </h6>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="roleFilter" class="form-label">Role</label>
                            <select class="form-select" id="roleFilter" name="role">
                                <option value="">All Roles</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="statusFilter" class="form-label">Status</label>
                            <select class="form-select" id="statusFilter" name="status">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    @if(auth()->user()->hasRole('admin'))
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="supervisorFilter" class="form-label">Supervisor</label>
                            <select class="form-select" id="supervisorFilter" name="supervisor_id">
                                <option value="">All Supervisors</option>
                                @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                                @endforeach
                                <option value="null">Independent (No Supervisor)</option>
                            </select>
                        </div>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-primary w-100" id="applyFilter">
                                    <i class="fas fa-search"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Users List
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="usersTable" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            @if(auth()->user()->hasRole('admin'))
                            <th>Supervisor</th>
                            @endif
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables will populate this -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="viewUserModalLabel">
                    <i class="fas fa-user"></i> User Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user?</p>
                <p class="text-muted small">This action will soft delete the user. You can restore it later if needed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete User</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<style>
    /* Table Improvements */
    #usersTable {
        font-size: 0.9rem;
    }

    #usersTable thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        vertical-align: middle;
        white-space: nowrap;
    }

    #usersTable tbody td {
        vertical-align: middle;
        padding: 0.75rem 0.5rem;
    }

    /* Action buttons styling */
    .btn-group {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 4px;
    }

    .btn-group .btn {
        border-radius: 0;
    }

    .btn-group .btn:first-child {
        border-top-left-radius: 4px;
        border-bottom-left-radius: 4px;
    }

    .btn-group .btn:last-child {
        border-top-right-radius: 4px;
        border-bottom-right-radius: 4px;
    }

    /* Avatar and name alignment */
    #usersTable .d-flex {
        align-items: center;
    }

    /* Status badges */
    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
        font-weight: 500;
    }

    /* Role badges */
    .badge.bg-danger,
    .badge.bg-primary,
    .badge.bg-success {
        font-size: 0.75rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        #usersTable {
            font-size: 0.85rem;
        }

        .btn-group .btn {
            padding: 0.25rem 0.4rem;
            font-size: 0.8rem;
        }
    }

    /* DataTables processing indicator */
    .dataTables_processing {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* Hover effect for table rows */
    #usersTable tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>
@endpush

@push('scripts')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    let table = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("admin.users.datatable") }}',
            data: function(d) {
                d.role = $('#roleFilter').val();
                d.status = $('#statusFilter').val();
                d.supervisor_id = $('#supervisorFilter').val();
            }
        },
        columns: [
            {
                data: 'employee_id',
                name: 'employee_id',
                width: '10%'
            },
            {
                data: 'name',
                name: 'name',
                width: '20%',
                render: function(data, type, row) {
                    let avatar = row.avatar
                        ? '<img src="/storage/' + row.avatar + '" class="rounded-circle me-2" width="32" height="32" alt="Avatar">'
                        : '<div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;font-size:14px;">' + data.charAt(0).toUpperCase() + '</div>';
                    return '<div class="d-flex align-items-center">' + avatar + '<span>' + data + '</span></div>';
                }
            },
            {
                data: 'email',
                name: 'email',
                width: '20%'
            },
            {
                data: 'role',
                name: 'role',
                orderable: false,
                searchable: false,
                width: '12%'
            },
            @if(auth()->user()->hasRole('admin'))
            {
                data: 'supervisor_name',
                name: 'supervisor.name',
                defaultContent: '-',
                width: '15%'
            },
            @endif
            {
                data: 'status_badge',
                name: 'status',
                orderable: true,
                searchable: false,
                width: '10%'
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                width: '13%',
                className: 'text-center'
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No users found',
            zeroRecords: 'No matching users found'
        },
        drawCallback: function() {
            // Initialize Bootstrap tooltips after table draw
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    });

    // Apply filters
    $('#applyFilter').on('click', function() {
        table.ajax.reload();
    });

    // Reset filters on Enter key
    $('#filterForm').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            table.ajax.reload();
        }
    });

    // View User
    let deleteUserId = null;

    $(document).on('click', '.view-user', function() {
        const userId = $(this).data('id');
        $('#userDetailsContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);

        $('#viewUserModal').modal('show');

        $.ajax({
            url: '/admin/users/' + userId,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const user = response.user;
                    let html = `
                        <div class="row">
                            <div class="col-md-4 text-center mb-3">
                                ${user.avatar
                                    ? '<img src="/storage/' + user.avatar + '" class="img-fluid rounded-circle" style="max-width: 150px;" alt="Avatar">'
                                    : '<div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:150px;height:150px;font-size:48px;">' + user.name.charAt(0).toUpperCase() + '</div>'
                                }
                            </div>
                            <div class="col-md-8">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Employee ID:</th>
                                        <td>${user.employee_id || '-'}</td>
                                    </tr>
                                    <tr>
                                        <th>Name:</th>
                                        <td>${user.name}</td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td>${user.email}</td>
                                    </tr>
                                    <tr>
                                        <th>Phone:</th>
                                        <td>${user.phone || '-'}</td>
                                    </tr>
                                    <tr>
                                        <th>Role:</th>
                                        <td>${user.roles.map(r => '<span class="badge bg-primary">' + r.name + '</span>').join(' ')}</td>
                                    </tr>
                                    ${user.supervisor ? `
                                    <tr>
                                        <th>Supervisor:</th>
                                        <td>${user.supervisor.name}</td>
                                    </tr>
                                    ` : ''}
                                    <tr>
                                        <th>Status:</th>
                                        <td><span class="badge bg-${user.status === 'active' ? 'success' : user.status === 'inactive' ? 'warning' : 'danger'}">${user.status}</span></td>
                                    </tr>
                                    ${user.coverage_states ? `
                                    <tr>
                                        <th>Coverage States:</th>
                                        <td>${Array.isArray(user.coverage_states) ? user.coverage_states.join(', ') : (typeof user.coverage_states === 'string' && user.coverage_states.startsWith('[') ? JSON.parse(user.coverage_states).join(', ') : user.coverage_states)}</td>
                                    </tr>
                                    ` : ''}
                                    ${user.skill_tags ? `
                                    <tr>
                                        <th>Skills:</th>
                                        <td>${(Array.isArray(user.skill_tags) ? user.skill_tags : (typeof user.skill_tags === 'string' && user.skill_tags.startsWith('[') ? JSON.parse(user.skill_tags) : [user.skill_tags])).map(s => '<span class="badge bg-info text-dark">' + s + '</span>').join(' ')}</td>
                                    </tr>
                                    ` : ''}
                                </table>
                            </div>
                        </div>
                    `;
                    $('#userDetailsContent').html(html);
                }
            },
            error: function(xhr) {
                $('#userDetailsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> Error loading user details
                    </div>
                `);
            }
        });
    });

    // Delete User
    $(document).on('click', '.delete-user', function() {
        deleteUserId = $(this).data('id');
        $('#deleteUserModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        if (!deleteUserId) return;

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Deleting...');

        $.ajax({
            url: '/admin/users/' + deleteUserId,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#deleteUserModal').modal('hide');
                table.ajax.reload();

                // Show success message
                const alert = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> ${response.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                $('.container-fluid').prepend(alert);

                setTimeout(() => {
                    $('.alert').fadeOut();
                }, 3000);
            },
            error: function(xhr) {
                $('#deleteUserModal').modal('hide');

                const message = xhr.responseJSON?.message || 'Failed to delete user';
                const alert = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                $('.container-fluid').prepend(alert);
            },
            complete: function() {
                btn.prop('disabled', false).html('Delete User');
                deleteUserId = null;
            }
        });
    });

    // Status Toggle
    $(document).on('change', '.status-toggle', function() {
        const userId = $(this).data('id');
        const newStatus = $(this).is(':checked') ? 'active' : 'inactive';
        const toggle = $(this);

        $.ajax({
            url: '/admin/users/' + userId + '/toggle-status',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { status: newStatus },
            success: function(response) {
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                // Revert toggle on error
                toggle.prop('checked', !toggle.is(':checked'));
                alert('Failed to update status');
            }
        });
    });
});
</script>
@endpush
