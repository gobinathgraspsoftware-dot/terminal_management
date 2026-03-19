@extends('layouts.app')

@section('title', 'Team Management')

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-diagram-3 me-2"></i>Team Management</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Teams</li>
                </ol>
            </nav>
        </div>
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
                            <h4 class="mb-0">{{ $statistics['total_supervisors'] ?? 0 }}</h4>
                            <div class="small">
                                <span class="badge bg-info">{{ $statistics['internal_supervisors'] ?? 0 }} Internal</span>
                                <span class="badge bg-warning text-dark">{{ $statistics['external_supervisors'] ?? 0 }} External</span>
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
                            <i class="bi bi-person-gear fs-4 text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Technicians</div>
                            <h4 class="mb-0">{{ $statistics['total_technicians'] ?? 0 }}</h4>
                            <div class="small text-success">{{ $statistics['assigned_technicians'] ?? 0 }} assigned</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 {{ ($statistics['unassigned_technicians'] ?? 0) > 0 ? 'bg-danger' : 'bg-secondary' }} bg-opacity-10 me-3">
                            <i class="bi bi-exclamation-triangle fs-4 {{ ($statistics['unassigned_technicians'] ?? 0) > 0 ? 'text-danger' : 'text-secondary' }}"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Unassigned Technicians</div>
                            <h4 class="mb-0 {{ ($statistics['unassigned_technicians'] ?? 0) > 0 ? 'text-danger' : '' }}">{{ $statistics['unassigned_technicians'] ?? 0 }}</h4>
                            <div class="small text-muted">Needs attention</div>
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
                            <h4 class="mb-0">{{ $statistics['avg_team_size'] ?? 0 }}</h4>
                            <div class="small text-muted">Largest: {{ $statistics['largest_team']['supervisor_name'] ?? '-' }} ({{ $statistics['largest_team']['team_size'] ?? 0 }})</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- View Tabs --}}
    <ul class="nav nav-tabs mb-3" id="teamViewTabs">
        <li class="nav-item">
            <a class="nav-link {{ $view === 'all' ? 'active' : '' }}" href="{{ route('admin.teams.index') }}?view=all">
                <i class="bi bi-diagram-3 me-1"></i> All Teams
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $view === 'supervisors' ? 'active' : '' }}" href="{{ route('admin.teams.index') }}?view=supervisors">
                <i class="bi bi-person-badge me-1"></i> Supervisors
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $view === 'technicians' ? 'active' : '' }}" href="{{ route('admin.teams.index') }}?view=technicians">
                <i class="bi bi-person-gear me-1"></i> Technicians
            </a>
        </li>
    </ul>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                @if($view === 'supervisors' || $view === 'all')
                <div class="col-md-3">
                    <label class="form-label small mb-1">Supervisor Type</label>
                    <select id="filterSupervisorType" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="internal">Internal (Has Team)</option>
                        <option value="external">External (No Team)</option>
                    </select>
                </div>
                @endif
                @if($view === 'technicians' || $view === 'all')
                <div class="col-md-3">
                    <label class="form-label small mb-1">Supervisor</label>
                    <select id="filterSupervisor" class="form-select form-select-sm">
                        <option value="">All Supervisors</option>
                        @foreach($supervisors->where('supervisor_type', 'internal') as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->technicians_count }})</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3 d-flex align-items-end">
                    <button id="btnClearFilters" class="btn btn-sm btn-outline-secondary">
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
                        @if($view === 'supervisors')
                        <tr>
                            <th width="50"></th>
                            <th>Name</th>
                            <th>Employee ID</th>
                            <th>Type</th>
                            <th>Team Size</th>
                            <th>Coverage</th>
                            <th>Status</th>
                            <th width="80" class="text-center">Actions</th>
                        </tr>
                        @elseif($view === 'technicians')
                        <tr>
                            <th width="50"></th>
                            <th>Name</th>
                            <th>Employee ID</th>
                            <th>Supervisor</th>
                            <th>Coverage</th>
                            <th>Status</th>
                            <th width="100" class="text-center">Actions</th>
                        </tr>
                        @else
                        <tr>
                            <th width="50"></th>
                            <th>Name</th>
                            <th>Employee ID</th>
                            <th>Role</th>
                            <th>Supervisor / Team</th>
                            <th>Coverage</th>
                            <th>Status</th>
                            <th width="100" class="text-center">Actions</th>
                        </tr>
                        @endif
                    </thead>
                </table>
            </div>
        </div>
    </div>

    {{-- Assign Technician Modal --}}
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Reassign Technician</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="assignTechnicianId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Technician</label>
                        <input type="text" id="assignTechnicianName" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assign to Supervisor <span class="text-danger">*</span></label>
                        <select id="assignSupervisorId" class="form-select">
                            <option value="">Select Internal Supervisor...</option>
                            @foreach($supervisors->where('supervisor_type', 'internal') as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->technicians_count }} members)</option>
                            @endforeach
                        </select>
                        <div class="form-text text-info">
                            <i class="bi bi-info-circle me-1"></i>Only internal supervisors can have technician teams.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="btnConfirmAssign" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Assign
                    </button>
                </div>
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
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Unassigned Technicians</label>
                        <select id="bulkTechnicianIds" class="form-select" multiple size="6">
                            @foreach($unassignedTechnicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }} ({{ $tech->employee_id }})</option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assign to Supervisor <span class="text-danger">*</span></label>
                        <select id="bulkSupervisorId" class="form-select">
                            <option value="">Select Internal Supervisor...</option>
                            @foreach($supervisors->where('supervisor_type', 'internal') as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->technicians_count }} members)</option>
                            @endforeach
                        </select>
                        <div class="form-text text-info">
                            <i class="bi bi-info-circle me-1"></i>Only internal supervisors are listed.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="btnConfirmBulkAssign" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Assign Selected
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(function() {
    const currentView = '{{ $view }}';

    // Build DataTable columns based on view
    let columns;
    if (currentView === 'supervisors') {
        columns = [
            { data: 'avatar', name: 'avatar', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'employee_id', name: 'employee_id' },
            { data: 'supervisor_type_badge', name: 'supervisor_type', orderable: false },
            { data: 'team_size', name: 'team_size', orderable: false, searchable: false },
            { data: 'coverage', name: 'coverage', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' },
        ];
    } else if (currentView === 'technicians') {
        columns = [
            { data: 'avatar', name: 'avatar', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'employee_id', name: 'employee_id' },
            { data: 'supervisor_name', name: 'supervisor_name', orderable: false },
            { data: 'coverage', name: 'coverage', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' },
        ];
    } else {
        columns = [
            { data: 'avatar', name: 'avatar', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'employee_id', name: 'employee_id' },
            { data: 'role', name: 'role', orderable: false },
            { data: 'supervisor_info', name: 'supervisor_info', orderable: false },
            { data: 'coverage', name: 'coverage', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' },
        ];
    }

    const table = $('#teamsTable').DataTable({
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
        language: { emptyTable: 'No team members found' },
        drawCallback: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Filters
    $('#filterStatus, #filterSupervisorType, #filterSupervisor').on('change', function() {
        table.ajax.reload();
    });

    $('#btnClearFilters').on('click', function() {
        $('#filterStatus, #filterSupervisorType, #filterSupervisor').val('');
        table.ajax.reload();
    });

    // Single assign
    $(document).on('click', '.reassign-technician', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const supervisorId = $(this).data('supervisor');
        $('#assignTechnicianId').val(id);
        $('#assignTechnicianName').val(name);
        $('#assignSupervisorId').val(supervisorId || '');
        new bootstrap.Modal('#assignModal').show();
    });

    $('#btnConfirmAssign').on('click', function() {
        const techId = $('#assignTechnicianId').val();
        const supId = $('#assignSupervisorId').val();
        if (!supId) {
            Swal.fire('Error', 'Please select an internal supervisor.', 'warning');
            return;
        }
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Assigning...');

        $.ajax({
            url: '{{ route("admin.teams.assign") }}',
            method: 'POST',
            data: { technician_id: techId, supervisor_id: supId, _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    Swal.fire('Success', res.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Assignment failed';
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Assign');
            }
        });
    });

    // Bulk assign
    $('#btnConfirmBulkAssign').on('click', function() {
        const techIds = $('#bulkTechnicianIds').val();
        const supId = $('#bulkSupervisorId').val();
        if (!techIds || techIds.length === 0) {
            Swal.fire('Error', 'Please select at least one technician.', 'warning');
            return;
        }
        if (!supId) {
            Swal.fire('Error', 'Please select an internal supervisor.', 'warning');
            return;
        }
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Assigning...');

        $.ajax({
            url: '{{ route("admin.teams.bulk-assign") }}',
            method: 'POST',
            data: { technician_ids: techIds, supervisor_id: supId, _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    Swal.fire('Success', res.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('bulkAssignModal')).hide();
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Bulk assignment failed';
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Assign Selected');
            }
        });
    });
});
</script>
@endpush
