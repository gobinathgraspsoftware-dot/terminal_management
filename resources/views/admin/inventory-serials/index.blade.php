@extends('layouts.app')

@section('title', 'Inventory Serial Tracking - Admin')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-upc-scan me-2"></i>Inventory Serial Tracking</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Serial Tracking</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <!-- Barcode Scanner Input -->
            <div class="input-group" style="width: 280px;">
                <span class="input-group-text bg-warning text-dark"><i class="bi bi-upc-scan"></i></span>
                <input type="text" id="barcodeInput" class="form-control" placeholder="Scan barcode or type serial..."
                       autofocus autocomplete="off">
            </div>
            <!-- Serial Lookup Button -->
            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#serialLookupModal">
                <i class="bi bi-search me-1"></i> Serial Lookup
            </button>
            @can('create_inventory')
            <a href="{{ route('admin.inventory-serials.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Serial
            </a>
            @endcan
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-label">Total Serials</div>
                    <div class="stats-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-hash"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-label">In Stock</div>
                    <div class="stats-value text-success">{{ number_format($stats['in_stock']) }}</div>
                </div>
                <div class="stats-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-label">Issued to Tech</div>
                    <div class="stats-value text-info">{{ number_format($stats['issued_to_tech']) }}</div>
                </div>
                <div class="stats-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-person-gear"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stats-label">Installed</div>
                    <div class="stats-value text-primary">{{ number_format($stats['installed']) }}</div>
                </div>
                <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Stats Row -->
<div class="row mb-4">
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card text-center p-2">
            <small class="text-muted">Under Service</small>
            <strong class="text-warning">{{ $stats['under_service'] }}</strong>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card text-center p-2">
            <small class="text-muted">Reserved</small>
            <strong class="text-dark">{{ $stats['reserved'] }}</strong>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card text-center p-2">
            <small class="text-muted">Returned</small>
            <strong class="text-secondary">{{ $stats['returned_to_vendor'] }}</strong>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card text-center p-2">
            <small class="text-muted">Wasted</small>
            <strong class="text-danger">{{ $stats['wasted'] }}</strong>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card text-center p-2">
            <small class="text-muted">Warranty Active</small>
            <strong class="text-success">{{ $stats['warranty_active'] }}</strong>
        </div>
    </div>
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card text-center p-2">
            <small class="text-muted">Expiring (30d)</small>
            <strong class="text-warning">{{ $stats['warranty_expiring'] }}</strong>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">Status</label>
                <select id="filterStatus" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach($filterOptions['statuses'] as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Location Type</label>
                <select id="filterLocationType" class="form-select">
                    <option value="">All Locations</option>
                    @foreach($filterOptions['location_types'] as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Depot</label>
                <select id="filterDepot" class="form-select">
                    <option value="">All Depots</option>
                    @foreach($filterOptions['depots'] as $depot)
                        <option value="{{ $depot->id }}">{{ $depot->depot_name }} ({{ $depot->depot_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Warranty</label>
                <select id="filterWarranty" class="form-select">
                    <option value="">All</option>
                    <option value="active">Active Warranty</option>
                    <option value="expired">Expired Warranty</option>
                </select>
            </div>
        </div>
        <div class="mt-3 text-end">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearFilters">
                <i class="bi bi-x-circle me-1"></i> Clear Filters
            </button>
        </div>
    </div>
</div>

<!-- DataTable -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-table me-2"></i>Serial Numbers</h5>
        @can('export_inventory')
        <button class="btn btn-sm btn-outline-success" id="btnExport">
            <i class="bi bi-download me-1"></i> Export
        </button>
        @endcan
    </div>
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
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Include Reusable Serial Lookup Modal -->
@include('components.serial-lookup-modal')
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#serialsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.inventory-serials.datatable") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.location_type = $('#filterLocationType').val();
                d.depot_id = $('#filterDepot').val();
                d.warranty_status = $('#filterWarranty').val();
            },
            error: function(xhr) {
                console.error('DataTable AJAX error:', xhr);
                if (xhr.status === 401) {
                    window.location.href = '{{ route("login") }}';
                }
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'serial_info', name: 'serial_no' },
            { data: 'category_name', name: 'category_name', orderable: false },
            { data: 'status_badge', name: 'current_status' },
            { data: 'location_info', name: 'current_location_type' },
            { data: 'warranty', name: 'warranty_end', orderable: false },
            { data: 'grn_info', name: 'grn_id', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No serial records found.',
        }
    });

    // Filter change handlers
    $('#filterStatus, #filterLocationType, #filterDepot, #filterWarranty').on('change', function() {
        table.ajax.reload();
    });

    // Clear filters
    $('#btnClearFilters').on('click', function() {
        $('#filterStatus, #filterLocationType, #filterDepot, #filterWarranty').val('');
        table.ajax.reload();
    });

    // Barcode Scanner Input - auto-submit on Enter or after pause
    var barcodeTimer;
    $('#barcodeInput').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            var serial = $(this).val().trim();
            if (serial.length > 0) {
                lookupSerial(serial);
            }
        }
    }).on('input', function() {
        clearTimeout(barcodeTimer);
        var input = $(this);
        barcodeTimer = setTimeout(function() {
            var serial = input.val().trim();
            if (serial.length >= 5) { // Auto-trigger after pause for barcode scanners
                lookupSerial(serial);
            }
        }, 300); // 300ms delay for barcode scanner
    });

    // Serial lookup from barcode
    function lookupSerial(serialNo) {
        $.ajax({
            url: '{{ route("api.serials.validate") }}',
            data: { serial_no: serialNo },
            success: function(response) {
                if (response.exists && response.serial) {
                    // Redirect to detail page
                    window.location.href = '{{ route("admin.inventory-serials.show", ":id") }}'.replace(':id', response.serial.id);
                } else {
                    showToast('Serial number not found: ' + serialNo, 'warning');
                    $('#barcodeInput').val('').focus();
                }
            },
            error: function() {
                showToast('Error looking up serial number.', 'error');
                $('#barcodeInput').val('').focus();
            }
        });
    }

    // Delete handler
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var serial = $(this).data('serial');

        confirmAction(
            'Delete Serial?',
            'Are you sure you want to delete serial ' + serial + '? This action can be undone.',
            function() {
                $.ajax({
                    url: '{{ route("admin.inventory-serials.index") }}/' + id,
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            table.ajax.reload();
                        } else {
                            showToast(response.message || 'Delete failed.', 'error');
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message || 'Failed to delete serial.';
                        showToast(msg, 'error');
                    }
                });
            }
        );
    });
});
</script>
@endpush
