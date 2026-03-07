@extends('layouts.app')

@section('title', 'User Management - TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-people me-2"></i>User Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </nav>
        </div>
        @can('create_users')
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Add New User
        </a>
        @endcan
    </div>

    <div class="row mb-4" id="statsRow">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary fs-3 fw-bold" id="statTotal">-</div>
                    <small class="text-muted">Total Users</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-success fs-3 fw-bold" id="statActive">-</div>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-warning fs-3 fw-bold" id="statInactive">-</div>
                    <small class="text-muted">Inactive</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-danger fs-3 fw-bold" id="statSuspended">-</div>
                    <small class="text-muted">Suspended</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Role</label>
                    <select id="filterRole" class="form-select">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Supervisor</label>
                    <select id="filterSupervisor" class="form-select">
                        <option value="">All</option>
                        <option value="null">No Supervisor</option>
                        @foreach($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" id="btnResetFilters" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Employee ID</th>
                            <th>Role</th>
                            <th>Supervisor</th>
                            <th>Status</th>
                            <th width="12%">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>User Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewUserBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.users.datatable") }}',
            data: function(d) {
                d.role = $('#filterRole').val();
                d.status = $('#filterStatus').val();
                d.supervisor_id = $('#filterSupervisor').val();
            }
        },
        columns: [
            { data: null, name: 'id', orderable: false, searchable: false, render: function(data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            }},
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'employee_id', name: 'employee_id', defaultContent: '-' },
            { data: 'role', name: 'role', orderable: false, searchable: false },
            { data: 'supervisor_name', name: 'supervisor_name', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...',
            emptyTable: 'No users found'
        },
        drawCallback: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    $('#filterRole, #filterStatus, #filterSupervisor').on('change', function() {
        table.draw();
    });

    $('#btnResetFilters').on('click', function() {
        $('#filterRole, #filterStatus, #filterSupervisor').val('');
        table.draw();
    });

    // View user modal — shows state/city info
    $(document).on('click', '.view-user', function() {
        var userId = $(this).data('id');
        $('#viewUserBody').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
        $('#viewUserModal').modal('show');

        $.ajax({
            url: '{{ url("admin/users") }}/' + userId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var user = response.user;
                    var roleBadge = user.roles && user.roles.length > 0
                        ? '<span class="badge bg-primary">' + user.roles[0].name + '</span>'
                        : '<span class="badge bg-secondary">No Role</span>';

                    var statusClass = user.status === 'active' ? 'success' : (user.status === 'inactive' ? 'warning' : 'danger');

                    var avatarUrl = user.avatar_url;
                    var fallbackUrl = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name) + '&size=200&background=random';

                    var stateName = user.state ? user.state.name : '-';
                    var cityName = user.city ? user.city.name + ' (' + user.city.postcode + ')' : '-';

                    var html = '<div class="row">';
                    html += '<div class="col-md-4 text-center mb-3">';
                    html += '<img src="' + avatarUrl + '" class="rounded-circle img-fluid" style="width:120px;height:120px;object-fit:cover;" alt="' + user.name + '" onerror="this.onerror=null;this.src=\'' + fallbackUrl + '\';">';
                    html += '<h5 class="mt-2 mb-0">' + user.name + '</h5>';
                    html += '<p class="text-muted">' + roleBadge + '</p>';
                    html += '</div>';
                    html += '<div class="col-md-8">';
                    html += '<table class="table table-sm">';
                    html += '<tr><th width="35%">Email</th><td>' + user.email + '</td></tr>';
                    html += '<tr><th>Phone</th><td>' + (user.phone || '-') + '</td></tr>';
                    html += '<tr><th>Employee ID</th><td>' + (user.employee_id || '-') + '</td></tr>';
                    html += '<tr><th>Status</th><td><span class="badge bg-' + statusClass + '">' + (user.status ? user.status.charAt(0).toUpperCase() + user.status.slice(1) : '-') + '</span></td></tr>';
                    html += '<tr><th>Supervisor</th><td>' + (user.supervisor ? user.supervisor.name : 'None') + '</td></tr>';
                    html += '<tr><th>State</th><td>' + stateName + '</td></tr>';
                    html += '<tr><th>City</th><td>' + cityName + '</td></tr>';
                    html += '<tr><th>Address</th><td>' + (user.address || '-') + '</td></tr>';
                    html += '</table>';
                    html += '</div></div>';
                    $('#viewUserBody').html(html);
                }
            },
            error: function() {
                $('#viewUserBody').html('<div class="alert alert-danger">Failed to load user details.</div>');
            }
        });
    });

    $(document).on('click', '.delete-user', function() {
        var userId = $(this).data('id');
        confirmAction('Delete User', 'Are you sure you want to delete this user?', function() {
            $.ajax({
                url: '{{ url("admin/users") }}/' + userId,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    if (response.success) {
                        showToast(response.message);
                        table.draw();
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function(xhr) {
                    showToast(xhr.responseJSON?.message || 'Failed to delete user', 'error');
                }
            });
        });
    });

    $(document).on('click', '.restore-user', function() {
        var userId = $(this).data('id');
        confirmAction('Restore User', 'Are you sure you want to restore this user?', function() {
            $.ajax({
                url: '{{ url("admin/users") }}/' + userId + '/restore',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    if (response.success) {
                        showToast(response.message);
                        table.draw();
                    }
                },
                error: function(xhr) {
                    showToast(xhr.responseJSON?.message || 'Failed to restore user', 'error');
                }
            });
        });
    });
});
</script>
@endpush
