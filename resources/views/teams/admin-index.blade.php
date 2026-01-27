@extends('layouts.app')

@section('title', 'Team Management - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Team Management</h1>
            <p class="text-muted mb-0">Manage supervisor teams and assign technicians</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                <i class="fas fa-users"></i> Bulk Assign
            </button>
        </div>
    </div>

    <!-- Overall Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                <i class="fas fa-user-tie fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Supervisors</h6>
                            <h3 class="mb-0">{{ $stats['total_supervisors'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                                <i class="fas fa-user-check fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Assigned Techs</h6>
                            <h3 class="mb-0">{{ $stats['assigned_technicians'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                                <i class="fas fa-user-clock fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Independent</h6>
                            <h3 class="mb-0">{{ $stats['unassigned_technicians'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 text-info rounded-3 p-3">
                                <i class="fas fa-briefcase fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Active Jobs</h6>
                            <h3 class="mb-0">{{ $stats['total_active_jobs'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Supervisor Teams -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0">
                <i class="fas fa-sitemap text-primary me-2"></i>Supervisor Teams
            </h5>
        </div>
        <div class="card-body">
            @if($supervisors->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-sitemap fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No Supervisors Available</h5>
                    <p class="text-muted">Create supervisor accounts to organize technicians into teams.</p>
                </div>
            @else
                <div class="accordion" id="supervisorAccordion">
                    @foreach($supervisors as $supervisor)
                        <div class="accordion-item border-0 mb-2 shadow-sm">
                            <h2 class="accordion-header">
                                <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#supervisor{{ $supervisor->id }}">
                                    <div class="d-flex align-items-center w-100">
                                        <div class="flex-grow-1">
                                            <strong>{{ $supervisor->name }}</strong>
                                            <span class="badge bg-primary ms-2">{{ $supervisor->technicians_count }} Technicians</span>
                                        </div>
                                        <div class="me-3">
                                            <small class="text-muted">{{ $supervisor->email }}</small>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="supervisor{{ $supervisor->id }}" 
                                 class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" 
                                 data-bs-parent="#supervisorAccordion">
                                <div class="accordion-body">
                                    @if($supervisor->technicians->isEmpty())
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No technicians assigned to this supervisor yet.
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Technician</th>
                                                        <th>Email</th>
                                                        <th class="text-center">Status</th>
                                                        <th class="text-center">Active Jobs</th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($supervisor->technicians as $tech)
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 12px;">
                                                                        <strong>{{ substr($tech->name, 0, 2) }}</strong>
                                                                    </div>
                                                                    {{ $tech->name }}
                                                                </div>
                                                            </td>
                                                            <td>{{ $tech->email }}</td>
                                                            <td class="text-center">
                                                                @if($tech->is_active)
                                                                    <span class="badge bg-success">Active</span>
                                                                @else
                                                                    <span class="badge bg-secondary">Inactive</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge bg-warning text-dark">{{ $tech->active_jobs ?? 0 }}</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-outline-primary" 
                                                                        onclick="reassignTechnician({{ $tech->id }}, '{{ $tech->name }}')"
                                                                        title="Reassign">
                                                                    <i class="fas fa-exchange-alt"></i>
                                                                </button>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-outline-danger" 
                                                                        onclick="removeFromTeam({{ $tech->id }}, '{{ $tech->name }}')"
                                                                        title="Make Independent">
                                                                    <i class="fas fa-user-slash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Independent Technicians -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-user-circle text-warning me-2"></i>Independent Technicians
                </h5>
                @if($unassignedTechnicians->isNotEmpty())
                    <button type="button" 
                            class="btn btn-sm btn-primary" 
                            onclick="bulkAssignUnassigned()">
                        <i class="fas fa-users"></i> Assign All
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if($unassignedTechnicians->isEmpty())
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    All technicians are assigned to supervisors!
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAllUnassigned" class="form-check-input">
                                </th>
                                <th>Technician</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Active Jobs</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unassignedTechnicians as $tech)
                                <tr>
                                    <td>
                                        <input type="checkbox" 
                                               class="form-check-input unassigned-checkbox" 
                                               value="{{ $tech->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                                <strong>{{ substr($tech->name, 0, 2) }}</strong>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $tech->name }}</h6>
                                                <small class="text-muted">{{ $tech->employee_id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $tech->email }}</td>
                                    <td>{{ $tech->phone ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($tech->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">{{ $tech->active_jobs ?? 0 }}</span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-primary" 
                                                onclick="assignTechnician({{ $tech->id }}, '{{ $tech->name }}')"
                                                title="Assign to Supervisor">
                                            <i class="fas fa-user-plus"></i> Assign
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Assignment Modal -->
@include('teams.partials._assignment_modal')

<!-- Bulk Assignment Modal -->
@include('teams.partials._bulk_assignment_modal')

@push('styles')
<style>
    .accordion-button:not(.collapsed) {
        background-color: #f8f9fa;
        color: #0d6efd;
    }
    
    .accordion-button::after {
        margin-left: auto;
    }
</style>
@endpush

@push('scripts')
<script>
let allTechnicians = @json($allTechnicians);
let supervisors = @json($supervisors);

// Select all unassigned checkboxes
$('#selectAllUnassigned').on('change', function() {
    $('.unassigned-checkbox').prop('checked', $(this).prop('checked'));
});

// Assign single technician
function assignTechnician(technicianId, technicianName) {
    $('#assignmentModalLabel').text('Assign ' + technicianName);
    $('#technician_id').val(technicianId);
    $('#current_assignment').html('<span class="badge bg-warning">Independent</span>');
    $('#supervisor_id').val('').trigger('change');
    $('#assignmentModal').modal('show');
}

// Reassign technician
function reassignTechnician(technicianId, technicianName) {
    const technician = allTechnicians.find(t => t.id === technicianId);
    const currentSupervisor = technician.supervisor ? technician.supervisor.name : 'Independent';
    
    $('#assignmentModalLabel').text('Reassign ' + technicianName);
    $('#technician_id').val(technicianId);
    $('#current_assignment').html('<span class="badge bg-info">' + currentSupervisor + '</span>');
    $('#supervisor_id').val('').trigger('change');
    $('#assignmentModal').modal('show');
}

// Remove from team
function removeFromTeam(technicianId, technicianName) {
    if (!confirm('Make ' + technicianName + ' an independent technician?')) {
        return;
    }

    $.ajax({
        url: '/teams/' + technicianId + '/remove',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            showAlert('success', response.message);
            setTimeout(() => location.reload(), 1500);
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseJSON?.message || 'Failed to remove from team');
        }
    });
}

// Bulk assign unassigned
function bulkAssignUnassigned() {
    const selectedIds = $('.unassigned-checkbox:checked').map(function() {
        return $(this).val();
    }).get();

    if (selectedIds.length === 0) {
        alert('Please select at least one technician');
        return;
    }

    $('#bulk_technician_ids').val(JSON.stringify(selectedIds));
    $('#selected_count').text(selectedIds.length);
    $('#bulk_supervisor_id').val('').trigger('change');
    $('#bulkAssignModal').modal('show');
}

// Show alert
function showAlert(type, message) {
    const alert = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('.container-fluid').prepend(alert);
}
</script>
@endpush
@endsection
