@extends('layouts.app')

@section('title', 'Team Management - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
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
                <div class="card-body text-center">
                    <div class="text-primary mb-2"><i class="bi bi-person-badge fs-3"></i></div>
                    <h3 class="mb-1">{{ $statistics['total_supervisors'] }}</h3>
                    <small class="text-muted">Supervisors</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-success mb-2"><i class="bi bi-person-gear fs-3"></i></div>
                    <h3 class="mb-1">{{ $statistics['total_technicians'] }}</h3>
                    <small class="text-muted">Technicians</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-info mb-2"><i class="bi bi-people fs-3"></i></div>
                    <h3 class="mb-1">{{ $statistics['avg_team_size'] }}</h3>
                    <small class="text-muted">Avg Team Size</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-warning mb-2"><i class="bi bi-trophy fs-3"></i></div>
                    <h3 class="mb-1">{{ $statistics['largest_team']['team_size'] }}</h3>
                    <small class="text-muted">Largest Team ({{ $statistics['largest_team']['supervisor_name'] }})</small>
                </div>
            </div>
        </div>
    </div>

    {{-- View Tabs --}}
    @php
        $currentView = request()->get('view', 'all');
    @endphp
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ $currentView === 'all' ? 'active' : '' }}" href="{{ route('admin.teams.index') }}">
                <i class="bi bi-diagram-3 me-1"></i> All Teams
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $currentView === 'supervisors' ? 'active' : '' }}" href="{{ route('admin.teams.index') }}?view=supervisors">
                <i class="bi bi-person-badge me-1"></i> Supervisors
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $currentView === 'technicians' ? 'active' : '' }}" href="{{ route('admin.teams.index') }}?view=technicians">
                <i class="bi bi-person-gear me-1"></i> Technicians
            </a>
        </li>
        @if($unassignedTechnicians->count() > 0)
        <li class="nav-item">
            <a class="nav-link {{ $currentView === 'unassigned' ? 'active' : '' }} text-danger" href="{{ route('admin.teams.index') }}?view=unassigned">
                <i class="bi bi-exclamation-triangle me-1"></i> Unassigned
                <span class="badge bg-danger ms-1">{{ $unassignedTechnicians->count() }}</span>
            </a>
        </li>
        @endif
    </ul>

    {{-- Card View (for All Teams) --}}
    @if($currentView === 'all')
        <div class="row g-3 mb-4">
            @forelse($supervisors as $supervisor)
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex align-items-center py-3">
                            <img src="{{ $supervisor->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($supervisor->name) }}"
                                 class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <h6 class="mb-0">{{ $supervisor->name }}</h6>
                                <small class="text-muted">{{ $supervisor->employee_id }}</small>
                            </div>
                            <span class="badge bg-primary">{{ $supervisor->technicians_count }} members</span>
                        </div>
                        <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                            @if($supervisor->technicians->count() > 0)
                                <ul class="list-group list-group-flush">
                                    @foreach($supervisor->technicians as $member)
                                        <li class="list-group-item d-flex align-items-center py-2">
                                            <img src="{{ $member->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($member->name) }}"
                                                 class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <p class="mb-0 text-truncate">{{ $member->name }}</p>
                                                <small class="text-muted">{{ $member->employee_id }}</small>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-link btn-sm text-muted p-0" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('admin.teams.show', $member->id) }}">
                                                            <i class="bi bi-eye me-2"></i> View
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item reassign-technician" href="#"
                                                           data-id="{{ $member->id }}" data-name="{{ $member->name }}"
                                                           data-supervisor="{{ $supervisor->id }}">
                                                            <i class="bi bi-arrow-left-right me-2"></i> Reassign
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-people fs-3 opacity-50"></i>
                                    <p class="mb-0 mt-1">No members yet</p>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-white text-center">
                            <a href="{{ route('admin.teams.show', $supervisor->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        No supervisors found. Create supervisors first to manage teams.
                    </div>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Table View (for Supervisors / Technicians / Unassigned tabs) --}}
    @if(in_array($currentView, ['supervisors', 'technicians', 'unassigned']))
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    @if($currentView === 'supervisors')
                        <i class="bi bi-person-badge me-2"></i>Supervisors
                    @elseif($currentView === 'unassigned')
                        <i class="bi bi-exclamation-triangle me-2 text-danger"></i>Unassigned Technicians
                    @else
                        <i class="bi bi-person-gear me-2"></i>Technicians
                    @endif
                </h5>
                <div class="d-flex gap-2">
                    <select id="filterStatus" class="form-select form-select-sm" style="width: 140px;">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    @if($currentView === 'technicians')
                        <select id="filterSupervisor" class="form-select form-select-sm" style="width: 200px;">
                            <option value="">All Supervisors</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="teamTable" class="table table-hover table-striped align-middle" style="width:100%">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                @if($currentView === 'supervisors')
                                    <th>Team Size</th>
                                @else
                                    <th>Supervisor</th>
                                @endif
                                <th>Coverage</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    @endif
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
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Reassign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bulk Assign Modal (for unassigned technicians) --}}
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
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-check-lg me-1"></i> Assign Selected
                    </button>
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

    // Initialize DataTable for table views
    if (['supervisors', 'technicians', 'unassigned'].includes(currentView)) {
        var columns = [
            { data: 'employee_id', name: 'employee_id' },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
        ];

        if (currentView === 'supervisors') {
            columns.push({ data: 'team_size', name: 'team_size', orderable: false, searchable: false });
        } else {
            columns.push({ data: 'supervisor_name', name: 'supervisor_name', orderable: false, searchable: false });
        }

        columns.push(
            { data: 'coverage', name: 'coverage', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        );

        var table = $('#teamTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("admin.teams.datatable") }}',
                data: function(d) {
                    d.view = currentView;
                    d.status = $('#filterStatus').val();
                    if (currentView === 'technicians') {
                        d.supervisor_id = $('#filterSupervisor').val();
                    }
                }
            },
            columns: columns,
            order: [[1, 'asc']],
            responsive: true,
            language: {
                emptyTable: currentView === 'unassigned'
                    ? 'All technicians are assigned to supervisors.'
                    : 'No records found.'
            }
        });

        // Filter handlers
        $('#filterStatus, #filterSupervisor').on('change', function() {
            table.ajax.reload();
        });
    }

    // Reassign technician
    $(document).on('click', '.reassign-technician', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var name = $(this).data('name');
        var supervisor = $(this).data('supervisor');

        $('#reassignTechnicianId').val(id);
        $('#reassignTechnicianName').text(name);
        $('#reassignSupervisorSelect').val(supervisor || '');
        $('#reassignModal').modal('show');
    });

    // Submit reassign
    $('#reassignForm').on('submit', function(e) {
        e.preventDefault();

        var supervisorId = $('#reassignSupervisorSelect').val();
        if (!supervisorId) {
            showToast('error', 'Please select a supervisor. All technicians must have a supervisor.');
            return;
        }

        $.ajax({
            url: '{{ route("admin.teams.assign") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#reassignModal').modal('hide');
                    showToast('success', response.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast('error', response.message);
                }
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'An error occurred');
            }
        });
    });

    // Bulk assign
    $('#bulkAssignForm').on('submit', function(e) {
        e.preventDefault();

        var supervisorId = $('#bulkSupervisorSelect').val();
        if (!supervisorId) {
            showToast('error', 'Please select a supervisor.');
            return;
        }

        var techIds = [];
        $('.bulk-tech-check:checked').each(function() {
            techIds.push($(this).val());
        });

        if (techIds.length === 0) {
            showToast('error', 'Please select at least one technician.');
            return;
        }

        $.ajax({
            url: '{{ route("admin.teams.bulk-assign") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                technician_ids: techIds,
                supervisor_id: supervisorId
            },
            success: function(response) {
                if (response.success) {
                    $('#bulkAssignModal').modal('hide');
                    showToast('success', response.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast('error', response.message);
                }
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'An error occurred');
            }
        });
    });

    // Select all toggle for bulk assign
    $('#selectAllBulk').on('change', function() {
        $('.bulk-tech-check').prop('checked', $(this).is(':checked'));
    });
});
</script>
@endpush
