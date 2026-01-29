@extends('layouts.app')

@section('title', 'Partners - TMS')

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-building me-2"></i>Partners</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Partners</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-value">{{ $statistics['total'] ?? 0 }}</div>
                    <div class="stats-label">Total Partners</div>
                </div>
                <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-building"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-value text-success">{{ $statistics['active'] ?? 0 }}</div>
                    <div class="stats-label">Active Partners</div>
                </div>
                <div class="stats-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-value text-info">{{ $statistics['with_clients'] ?? 0 }}</div>
                    <div class="stats-label">With Clients</div>
                </div>
                <div class="stats-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Job Intake Method</label>
                <select id="filterJobIntake" class="form-select">
                    <option value="">All Methods</option>
                    <option value="manual">Manual</option>
                    <option value="import">Import</option>
                    <option value="api">API</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">State</label>
                <select id="filterState" class="form-select">
                    <option value="">All States</option>
                    <option value="Johor">Johor</option>
                    <option value="Kedah">Kedah</option>
                    <option value="Kelantan">Kelantan</option>
                    <option value="Melaka">Melaka</option>
                    <option value="Negeri Sembilan">Negeri Sembilan</option>
                    <option value="Pahang">Pahang</option>
                    <option value="Penang">Penang</option>
                    <option value="Perak">Perak</option>
                    <option value="Perlis">Perlis</option>
                    <option value="Sabah">Sabah</option>
                    <option value="Sarawak">Sarawak</option>
                    <option value="Selangor">Selangor</option>
                    <option value="Terengganu">Terengganu</option>
                    <option value="Kuala Lumpur">Kuala Lumpur</option>
                    <option value="Labuan">Labuan</option>
                    <option value="Putrajaya">Putrajaya</option>
                </select>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" id="btnResetFilters" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Reset Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Partners Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table me-2"></i>Partners List</span>
        <span class="badge bg-secondary" id="tableCount">0 records</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="partnersTable" class="table table-striped table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Partner Code</th>
                        <th>Partner Name</th>
                        <th>PIC</th>
                        <th>Location</th>
                        <th>Job Intake</th>
                        <th>Clients</th>
                        <th>Jobs</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    let partnersTable = $('#partnersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('supervisor.partners.datatable') }}",
            data: function(d) {
                d.job_intake_method = $('#filterJobIntake').val();
                d.state = $('#filterState').val();
            }
        },
        columns: [
            { data: 'partner_code', name: 'partner_code' },
            { data: 'partner_name', name: 'partner_name' },
            { data: 'pic_info', name: 'pic_name', orderable: false },
            { 
                data: null, 
                name: 'city',
                render: function(data) {
                    let parts = [];
                    if (data.city) parts.push(data.city);
                    if (data.state) parts.push(data.state);
                    return parts.join(', ') || '-';
                }
            },
            { data: 'job_intake_badge', name: 'job_intake_method', orderable: false },
            { 
                data: 'clients_count', 
                name: 'clients_count',
                className: 'text-center',
                render: function(data) {
                    return '<span class="badge bg-info">' + data + '</span>';
                }
            },
            { 
                data: 'job_orders_count', 
                name: 'job_orders_count',
                className: 'text-center',
                render: function(data) {
                    return '<span class="badge bg-secondary">' + data + '</span>';
                }
            },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        drawCallback: function(settings) {
            let info = this.api().page.info();
            $('#tableCount').text(info.recordsTotal + ' records');
        },
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading...',
            emptyTable: 'No partners found',
            zeroRecords: 'No matching partners found'
        }
    });

    // Filter change handlers
    $('#filterJobIntake, #filterState').on('change', function() {
        partnersTable.ajax.reload();
    });

    // Reset filters
    $('#btnResetFilters').on('click', function() {
        $('#filterJobIntake').val('');
        $('#filterState').val('');
        partnersTable.ajax.reload();
    });
});
</script>
@endpush
