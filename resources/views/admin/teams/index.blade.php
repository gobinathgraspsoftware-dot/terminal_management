@extends('layouts.admin')

@section('title', 'Team Management')

@section('page-header')
<div>
    <h4 class="mb-1">Team Management</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Team Management</li>
        </ol>
    </nav>
</div>
@endsection

@section('content')
{{-- Statistics Cards --}}
<div class="row mb-4">
    <div class="col-md-2 col-6 mb-3">
        <div class="card h-100 border-start border-primary border-4">
            <div class="card-body text-center py-3">
                <h3 class="text-primary mb-0">{{ $statistics['total_supervisors'] }}</h3>
                <small class="text-muted">Supervisors</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card h-100 border-start border-success border-4">
            <div class="card-body text-center py-3">
                <h3 class="text-success mb-0">{{ $statistics['total_technicians'] }}</h3>
                <small class="text-muted">Technicians</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card h-100 border-start border-info border-4">
            <div class="card-body text-center py-3">
                <h3 class="text-info mb-0">{{ $statistics['assigned_technicians'] }}</h3>
                <small class="text-muted">Assigned</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card h-100 border-start border-warning border-4">
            <div class="card-body text-center py-3">
                <h3 class="text-warning mb-0">{{ $statistics['independent_technicians'] }}</h3>
                <small class="text-muted">Independent</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card h-100 border-start border-secondary border-4">
            <div class="card-body text-center py-3">
                <h3 class="mb-0">{{ $statistics['avg_team_size'] }}</h3>
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

{{-- View Toggle --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-primary active" id="cardViewBtn"><i class="fas fa-th-large me-1"></i> Card View</button>
        <button type="button" class="btn btn-outline-primary" id="tableViewBtn"><i class="fas fa-list me-1"></i> Table View</button>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
        <i class="fas fa-users-cog me-1"></i> Bulk Assign
    </button>
</div>

{{-- Card View --}}
<div id="cardView">
    <div class="row">
        @foreach($supervisors as $supervisor)
            @include('admin.teams._partials.team-card', ['supervisor' => $supervisor])
        @endforeach
    </div>
    
    {{-- Independent Technicians Section --}}
    @if($independentTechnicians->count() > 0)
    <div class="card mt-4">
        <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-user-slash me-2"></i>Independent Technicians ({{ $independentTechnicians->count() }})</h6>
            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#assignIndependentModal">
                <i class="fas fa-user-plus me-1"></i> Assign to Team
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($independentTechnicians as $technician)
                    <div class="col-md-4 col-lg-3 mb-3">
                        <div class="card h-100 border">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $technician->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($technician->name) }}" class="rounded-circle me-2" style="width:40px;height:40px;">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h6 class="mb-0 text-truncate">{{ $technician->name }}</h6>
                                        <small class="text-muted">{{ $technician->employee_id }}</small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary w-100 assign-independent" data-id="{{ $technician->id }}" data-name="{{ $technician->name }}">
                                        <i class="fas fa-user-plus me-1"></i> Assign
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Table View --}}
<div id="tableView" style="display: none;">
    <div class="card">
        <div class="card-header bg-white">
            <div class="row g-2">
                <div class="col-md-3">
                    <select id="filterSupervisor" class="form-select form-select-sm">
                        <option value="">All Supervisors</option>
                        <option value="independent">Independent Only</option>
                        @foreach($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table id="techniciansTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Employee</th>
                        <th>Name</th>
                        <th>Supervisor</th>
                        <th>Coverage</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@include('admin.teams._modals')
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#cardViewBtn').on('click', function() {
        $(this).addClass('active'); $('#tableViewBtn').removeClass('active');
        $('#cardView').show(); $('#tableView').hide();
    });
    
    $('#tableViewBtn').on('click', function() {
        $(this).addClass('active'); $('#cardViewBtn').removeClass('active');
        $('#tableView').show(); $('#cardView').hide();
        if (!$.fn.DataTable.isDataTable('#techniciansTable')) { initDataTable(); }
    });
    
    function initDataTable() {
        window.techniciansTable = $('#techniciansTable').DataTable({
            processing: true, serverSide: true,
            ajax: { url: '{{ route("admin.teams.datatable") }}', data: function(d) { d.supervisor_id = $('#filterSupervisor').val(); d.status = $('#filterStatus').val(); } },
            columns: [
                { data: null, orderable: false, render: function(data) { return '<input type="checkbox" class="row-select" value="' + data.id + '">'; }},
                { data: 'employee_id' }, { data: 'name' }, { data: 'supervisor_name' }, { data: 'coverage' }, { data: 'status_badge' }, { data: 'actions', orderable: false }
            ],
            order: [[2, 'asc']]
        });
    }
    
    $('#filterSupervisor, #filterStatus').on('change', function() { if (window.techniciansTable) { window.techniciansTable.ajax.reload(); } });
    $(document).on('change', '#selectAll', function() { $('.row-select').prop('checked', $(this).is(':checked')); });
    
    $(document).on('click', '.assign-independent, .reassign-technician', function() {
        $('#reassignTechnicianId').val($(this).data('id'));
        $('#reassignTechnicianName').text($(this).data('name'));
        $('#reassignSupervisorSelect').val($(this).data('supervisor') || '');
        $('#reassignModal').modal('show');
    });
    
    $('#reassignForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("admin.teams.assign") }}', method: 'POST',
            data: { technician_id: $('#reassignTechnicianId').val(), supervisor_id: $('#reassignSupervisorSelect').val() },
            success: function(response) { $('#reassignModal').modal('hide'); showToast('success', response.message); setTimeout(function() { location.reload(); }, 1000); },
            error: function(xhr) { showToast('error', xhr.responseJSON?.message || 'Failed'); }
        });
    });
    
    $(document).on('click', '.remove-from-team', function() {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({ title: 'Remove from Team?', text: name + ' will become independent.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Yes, remove' }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({ url: '/admin/teams/' + id + '/remove', method: 'POST', success: function(response) { showToast('success', response.message); setTimeout(function() { location.reload(); }, 1000); }, error: function(xhr) { showToast('error', xhr.responseJSON?.message || 'Failed'); } });
            }
        });
    });
    
    $('#bulkAssignForm').on('submit', function(e) {
        e.preventDefault();
        var selectedIds = []; $('.row-select:checked').each(function() { selectedIds.push($(this).val()); });
        if (selectedIds.length === 0) { showToast('warning', 'Please select technicians'); return; }
        $.ajax({ url: '{{ route("admin.teams.bulk-assign") }}', method: 'POST', data: { technician_ids: selectedIds, supervisor_id: $('#bulkSupervisorSelect').val() },
            success: function(response) { $('#bulkAssignModal').modal('hide'); showToast('success', response.message); setTimeout(function() { location.reload(); }, 1000); },
            error: function(xhr) { showToast('error', xhr.responseJSON?.message || 'Failed'); }
        });
    });
    
    $(document).on('click', '.view-member', function() {
        var id = $(this).data('id');
        $.ajax({ url: '/admin/teams/' + id, method: 'GET', success: function(response) {
            var user = response.user, stats = response.statistics;
            $('#memberDetailName').text(user.name); $('#memberDetailEmployee').text(user.employee_id); $('#memberDetailEmail').text(user.email);
            $('#memberDetailPhone').text(user.phone || '-'); $('#memberDetailStatus').html(user.status_badge); $('#memberDetailSupervisor').text(user.supervisor?.name || 'Independent');
            $('#memberDetailTotalJobs').text(stats.total_jobs); $('#memberDetailCompletedJobs').text(stats.completed_jobs); $('#memberDetailPendingJobs').text(stats.pending_jobs);
            $('#memberDetailModal').modal('show');
        }, error: function() { showToast('error', 'Failed to load'); } });
    });
});
</script>
@endpush
