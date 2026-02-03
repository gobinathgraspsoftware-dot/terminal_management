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
                        <select id="filter_type" class="form-select form-select-sm">
                            <option value="">All Types</option>
                            @foreach($filterOptions['movement_types'] as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Serial No</label>
                        <input type="text" id="filter_serial" class="form-control form-control-sm"
                               placeholder="Search serial...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Date From</label>
                        <input type="date" id="filter_date_from" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Date To</label>
                        <input type="date" id="filter_date_to" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select id="filter_reversed" class="form-select form-select-sm">
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

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#movementTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.serial-movement-history.datatable") }}',
            data: function(d) {
                d.transaction_type = $('#filter_type').val();
                d.serial_no        = $('#filter_serial').val();
                d.date_from        = $('#filter_date_from').val();
                d.date_to          = $('#filter_date_to').val();
                d.show_reversed    = $('#filter_reversed').val();
            },
            error: function(xhr) {
                console.error('DataTable AJAX error:', xhr);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to load movement data.');
                }
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'date_display', name: 'transaction_date' },
            { data: 'serial_info', name: 'serial_no' },
            { data: 'type_badge', name: 'transaction_type' },
            { data: 'from_to', name: 'from_location_type', orderable: false },
            { data: 'reference_display', name: 'reference_type' },
            { data: 'performed_by', name: 'created_by', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            emptyTable: 'No movement records found',
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading...'
        },
    });

    // Filter buttons
    $('#btnFilter').on('click', function() { table.draw(); });
    $('#btnReset').on('click', function() {
        $('#filterForm')[0].reset();
        table.draw();
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
                    table.draw();
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
});
</script>
@endsection
