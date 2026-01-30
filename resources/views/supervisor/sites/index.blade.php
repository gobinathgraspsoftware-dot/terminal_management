@extends('layouts.app')

@section('title', 'Sites Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Sites / Branches</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Sites</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Info Alert for Coverage States -->
    @php
        $coverageStates = json_decode(auth()->user()->coverage_states, true) ?? [];
    @endphp
    @if(!empty($coverageStates))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Coverage States:</strong> You can view sites in: {{ implode(', ', $coverageStates) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 text-primary rounded p-3">
                                <i class="bi bi-geo-alt fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total Sites</div>
                            <div class="fs-4 fw-semibold">{{ $stats['total'] ?? 0 }}</div>
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
                            <div class="bg-success bg-opacity-10 text-success rounded p-3">
                                <i class="bi bi-check-circle fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Active Sites</div>
                            <div class="fs-4 fw-semibold">{{ $stats['active'] ?? 0 }}</div>
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
                            <div class="bg-secondary bg-opacity-10 text-secondary rounded p-3">
                                <i class="bi bi-x-circle fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Inactive Sites</div>
                            <div class="fs-4 fw-semibold">{{ $stats['inactive'] ?? 0 }}</div>
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
                            <div class="bg-info bg-opacity-10 text-info rounded p-3">
                                <i class="bi bi-box-seam fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Sites with Assets</div>
                            <div class="fs-4 fw-semibold">{{ $stats['with_assets'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Client</label>
                    <select id="filter-client" class="form-select form-select-sm">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->client_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">State</label>
                    <select id="filter-state" class="form-select form-select-sm">
                        <option value="">All States</option>
                        @foreach($states as $state)
                        <option value="{{ $state }}">{{ $state }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Status</label>
                    <select id="filter-status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="sites-table" class="table table-hover align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Site Code</th>
                            <th>Site Name</th>
                            <th>Client</th>
                            <th>Location</th>
                            <th>GPS</th>
                            <th>Assets</th>
                            <th>Status</th>
                            <th width="10%">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#sites-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("supervisor.sites.datatable") }}',
            data: function(d) {
                d.client_id = $('#filter-client').val();
                d.state = $('#filter-state').val();
                d.status = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'site_code', name: 'site_code' },
            { data: 'site_name', name: 'site_name' },
            { data: 'client_name', name: 'client_name' },
            { data: 'full_address', name: 'full_address', orderable: false },
            { data: 'coordinates', name: 'coordinates', orderable: false, searchable: false },
            { data: 'assets_count', name: 'assets_count', orderable: false, searchable: false, className: 'text-center' },
            { data: 'status_badge', name: 'status', className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true
    });

    // Filter change events
    $('#filter-client, #filter-state, #filter-status').on('change', function() {
        table.draw();
    });
});
</script>
@endpush
