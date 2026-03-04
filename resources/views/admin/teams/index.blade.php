@extends('layouts.app')

@section('title', 'Team Management')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Team Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Team Management</li>
                </ol>
            </nav>
        </div>
        @if($currentView !== 'supervisors')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
            <i class="bi bi-people me-1"></i> Bulk Assign
        </button>
        @endif
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-2 col-6 mb-3">
            <div class="card h-100 border-start border-primary border-4">
                <div class="card-body text-center py-3">
                    <h3 class="text-primary mb-0">{{ $statistics['total_supervisors'] ?? 0 }}</h3>
                    <small class="text-muted">Supervisors</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card h-100 border-start border-success border-4">
                <div class="card-body text-center py-3">
                    <h3 class="text-success mb-0">{{ $statistics['total_technicians'] ?? 0 }}</h3>
                    <small class="text-muted">Technicians</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card h-100 border-start border-info border-4">
                <div class="card-body text-center py-3">
                    <h3 class="text-info mb-0">{{ $statistics['assigned_technicians'] ?? 0 }}</h3>
                    <small class="text-muted">Assigned</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card h-100 border-start border-warning border-4">
                <div class="card-body text-center py-3">
                    <h3 class="text-warning mb-0">{{ $statistics['independent_technicians'] ?? 0 }}</h3>
                    <small class="text-muted">Independent</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card h-100 border-start border-secondary border-4">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $statistics['avg_team_size'] ?? 0 }}</h3>
                    <small class="text-muted">Avg Team Size</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card h-100 border-start border-dark border-4">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0">{{ $statistics['largest_team']['team_size'] ?? 0 }}</h3>
                    <small class="text-muted">Largest Team</small>
                </div>
            </div>
        </div>
    </div>

    {{-- View Title --}}
    <div class="card">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <h5 class="mb-0">
                        @switch($currentView)
                            @case('supervisors')
                                <i class="bi bi-person-badge me-2"></i>Supervisors List
                                @break
                            @case('technicians')
                                <i class="bi bi-person-gear me-2"></i>Assigned Technicians
                                @break
                            @case('independent')
                                <i class="bi bi-person-dash me-2"></i>Independent Technicians
                                @break
                            @default
                                <i class="bi bi-diagram-3 me-2"></i>All Team Members
                        @endswitch
                    </h5>
                </div>
                @if($currentView !== 'supervisors')
                <div class="col-md-3">
                    <select id="filterSupervisor" class="form-select form-select-sm">
                        <option value="">All Supervisors</option>
                        <option value="independent">Independent Only</option>
                        @foreach($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                @if($currentView !== 'supervisors')
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-success btn-sm" id="assignSelectedBtn" disabled>
                        <i class="bi bi-person-plus me-1"></i> Assign Selected
                    </button>
                </div>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if($currentView === 'supervisors')
                {{-- Supervisors Table --}}
                <table id="supervisorsTable" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Name</th>
                            <th>Team Size</th>
                            <th>Coverage</th>
                            <th>Status</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            @else
                {{-- Technicians Table --}}
                <table id="techniciansTable" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>Employee</th>
                            <th>Name</th>
                            <th>Supervisor</th>
                            <th>Coverage</th>
                            <th>Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            @endif
        </div>
    </div>
</div>

{{-- Reassign Modal --}}
<div class="modal fade" id="reassignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Reassign Technician</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reassignForm">
                <div class="modal-body">
                    <p>Reassigning: <strong id="reassignTechnicianName"></strong></p>
                    <input type="hidden" id="reassignTechnicianId" name="technician_id">
                    <div class="mb-3">
                        <label class="form-label">Select Supervisor</label>
                        <select name="supervisor_id" id="reassignSupervisorSelect" class="form-select">
                            <option value="">-- Make Independent --</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }} ({{ $supervisor->technicians_count ?? 0 }} members)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bulk Assign Modal --}}
<div class="modal fade" id="bulkAssignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-people me-2"></i>Bulk Assign Technicians</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkAssignForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <span id="selectedCountText">Select technicians from the table first.</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign to Supervisor</label>
                        <select name="supervisor_id" id="bulkSupervisorSelect" class="form-select">
                            <option value="">-- Make Independent --</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Member Detail Modal --}}
<div class="modal fade" id="memberDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person me-2"></i>Member Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th width="40%">Name</th><td id="memberDetailName">-</td></tr>
                            <tr><th>Employee ID</th><td id="memberDetailEmployee">-</td></tr>
                            <tr><th>Email</th><td id="memberDetailEmail">-</td></tr>
                            <tr><th>Phone</th><td id="memberDetailPhone">-</td></tr>
                            <tr><th>Status</th><td id="memberDetailStatus">-</td></tr>
                            <tr><th>Supervisor</th><td id="memberDetailSupervisor">-</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6>Performance</h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4 id="memberDetailTotalJobs" class="text-primary mb-0">0</h4>
                                        <small>Total</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 id="memberDetailCompletedJobs" class="text-success mb-0">0</h4>
                                        <small>Completed</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 id="memberDetailPendingJobs" class="text-warning mb-0">0</h4>
                                        <small>Pending</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="memberViewFullLink" class="btn btn-info">
                    <i class="bi bi-box-arrow-up-right me-1"></i> View Full Details
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Suppress DataTable alert popups - log to console instead
    $.fn.dataTable.ext.errMode = 'none';

    var currentView = '{{ $currentView }}';

    @if($currentView === 'supervisors')
    // Initialize Supervisors DataTable
    var table = $('#supervisorsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.teams.datatable") }}',
            data: function(d) {
                d.view = 'supervisors';
                d.status = $('#filterStatus').val();
            },
            error: function(xhr, error, thrown) {
                console.error('Supervisors DataTable Error:', xhr.status, xhr.responseText);
            }
        },
        columns: [
            { data: 'employee_id', name: 'employee_id' },
            { data: 'name', name: 'name' },
            { data: 'team_count', name: 'team_count', searchable: false, orderable: false },
            { data: 'coverage', name: 'coverage', searchable: false, orderable: false },
            { data: 'status_badge', name: 'status_badge', searchable: false, orderable: false },
            { data: 'actions', name: 'actions', searchable: false, orderable: false }
        ],
        order: [[1, 'asc']],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });
    @else
    // Initialize Technicians DataTable
    var table = $('#techniciansTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.teams.datatable") }}',
            data: function(d) {
                d.view = currentView;
                d.supervisor_id = $('#filterSupervisor').val();
                d.status = $('#filterStatus').val();
            },
            error: function(xhr, error, thrown) {
                console.error('Technicians DataTable Error:', xhr.status, xhr.responseText);
            }
        },
        columns: [
            {
                data: null,
                name: 'checkbox',
                searchable: false,
                orderable: false,
                render: function(data) {
                    return '<input type="checkbox" class="form-check-input row-select" value="' + data.id + '">';
                }
            },
            { data: 'employee_id', name: 'employee_id' },
            { data: 'name', name: 'name' },
            { data: 'supervisor_name', name: 'supervisor_name', searchable: false, orderable: false },
            { data: 'coverage', name: 'coverage', searchable: false, orderable: false },
            { data: 'status_badge', name: 'status_badge', searchable: false, orderable: false },
            { data: 'actions', name: 'actions', searchable: false, orderable: false }
        ],
        order: [[2, 'asc']],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });

    // Select all checkbox
    $('#selectAll').on('change', function() {
        $('.row-select').prop('checked', $(this).is(':checked'));
        updateSelectedCount();
    });

    // Individual checkbox change
    $(document).on('change', '.row-select', function() {
        updateSelectedCount();
    });

    // Update selected count
    function updateSelectedCount() {
        var count = $('.row-select:checked').length;
        $('#selectedCountText').text(count > 0 ? count + ' technician(s) selected.' : 'Select technicians from the table first.');
        $('#assignSelectedBtn').prop('disabled', count === 0);
    }

    // Assign selected button click
    $('#assignSelectedBtn').on('click', function() {
        $('#bulkAssignModal').modal('show');
    });

    // Reassign button click
    $(document).on('click', '.reassign-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var supervisor = $(this).data('supervisor');

        $('#reassignTechnicianId').val(id);
        $('#reassignTechnicianName').text(name);
        $('#reassignSupervisorSelect').val(supervisor || '');
        $('#reassignModal').modal('show');
    });

    // Reassign form submit
    $('#reassignForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("admin.teams.assign") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                technician_id: $('#reassignTechnicianId').val(),
                supervisor_id: $('#reassignSupervisorSelect').val()
            },
            success: function(response) {
                $('#reassignModal').modal('hide');
                showToast('success', response.message);
                table.ajax.reload();
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'Failed to reassign');
            }
        });
    });

    // Bulk assign form submit
    $('#bulkAssignForm').on('submit', function(e) {
        e.preventDefault();

        var selectedIds = [];
        $('.row-select:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            showToast('warning', 'Please select technicians first');
            return;
        }

        $.ajax({
            url: '{{ route("admin.teams.bulk-assign") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                technician_ids: selectedIds,
                supervisor_id: $('#bulkSupervisorSelect').val()
            },
            success: function(response) {
                $('#bulkAssignModal').modal('hide');
                showToast('success', response.message);
                table.ajax.reload();
                $('#selectAll').prop('checked', false);
                updateSelectedCount();
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'Failed to bulk assign');
            }
        });
    });

    // Remove from team button click
    $(document).on('click', '.remove-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        if (confirm('Remove "' + name + '" from team? They will become independent.')) {
            $.ajax({
                url: '/admin/teams/' + id + '/remove',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    showToast('success', response.message);
                    table.ajax.reload();
                },
                error: function(xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Failed to remove');
                }
            });
        }
    });
    @endif

    // Filter handlers
    $('#filterSupervisor, #filterStatus').on('change', function() {
        table.ajax.reload();
    });

    // Toast notification
    function showToast(type, message) {
        var bgClass = type === 'success' ? 'bg-success' : (type === 'error' ? 'bg-danger' : 'bg-warning');
        var toast = $('<div class="toast align-items-center text-white ' + bgClass + ' border-0 position-fixed" style="top: 20px; right: 20px; z-index: 9999;" role="alert">' +
            '<div class="d-flex">' +
            '<div class="toast-body">' + message + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div></div>');

        $('body').append(toast);
        var bsToast = new bootstrap.Toast(toast[0]);
        bsToast.show();

        toast.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
});
</script>
@endpush
