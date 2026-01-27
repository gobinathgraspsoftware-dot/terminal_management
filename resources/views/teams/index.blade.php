@extends('layouts.app')

@section('title', 'My Team - TMS')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">My Team</h1>
            <p class="text-muted mb-0">Manage and monitor your team members</p>
        </div>
        <div>
            <span class="badge bg-primary fs-6">{{ $supervisor->name }}</span>
        </div>
    </div>

    <!-- Team Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Team Members</h6>
                            <h3 class="mb-0">{{ $stats['total_members'] }}</h3>
                            <small class="text-success">
                                <i class="fas fa-check-circle"></i> {{ $stats['active_members'] }} active
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                                <i class="fas fa-tasks fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Active Jobs</h6>
                            <h3 class="mb-0">{{ $stats['total_active_jobs'] }}</h3>
                            <small class="text-muted">
                                <i class="fas fa-chart-line"></i> In progress
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                                <i class="fas fa-check-double fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Completed (30d)</h6>
                            <h3 class="mb-0">{{ $stats['total_completed_jobs'] }}</h3>
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt"></i> This month
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 text-info rounded-3 p-3">
                                <i class="fas fa-chart-bar fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Avg Jobs/Tech</h6>
                            <h3 class="mb-0">{{ $stats['average_jobs_per_tech'] }}</h3>
                            <small class="text-muted">
                                <i class="fas fa-user"></i> Per technician
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Members Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users text-primary me-2"></i>Team Members
                </h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshTable()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($teamMembers->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No Team Members Yet</h5>
                    <p class="text-muted">Contact your administrator to assign technicians to your team.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table id="teamMembersTable" class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Technician</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Active Jobs</th>
                                <th class="text-center">Completed (30d)</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($teamMembers as $member)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                @if($member->avatar)
                                                    <img src="{{ asset('storage/' . $member->avatar) }}" 
                                                         class="rounded-circle" width="40" height="40" alt="Avatar">
                                                @else
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <strong>{{ substr($member->name, 0, 2) }}</strong>
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $member->name }}</h6>
                                                <small class="text-muted">{{ $member->employee_id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $member->email }}</td>
                                    <td>{{ $member->phone ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($member->is_active)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle"></i> Active
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-times-circle"></i> Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">
                                            {{ $member->active_jobs ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">
                                            {{ $member->completed_jobs ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('teams.show', $member) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
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

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .avatar-sm img {
        object-fit: cover;
    }
    
    .card {
        transition: transform 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#teamMembersTable').DataTable({
        responsive: true,
        order: [[1, 'asc']], // Sort by name
        pageLength: 10,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search technicians..."
        }
    });

    // Refresh table function
    window.refreshTable = function() {
        location.reload();
    };
});
</script>
@endpush
@endsection
