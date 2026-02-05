@extends('layouts.app')

@section('title', 'Stock Ledger')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-journal-text me-2"></i>Stock Ledger
            </h1>
            <p class="text-muted mb-0">Complete inventory movement history</p>
        </div>
        <div>
            @can('export_stock_ledger')
            <button type="button" class="btn btn-success" id="exportBtn">
                <i class="bi bi-file-earmark-excel me-1"></i>Export
            </button>
            @endcan
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Model</label>
                        <select class="form-select" name="model_id" id="modelFilter">
                            <option value="">All Models</option>
                            @foreach($models as $model)
                            <option value="{{ $model->id }}">{{ $model->model_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Transaction Type</label>
                        <select class="form-select" name="transaction_type" id="typeFilter">
                            <option value="">All Types</option>
                            @foreach(\App\Models\StockLedger::TYPE_OPTIONS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Location Type</label>
                        <select class="form-select" name="location_type" id="locationTypeFilter">
                            <option value="">All Locations</option>
                            <option value="depot">Depot</option>
                            <option value="technician">Technician</option>
                            <option value="site">Site</option>
                            <option value="vendor">Vendor</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Specific Location</label>
                        <select class="form-select" name="location_id" id="locationIdFilter">
                            <option value="">Select location type first</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="from_date" id="fromDateFilter">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="to_date" id="toDateFilter">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" class="form-control" name="serial_no" id="serialNoFilter" placeholder="Search serial...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Show Reversed</label>
                        <select class="form-select" name="show_reversed" id="showReversedFilter">
                            <option value="0">Hide Reversed</option>
                            <option value="1">Show All</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>Apply Filters
                        </button>
                        <button type="button" class="btn btn-secondary" id="resetFilters">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="ledgerTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transaction No</th>
                            <th>Type</th>
                            <th>Model</th>
                            <th>Serial No</th>
                            <th>Qty</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Reference</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Reversal Modal -->
<div class="modal fade" id="reversalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-2"></i>Reverse Movement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reversalForm">
                <div class="modal-body">
                    <input type="hidden" id="reversalLedgerId">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This will reverse the stock movement and create a counter-entry.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Reversal</label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="Optional: explain why this movement is being reversed"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reverse Movement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#ledgerTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.stock-ledger.index") }}',
            data: function(d) {
                d.model_id = $('#modelFilter').val();
                d.transaction_type = $('#typeFilter').val();
                d.location_type = $('#locationTypeFilter').val();
                d.location_id = $('#locationIdFilter').val();
                d.from_date = $('#fromDateFilter').val();
                d.to_date = $('#toDateFilter').val();
                d.serial_no = $('#serialNoFilter').val();
                d.show_reversed = $('#showReversedFilter').val();
            }
        },
        columns: [
            { data: 'transaction_date' },
            { data: 'transaction_no' },
            { data: 'type_badge', orderable: false },
            { data: 'model_name' },
            { data: 'serial_no' },
            { data: 'quantity', className: 'text-end' },
            { data: 'from_location' },
            { data: 'to_location' },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    if (data.reference_url) {
                        return '<a href="' + data.reference_url + '" target="_blank">' + data.reference + '</a>';
                    }
                    return data.reference;
                }
            },
            { data: 'created_by' },
            { data: 'reversal_status', orderable: false },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    let actions = '<div class="btn-group" role="group">';
                    actions += '<a href="/admin/stock-ledger/' + data.id + '" class="btn btn-sm btn-info">';
                    actions += '<i class="bi bi-eye"></i></a>';

                    @can('reverse_stock_ledger')
                    if (data.can_reverse && !data.is_reversed) {
                        actions += '<button type="button" class="btn btn-sm btn-warning reverse-btn" data-id="' + data.id + '">';
                        actions += '<i class="bi bi-arrow-counterclockwise"></i></button>';
                    }
                    @endcan

                    actions += '</div>';
                    return actions;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#filterForm')[0].reset();
        table.ajax.reload();
    });

    // Location type change - load specific locations
    $('#locationTypeFilter').on('change', function() {
        const type = $(this).val();
        const $locationId = $('#locationIdFilter');

        $locationId.empty().append('<option value="">Loading...</option>');

        if (!type) {
            $locationId.empty().append('<option value="">Select location type first</option>');
            return;
        }

        let url = '';
        if (type === 'depot') {
            url = '{{ route("admin.depots.index") }}';
        } else if (type === 'technician') {
            url = '{{ route("admin.users.index") }}?role=technician';
        }

        if (url) {
            $.get(url, function(data) {
                $locationId.empty().append('<option value="">All ' + type + 's</option>');
                // Populate based on response
                // This is a simplified version - adjust based on your actual API response
            });
        }
    });

    // Reverse button click
    $(document).on('click', '.reverse-btn', function() {
        const ledgerId = $(this).data('id');
        $('#reversalLedgerId').val(ledgerId);
        $('#reversalModal').modal('show');
    });

    // Reversal form submission
    $('#reversalForm').on('submit', function(e) {
        e.preventDefault();

        const ledgerId = $('#reversalLedgerId').val();
        const reason = $('textarea[name="reason"]').val();

        $.ajax({
            url: '/admin/stock-ledger/' + ledgerId + '/reverse',
            method: 'POST',
            data: {
                reason: reason,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#reversalModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                table.ajax.reload();
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Reversal failed';
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: error
                });
            }
        });
    });

    // Export button
    $('#exportBtn').on('click', function() {
        const filters = $('#filterForm').serialize();
        window.location.href = '{{ route("admin.stock-ledger.export") }}?' + filters;
    });
});
</script>
@endpush
