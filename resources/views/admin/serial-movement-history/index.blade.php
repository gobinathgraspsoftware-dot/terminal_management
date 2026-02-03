@extends('layouts.app')

@section('title', 'Serial Movement History - TMS')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-clock-history me-2"></i>Serial Movement History</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory-serials.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Movement History</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-bold text-primary">{{ number_format($stats['total'] ?? 0) }}</div>
                    <small class="text-muted">Total Movements</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-bold text-success">{{ number_format($stats['active'] ?? 0) }}</div>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-bold text-danger">{{ number_format($stats['reversed'] ?? 0) }}</div>
                    <small class="text-muted">Reversed</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-bold text-info">{{ number_format($stats['today'] ?? 0) }}</div>
                    <small class="text-muted">Today</small>
                </div>
            </div>
        </div>
        @foreach(array_slice($stats['by_type'] ?? [], 0, 2) as $type => $count)
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="fs-4 fw-bold">{{ number_format($count) }}</div>
                    <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $type)) }}</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Movement Type</label>
                        <select id="filter_type" name="filter_type" class="form-select form-select-sm">
                            <option value="">All Types</option>
                            @foreach($filterOptions['movement_types'] as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Serial No</label>
                        <input type="text" id="filter_serial" name="filter_serial" class="form-control form-control-sm"
                               placeholder="Search serial...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Date From</label>
                        <input type="date" id="filter_date_from" name="filter_date_from" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Date To</label>
                        <input type="date" id="filter_date_to" name="filter_date_to" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select id="filter_reversed" name="filter_reversed" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="active_only">Active Only</option>
                            <option value="reversed_only">Reversed Only</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="btnFilter" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        <button type="button" id="btnReset" class="btn btn-outline-secondary btn-sm w-100 mt-1">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="movementTable" class="table table-hover table-sm align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="10%">Date</th>
                            <th width="15%">Serial</th>
                            <th width="18%">Type</th>
                            <th width="20%">From → To</th>
                            <th width="12%">Reference</th>
                            <th width="10%">By</th>
                            <th width="10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Reversal Confirmation Modal --}}
<div class="modal fade" id="reversalModal" tabindex="-1" aria-labelledby="reversalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="reversalModalLabel">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Reverse Movement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    You are about to reverse movement <strong id="reversalTxn"></strong>.
                    This will create a new reversal entry and mark the original as reversed.
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Reason for Reversal</label>
                    <textarea id="reversalRemarks" class="form-control" rows="3"
                              placeholder="Enter reason for reversal..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="btnConfirmReverse" class="btn btn-warning">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Confirm Reversal
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    console.log('Initializing Serial Movement History DataTable...');

    // Initialize DataTable
    var table = $('#movementTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.serial-movement-history.datatable") }}',
            type: 'GET',
            data: function(d) {
                // Get filter values
                var filterType = $('#filter_type').val();
                var filterSerial = $('#filter_serial').val();
                var filterDateFrom = $('#filter_date_from').val();
                var filterDateTo = $('#filter_date_to').val();
                var filterReversed = $('#filter_reversed').val();

                // Only add non-empty values
                if (filterType && filterType !== '') {
                    d.transaction_type = filterType;
                }
                if (filterSerial && filterSerial.trim() !== '') {
                    d.serial_no = filterSerial.trim();
                }
                if (filterDateFrom && filterDateFrom !== '') {
                    d.date_from = filterDateFrom;
                }
                if (filterDateTo && filterDateTo !== '') {
                    d.date_to = filterDateTo;
                }
                if (filterReversed && filterReversed !== '') {
                    d.show_reversed = filterReversed;
                }

                // Debug: Log filter values
                console.log('DataTable Filter Values:', {
                    transaction_type: d.transaction_type,
                    serial_no: d.serial_no,
                    date_from: d.date_from,
                    date_to: d.date_to,
                    show_reversed: d.show_reversed
                });

                return d;
            },
            error: function(xhr, error, code) {
                console.error('DataTable AJAX Error Details:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error,
                    code: code
                });

                if (typeof toastr !== 'undefined') {
                    var errorMsg = 'Failed to load movement data.';
                    if (xhr.status === 403) {
                        errorMsg = 'Permission denied. Please check your access rights.';
                    } else if (xhr.status === 404) {
                        errorMsg = 'DataTable endpoint not found. Please check the route configuration.';
                    } else if (xhr.status === 500) {
                        errorMsg = 'Server error occurred. Please check the logs.';
                    }
                    toastr.error(errorMsg);
                } else {
                    alert('Error loading data. Status: ' + xhr.status);
                }
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'date_display', name: 'transaction_date' },
            { data: 'serial_info', name: 'serial_no', orderable: false },
            { data: 'type_badge', name: 'transaction_type', orderable: false },
            { data: 'from_to', name: 'from_location_type', orderable: false },
            { data: 'reference_display', name: 'reference_type', orderable: false },
            { data: 'performed_by', name: 'created_by', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            emptyTable: 'No movement records found',
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading...',
            zeroRecords: 'No matching records found with current filters'
        },
        drawCallback: function(settings) {
            console.log('DataTable drawn. Records:', settings.json ? settings.json.recordsTotal : 'N/A');
        }
    });

    // Filter button click handler
    $('#btnFilter').on('click', function() {
        console.log('Filter button clicked');
        console.log('Current filter values:', {
            type: $('#filter_type').val(),
            serial: $('#filter_serial').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val(),
            reversed: $('#filter_reversed').val()
        });

        // Reload the DataTable with new filters
        table.ajax.reload(function(json) {
            console.log('DataTable reloaded with filters:', json);
        }, false); // false = don't reset pagination
    });

    // Reset button click handler
    $('#btnReset').on('click', function() {
        console.log('Reset button clicked');

        // Clear all filter fields
        $('#filterForm')[0].reset();
        $('#filter_type').val('');
        $('#filter_serial').val('');
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
        $('#filter_reversed').val('');

        // Reload DataTable
        table.ajax.reload(function(json) {
            console.log('DataTable reset and reloaded:', json);
        }, true); // true = reset to page 1
    });

    // Allow Enter key to trigger filter
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        $('#btnFilter').click();
        return false;
    });

    // Reversal flow
    var reversalId = null;

    $(document).on('click', '.btn-reverse', function() {
        reversalId = $(this).data('id');
        $('#reversalTxn').text($(this).data('txn'));
        $('#reversalRemarks').val('');
        $('#reversalModal').modal('show');
    });

    $('#btnConfirmReverse').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');

        $.ajax({
            url: '{{ url("admin/serial-movement-history") }}/' + reversalId + '/reverse',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { remarks: $('#reversalRemarks').val() },
            success: function(response) {
                if (response.success) {
                    $('#reversalModal').modal('hide');
                    table.ajax.reload(null, false); // Reload without resetting pagination

                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message);
                    } else {
                        alert(response.message);
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message);
                    } else {
                        alert('Error: ' + response.message);
                    }
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || 'Failed to reverse movement.';
                console.error('Reversal error:', xhr);

                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                } else {
                    alert('Error: ' + msg);
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-arrow-counterclockwise me-1"></i>Confirm Reversal');
            }
        });
    });

    // Debug: Log when page is ready
    console.log('Serial Movement History page initialized successfully');
    console.log('DataTable object:', table);
});
</script>
@endpush
