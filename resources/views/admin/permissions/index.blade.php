@extends('layouts.app')

@section('title', 'Permission Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Permission Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Permissions</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('create', \Spatie\Permission\Models\Permission::class)
            <a href="{{ route('admin.permissions.create') }}" class="btn btn-success me-2">
                <i class="bi bi-plus-circle me-1"></i> Create Permission
            </a>
            @endcan
            <a href="{{ route('admin.permissions.matrix') }}" class="btn btn-primary me-2">
                <i class="bi bi-grid-3x3-gap me-1"></i> Permission Matrix
            </a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-shield-lock me-1"></i> Manage Roles
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-key-fill text-primary fs-4"></i>
                        </div>
                    </div>
                    <h3 class="text-primary mb-1">{{ $stats['total_permissions'] }}</h3>
                    <small class="text-muted">Total Permissions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        </div>
                    </div>
                    <h3 class="text-success mb-1">{{ $stats['permissions_in_use'] }}</h3>
                    <small class="text-muted">In Use</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-4"></i>
                        </div>
                    </div>
                    <h3 class="text-warning mb-1">{{ $stats['unused_permissions'] }}</h3>
                    <small class="text-muted">Unused</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="bi bi-collection-fill text-info fs-4"></i>
                        </div>
                    </div>
                    <h3 class="text-info mb-1">{{ $stats['permission_groups'] }}</h3>
                    <small class="text-muted">Permission Groups</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-ul me-2"></i>All Permissions
            </h5>
            <div>
                <button class="btn btn-sm btn-outline-primary" id="refreshTable">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="permissions-table" class="table table-hover align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th width="80">ID</th>
                            <th>Permission Name</th>
                            <th>Display Name</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th width="100" class="text-center">Roles</th>
                            <th width="150" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data populated by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Action color mapping
    const actionColors = {
        'view': 'info',
        'view_all': 'info',
        'view_team': 'info',
        'view_own': 'info',
        'view_history': 'info',
        'create': 'success',
        'edit': 'warning',
        'delete': 'danger',
        'restore': 'secondary',
        'approve': 'primary',
        'reject': 'danger',
        'submit': 'primary',
        'cancel': 'danger',
        'void': 'danger',
        'export': 'secondary',
        'import': 'secondary',
        'print': 'secondary',
        'send': 'primary',
        'assign': 'primary',
        'reassign': 'warning',
        'transfer': 'warning',
        'adjust': 'warning',
        'receive': 'success',
        'dispatch': 'info',
        'post': 'success',
        'complete': 'success',
        'start': 'info',
        'fail': 'danger',
        'close': 'secondary',
        'convert': 'primary',
        'manage': 'dark',
    };

    // Initialize DataTable
    const table = $('#permissions-table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ route("admin.permissions.index") }}',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            dataSrc: 'data',
            error: function(xhr, error, code) {
                console.error('DataTable AJAX Error:', error, code);
                console.error('Response:', xhr.responseText);
                showToast('Failed to load permissions. Check console for details.', 'error');
            }
        },
        columns: [
            {
                data: 'id',
                name: 'id',
                className: 'text-center'
            },
            {
                data: 'name',
                name: 'name',
                render: function(data) {
                    return '<code class="text-primary">' + data + '</code>';
                }
            },
            {
                data: 'display_name',
                name: 'display_name'
            },
            {
                data: 'group_label',
                name: 'group',
                render: function(data) {
                    return '<span class="badge bg-secondary">' + data + '</span>';
                }
            },
            {
                data: 'action',
                name: 'action',
                render: function(data) {
                    const color = actionColors[data] || 'secondary';
                    return '<span class="badge bg-' + color + '">' + data + '</span>';
                }
            },
            {
                data: 'roles_count',
                name: 'roles_count',
                className: 'text-center',
                render: function(data) {
                    const badgeClass = data > 0 ? 'bg-success' : 'bg-warning';
                    return '<span class="badge ' + badgeClass + '">' + data + '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    let html = '<div class="btn-group btn-group-sm" role="group">';

                    // Edit button
                    html += `<a href="{{ url('admin/permissions') }}/${row.id}/edit"
                               class="btn btn-outline-primary"
                               data-bs-toggle="tooltip"
                               title="Edit Permission">
                                <i class="bi bi-pencil"></i>
                            </a>`;

                    // Delete button
                    html += `<button type="button"
                                    class="btn btn-outline-danger delete-permission"
                                    data-id="${row.id}"
                                    data-name="${row.name}"
                                    data-roles="${row.roles_count}"
                                    data-bs-toggle="tooltip"
                                    title="Delete Permission">
                                <i class="bi bi-trash"></i>
                            </button>`;

                    html += '</div>';
                    return html;
                }
            }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: "_INPUT_",
            searchPlaceholder: "Search permissions...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ permissions",
            infoEmpty: "No permissions found",
            infoFiltered: "(filtered from _MAX_ total permissions)",
            zeroRecords: "No matching permissions found",
            emptyTable: "No permissions available"
        },
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        drawCallback: function() {
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Refresh table button
    $('#refreshTable').on('click', function() {
        table.ajax.reload(null, false);
        showToast('Table refreshed', 'success');
    });

    // Delete permission handler
    $(document).on('click', '.delete-permission', function() {
        const permissionId = $(this).data('id');
        const permissionName = $(this).data('name');
        const rolesCount = $(this).data('roles');

        let message = 'Are you sure you want to delete the permission "' + permissionName + '"?';
        if (rolesCount > 0) {
            message += '\n\nThis permission is currently assigned to ' + rolesCount + ' role(s) and will be removed from all of them.';
        }
        message += '\n\nThis action cannot be undone!';

        if (confirm(message)) {
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                url: '{{ url("admin/permissions") }}/' + permissionId,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showToast('Permission deleted successfully', 'success');
                    table.ajax.reload(null, false);
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(originalHtml);
                    const message = xhr.responseJSON?.message || 'Failed to delete permission';
                    showToast(message, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        }
    });
});
</script>
@endpush
