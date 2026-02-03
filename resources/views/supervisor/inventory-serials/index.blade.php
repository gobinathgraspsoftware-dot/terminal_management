@extends('layouts.app')

@section('title', 'Inventory Serial Tracking - Supervisor')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-upc-scan me-2"></i>Team Serial Tracking</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Serial Tracking</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <div class="input-group" style="width: 280px;">
                <span class="input-group-text bg-warning text-dark"><i class="bi bi-upc-scan"></i></span>
                <input type="text" id="barcodeInput" class="form-control" placeholder="Scan barcode..." autofocus autocomplete="off">
            </div>
            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#serialLookupModal">
                <i class="bi bi-search me-1"></i> Lookup
            </button>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-label">Total Tracked</div>
                    <div class="stats-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="stats-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-hash"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-label">In Stock</div>
                    <div class="stats-value text-success">{{ number_format($stats['in_stock']) }}</div>
                </div>
                <div class="stats-icon bg-success bg-opacity-10 text-success"><i class="bi bi-box-seam"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-label">With Technicians</div>
                    <div class="stats-value text-info">{{ number_format($stats['issued_to_tech']) }}</div>
                </div>
                <div class="stats-icon bg-info bg-opacity-10 text-info"><i class="bi bi-person-gear"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-label">Installed</div>
                    <div class="stats-value text-primary">{{ number_format($stats['installed']) }}</div>
                </div>
                <div class="stats-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-check-circle"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <select id="filterStatus" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach($filterOptions['statuses'] as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select id="filterLocationType" class="form-select">
                    <option value="">All Locations</option>
                    @foreach($filterOptions['location_types'] as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-outline-secondary" id="btnClearFilters">
                    <i class="bi bi-x-circle me-1"></i> Clear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- DataTable -->
<div class="card">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-table me-2"></i>Serial Numbers</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="serialsTable" class="table table-hover table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th width="30">#</th>
                        <th>Serial / Model</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th>Warranty</th>
                        <th>GRN</th>
                        <th width="80">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@include('components.serial-lookup-modal')
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#serialsTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: '{{ route("supervisor.inventory-serials.datatable") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.location_type = $('#filterLocationType').val();
            },
            error: function(xhr) { if (xhr.status === 401) window.location.href = '{{ route("login") }}'; }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'serial_info', name: 'serial_no' },
            { data: 'category_name', orderable: false },
            { data: 'status_badge', name: 'current_status' },
            { data: 'location_info', name: 'current_location_type' },
            { data: 'warranty', orderable: false },
            { data: 'grn_info', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']], pageLength: 25, responsive: true,
        language: { emptyTable: 'No serial records found for your team.' }
    });

    $('#filterStatus, #filterLocationType').on('change', function() { table.ajax.reload(); });
    $('#btnClearFilters').on('click', function() {
        $('#filterStatus, #filterLocationType').val('');
        table.ajax.reload();
    });

    // Barcode scanner
    $('#barcodeInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            var serial = $(this).val().trim();
            if (serial) {
                $.get('{{ route("api.serials.validate") }}', { serial_no: serial }, function(r) {
                    if (r.exists && r.serial) {
                        window.location.href = '{{ route("supervisor.inventory-serials.show", ":id") }}'.replace(':id', r.serial.id);
                    } else {
                        showToast('Serial not found: ' + serial, 'warning');
                        $('#barcodeInput').val('').focus();
                    }
                });
            }
        }
    });
});
</script>
@endpush
