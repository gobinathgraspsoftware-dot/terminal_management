@extends('layouts.app')

@section('title', 'Team Management - TMS')

@section('content')
<div class="container-fluid">
    @php
        $currentView = request()->get('view', 'all');
    @endphp

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                @if($currentView === 'supervisors')
                    <i class="bi bi-person-badge me-2"></i>Supervisors
                @elseif($currentView === 'technicians')
                    <i class="bi bi-person-gear me-2"></i>Technicians
                @else
                    <i class="bi bi-diagram-3 me-2"></i>All Teams
                @endif
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">
                        {{ $currentView === 'supervisors' ? 'Supervisors' : ($currentView === 'technicians' ? 'Technicians' : 'Teams') }}
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Unassigned Technicians Alert --}}
    @if($unassignedTechnicians->count() > 0)
        <div class="alert alert-danger d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
            <div class="flex-grow-1">
                <strong>{{ $unassignedTechnicians->count() }} Unassigned Technician(s)</strong> — All technicians must have a supervisor.
                <span class="d-block small text-muted mt-1">
                    {{ $unassignedTechnicians->pluck('name')->implode(', ') }}
                </span>
            </div>
            <button type="button" class="btn btn-danger btn-sm ms-3" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                <i class="bi bi-person-plus me-1"></i> Assign Now
            </button>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-person-badge text-primary fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $statistics['total_supervisors'] }}</h4>
                        <small class="text-muted">Supervisors</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-person-gear text-success fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $statistics['total_technicians'] }}</h4>
                        <small class="text-muted">Technicians</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 bg-info bg-opacity-10 p-3 me-3">
                        <i class="bi bi-people text-info fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $statistics['avg_team_size'] }}</h4>
                        <small class="text-muted">Avg Team Size</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-3 me-3">
                        <i class="bi bi-trophy text-warning fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $statistics['largest_team']['team_size'] }}</h4>
                        <small class="text-muted">Largest ({{ $statistics['largest_team']['supervisor_name'] }})</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable Card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">
                    @if($currentView === 'supervisors')
                        <i class="bi bi-person-badge me-2 text-primary"></i>Supervisor List
                    @elseif($currentView === 'technicians')
                        <i class="bi bi-person-gear me-2 text-success"></i>Technician List
                    @else
                        <i class="bi bi-people me-2 text-info"></i>All Team Members
                    @endif
                </h5>
                <div class="d-flex gap-2 align-items-center">
                    <select id="filterStatus" class="form-select form-select-sm" style="width: 130px;">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    @if(in_array($currentView, ['all', 'technicians']))
                        <select id="filterSupervisor" class="form-select form-select-sm" style="width: 200px;">
                            <option value="">All Supervisors</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->technicians_count }})</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="teamTable" class="table table-hover align-middle mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px;"></th>
                            <th>Name</th>
                            <th>Employee ID</th>
                            <th>Email</th>
                            @if($currentView === 'supervisors')
                                <th class="text-center">Team Size</th>
                            @elseif($currentView === 'technicians')
                                <th>Supervisor</th>
                            @else
                                <th>Role</th>
                                <th>Supervisor / Team</th>
                            @endif
                            <th>Coverage</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width:100px;">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
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
                @csrf
                <input type="hidden" name="technician_id" id="reassignTechnicianId">
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Reassigning: <strong id="reassignTechnicianName"></strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assign to Supervisor <span class="text-danger">*</span></label>
                        <select name="supervisor_id" id="reassignSupervisorSelect" class="form-select" required>
                            <option value="">-- Select Supervisor --</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->technicians_count }} members)</option>
                            @endforeach
                        </select>
                        <div class="form-text">All technicians must have a supervisor assigned.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Reassign</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bulk Assign Modal --}}
@if($unassignedTechnicians->count() > 0)
<div class="modal fade" id="bulkAssignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Assign Unassigned Technicians</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkAssignForm">
                @csrf
                <div class="modal-body">
                    <p class="text-muted mb-3">Select technicians and assign them to a supervisor.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Technicians</label>
                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                            @foreach($unassignedTechnicians as $tech)
                                <div class="form-check mb-2">
                                    <input class="form-check-input bulk-tech-check" type="checkbox"
                                           name="technician_ids[]" value="{{ $tech->id }}" id="bulkTech{{ $tech->id }}" checked>
                                    <label class="form-check-label" for="bulkTech{{ $tech->id }}">
                                        {{ $tech->name }} <span class="text-muted">({{ $tech->employee_id }})</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="selectAllBulk" checked>
                            <label class="form-check-label fw-bold" for="selectAllBulk">Select All</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assign to Supervisor <span class="text-danger">*</span></label>
                        <select name="supervisor_id" id="bulkSupervisorSelect" class="form-select" required>
                            <option value="">-- Select Supervisor --</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->technicians_count }} members)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-check-lg me-1"></i> Assign Selected</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var currentView = '{{ $currentView }}';

    // Build columns based on current view
    var columns = [
        {
            data: 'avatar',
            name: 'avatar',
            orderable: false,
            searchable: false,
            className: 'text-center pe-0'
        },
        { data: 'name', name: 'name' },
        { data: 'employee_id', name: 'employee_id' },
        { data: 'email', name: 'email' },
    ];

    if (currentView === 'supervisors') {
        columns.push({ data: 'team_size', name: 'team_size', orderable: false, searchable: false, className: 'text-center' });
    } else if (currentView === 'technicians') {
        columns.push({ data: 'supervisor_name', name: 'supervisor_name', orderable: false, searchable: false });
    } else {
        columns.push({ data: 'role', name: 'role', orderable: false, searchable: false });
        columns.push({ data: 'supervisor_info', name: 'supervisor_info', orderable: false, searchable: false });
    }

    columns.push(
        { data: 'coverage', name: 'coverage', orderable: false, searchable: false },
        { data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
    );

    var table = $('#teamTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.teams.datatable") }}',
            data: function(d) {
                d.view = currentView;
                d.status = $('#filterStatus').val();
                if ($('#filterSupervisor').length) {
                    d.supervisor_id = $('#filterSupervisor').val();
                }
            }
        },
        columns: columns,
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        dom: '<"row align-items-center mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            emptyTable: 'No records found.',
            processing: '<div class="d-flex align-items-center justify-content-center py-3"><span class="spinner-border spinner-border-sm me-2"></span> Loading...</div>'
        },
        drawCallback: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Filter handlers
    $('#filterStatus, #filterSupervisor').on('change', function() {
        table.ajax.reload();
    });

    // Reassign technician
    $(document).on('click', '.reassign-technician', function(e) {
        e.preventDefault();
        $('#reassignTechnicianId').val($(this).data('id'));
        $('#reassignTechnicianName').text($(this).data('name'));
        $('#reassignSupervisorSelect').val($(this).data('supervisor') || '');
        $('#reassignModal').modal('show');
    });

    // Submit reassign
    $('#reassignForm').on('submit', function(e) {
        e.preventDefault();
        if (!$('#reassignSupervisorSelect').val()) {
            showToast('error', 'Please select a supervisor.');
            return;
        }
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url: '{{ route("admin.teams.assign") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#reassignModal').modal('hide');
                    showToast('success', response.message);
                    table.ajax.reload(null, false);
                    // Reload page after short delay to update stats
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showToast('error', response.message);
                }
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'An error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Reassign');
            }
        });
    });

    // Bulk assign
    $('#bulkAssignForm').on('submit', function(e) {
        e.preventDefault();
        if (!$('#bulkSupervisorSelect').val()) {
            showToast('error', 'Please select a supervisor.');
            return;
        }
        var techIds = [];
        $('.bulk-tech-check:checked').each(function() { techIds.push($(this).val()); });
        if (techIds.length === 0) {
            showToast('error', 'Please select at least one technician.');
            return;
        }
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Assigning...');

        $.ajax({
            url: '{{ route("admin.teams.bulk-assign") }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', technician_ids: techIds, supervisor_id: $('#bulkSupervisorSelect').val() },
            success: function(response) {
                if (response.success) {
                    $('#bulkAssignModal').modal('hide');
                    showToast('success', response.message);
                    setTimeout(function() { location.reload(); }, 1500);
                }
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'An error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Assign Selected');
            }
        });
    });

    // Select all toggle
    $('#selectAllBulk').on('change', function() {
        $('.bulk-tech-check').prop('checked', $(this).is(':checked'));
    });
});
</script>
@endpush
