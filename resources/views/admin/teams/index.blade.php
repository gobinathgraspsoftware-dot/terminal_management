@extends('layouts.app')

@section('title', 'Team Management - TMS')

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-diagram-3 me-2"></i>Team Management
                @if($view === 'supervisors')
                    <span class="badge bg-primary fs-6 ms-2">Supervisors</span>
                @elseif($view === 'technicians')
                    <span class="badge bg-success fs-6 ms-2">Technicians</span>
                @endif
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Teams</li>
                </ol>
            </nav>
        </div>
        @if(count($unassignedTechnicians) > 0)
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#assignIndependentModal">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ count($unassignedTechnicians) }} Unassigned
        </button>
        @endif
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-primary bg-opacity-10 me-3">
                            <i class="bi bi-person-badge fs-4 text-primary"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Supervisors</div>
                            <h3 class="mb-0">{{ $statistics['total_supervisors'] }}</h3>
                            <div class="d-flex gap-1 mt-1">
                                <span class="badge bg-info">{{ $statistics['internal_supervisors'] }} Internal</span>
                                <span class="badge bg-warning text-dark">{{ $statistics['external_supervisors'] }} External</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-success bg-opacity-10 me-3">
                            <i class="bi bi-people fs-4 text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Technicians</div>
                            <h3 class="mb-0">{{ $statistics['total_technicians'] }}</h3>
                            <div class="small text-success">{{ $statistics['assigned_technicians'] }} assigned</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 {{ $statistics['unassigned_technicians'] > 0 ? 'bg-danger' : 'bg-secondary' }} bg-opacity-10 me-3">
                            <i class="bi bi-exclamation-triangle fs-4 {{ $statistics['unassigned_technicians'] > 0 ? 'text-danger' : 'text-secondary' }}"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Unassigned Technicians</div>
                            <h3 class="mb-0">{{ $statistics['unassigned_technicians'] }}</h3>
                            <div class="small {{ $statistics['unassigned_technicians'] > 0 ? 'text-danger' : 'text-success' }}">{{ $statistics['unassigned_technicians'] > 0 ? 'Needs attention' : 'All assigned' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-info bg-opacity-10 me-3">
                            <i class="bi bi-bar-chart fs-4 text-info"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Avg Team Size</div>
                            <h3 class="mb-0">{{ $statistics['avg_team_size'] }}</h3>
                            <div class="small text-muted">Largest: {{ $statistics['largest_team']['supervisor_name'] }} ({{ $statistics['largest_team']['team_size'] }})</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                @if($view === 'all' || $view === 'supervisors')
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Supervisor Type</label>
                    <select id="filterSupervisorType" class="form-select">
                        <option value="">All Types</option>
                        <option value="internal">Internal</option>
                        <option value="external">External</option>
                    </select>
                </div>
                @endif
                @if($view === 'all' || $view === 'technicians')
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Supervisor</label>
                    <select id="filterSupervisor" class="form-select">
                        <option value="">All Supervisors</option>
                        @foreach($supervisors as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->technicians_count }})</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" id="btnClearFilters" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="teamsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="50"></th>
                            <th>Name</th>
                            <th>Employee ID</th>
                            {{-- Dynamic columns per view --}}
                            @if($view === 'all')
                                <th>Role</th>
                                <th>Supervisor / Team</th>
                            @elseif($view === 'supervisors')
                                <th>Type</th>
                                <th>Team Size</th>
                            @elseif($view === 'technicians')
                                <th>Supervisor</th>
                            @endif
                            <th>State</th>
                            <th>Status</th>
                            <th width="100" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Include Modals --}}
@include('admin.teams._modals', ['independentTechnicians' => $unassignedTechnicians])
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var currentView = '{{ $view }}';

    // Build columns based on current view
    var columns = [
        { data: 'avatar', name: 'avatar', orderable: false, searchable: false },
        { data: 'name', name: 'name' },
        { data: 'employee_id', name: 'employee_id' },
    ];

    if (currentView === 'all') {
        columns.push({ data: 'role', name: 'role', orderable: false, searchable: false });
        columns.push({ data: 'supervisor_info', name: 'supervisor_info', orderable: false, searchable: false });
    } else if (currentView === 'supervisors') {
        columns.push({ data: 'supervisor_type_badge', name: 'supervisor_type_badge', orderable: false, searchable: false });
        columns.push({ data: 'team_size', name: 'team_size', orderable: false, searchable: false });
    } else if (currentView === 'technicians') {
        columns.push({ data: 'supervisor_name', name: 'supervisor_name', orderable: false, searchable: false });
    }

    columns.push({ data: 'state_name', name: 'state_name', orderable: false, searchable: false });
    columns.push({ data: 'status_badge', name: 'status', orderable: false, searchable: false });
    columns.push({ data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' });

    // Initialize DataTable
    var table = $('#teamsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.teams.datatable") }}',
            data: function(d) {
                d.view = currentView;
                d.status = $('#filterStatus').val();
                d.supervisor_type = $('#filterSupervisorType').val() || '';
                d.supervisor_id = $('#filterSupervisor').val() || '';
            }
        },
        columns: columns,
        order: [[1, 'asc']],
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...',
            emptyTable: 'No team members found'
        },
        drawCallback: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Filter handlers
    $('#filterStatus, #filterSupervisorType, #filterSupervisor').on('change', function() {
        table.ajax.reload();
    });

    $('#btnClearFilters').on('click', function() {
        $('#filterStatus, #filterSupervisorType, #filterSupervisor').val('');
        table.ajax.reload();
    });

    // Reassign Technician
    $(document).on('click', '.reassign-technician', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var supervisorId = $(this).data('supervisor');
        $('#reassignTechnicianId').val(id);
        $('#reassignTechnicianName').text(name);
        $('#reassignSupervisorSelect').val(supervisorId);
        $('#reassignModal').modal('show');
    });

    $('#reassignForm').on('submit', function(e) {
        e.preventDefault();
        var techId = $('#reassignTechnicianId').val();
        var supId = $('#reassignSupervisorSelect').val();

        if (!supId) {
            showToast('Please select a supervisor', 'warning');
            return;
        }

        $.ajax({
            url: '{{ route("admin.teams.assign") }}',
            method: 'POST',
            data: { technician_id: techId, supervisor_id: supId },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                $('#reassignModal').modal('hide');
                if (response.success) {
                    showToast(response.message);
                    table.ajax.reload();
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to reassign', 'error');
            }
        });
    });

    // Bulk Assign
    $('#bulkAssignForm').on('submit', function(e) {
        e.preventDefault();
        var supId = $('#bulkSupervisorSelect').val();
        if (!supId) {
            showToast('Please select a supervisor', 'warning');
            return;
        }
        // Bulk assign logic handled via _modals partial
    });
});
</script>
@endpush
