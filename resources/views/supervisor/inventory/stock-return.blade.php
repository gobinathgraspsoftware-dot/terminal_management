@extends('layouts.app')

@section('title', 'Stock Return — Inventory')

@section('content')
<div class="container-fluid py-4">

    {{-- ── Page Header ─────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="bi bi-box-arrow-in-left text-info me-2"></i>Stock Return
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Stock Return</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('supervisor.inventory.stock-return.create') }}"
               class="btn btn-info text-white btn-sm">
                <i class="bi bi-plus-circle me-1"></i>New Stock Return
            </a>
            <a href="{{ route('supervisor.inventory.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Inventory
            </a>
        </div>
    </div>

    {{-- ── Flash Messages ──────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Filter Card ─────────────────────────────────────────────────── --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3"
             style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#filterPanel">
            <span class="fw-semibold text-muted">
                <i class="bi bi-funnel me-2"></i>Filter Records
            </span>
            <i class="bi bi-chevron-down text-muted" id="filterChevron"></i>
        </div>
        <div class="collapse show" id="filterPanel">
            <div class="card-body pt-3 pb-4">
                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Item Type</label>
                        <select id="filter_item_type" class="form-select filter-select2">
                            <option value="">All Types</option>
                            <option value="router">Router</option>
                            <option value="accessory">Accessory</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Item Name</label>
                        <select id="filter_item_name" class="form-select filter-select2">
                            <option value="">All Items</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}">
                                    {{ $item->item_name }}
                                    @if($item->item_code)({{ $item->item_code }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Brand</label>
                        <select id="filter_brand" class="form-select filter-select2">
                            <option value="">All Brands</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand }}">{{ $brand }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Model</label>
                        <select id="filter_model" class="form-select filter-select2">
                            <option value="">All Models</option>
                            @foreach($models as $model)
                                <option value="{{ $model }}">{{ $model }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Accessory Type — conditional --}}
                    <div class="col-md-3" id="accessoryTypeWrapper" style="display:none;">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Accessory Type</label>
                        <select id="filter_accessory_type" class="form-select filter-select2">
                            <option value="">All Accessory Types</option>
                            <option value="sim_card">SIM Card</option>
                            <option value="antenna">Antenna</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Date From</label>
                        <input type="date" id="filter_date_from" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Date To</label>
                        <input type="date" id="filter_date_to" class="form-control">
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button id="btn_apply_filter" class="btn btn-primary">
                            <i class="bi bi-funnel-fill me-1"></i>Apply
                        </button>
                        <button id="btn_reset_filter" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ── DataTable ────────────────────────────────────────────────────── --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <span class="fw-semibold">
                <i class="bi bi-table me-2 text-info"></i>Stock Return Records
            </span>
            <span class="badge bg-info text-white" id="totalBadge">0 records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="stockReturnTable"
                       class="table table-hover table-striped align-middle mb-0"
                       style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th width="50">#</th>
                            <th>Movement No</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Router IDs</th>
                            <th>Qty</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Ticket No</th>
                            <th>Condition</th>
                            <th>Return Date</th>
                            <th>Performed By</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(function () {

    $('.filter-select2').select2({
        theme: 'bootstrap-5', width: '100%', allowClear: true,
        placeholder: function () { return $(this).find('option:first').text(); }
    });

    $('#filterPanel').on('show.bs.collapse hide.bs.collapse', function () {
        $('#filterChevron').toggleClass('bi-chevron-down bi-chevron-up');
    });

    $('#filter_item_type').on('change', function () {
        if ($(this).val() === 'accessory') {
            $('#accessoryTypeWrapper').show();
        } else {
            $('#accessoryTypeWrapper').hide();
            $('#filter_accessory_type').val(null).trigger('change.select2');
        }
    });

    var table = $('#stockReturnTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('supervisor.inventory.stock-return.datatable') }}',
            type: 'GET',
            data: function (d) {
                d.item_type         = $('#filter_item_type').val();
                d.inventory_item_id = $('#filter_item_name').val();
                d.brand             = $('#filter_brand').val();
                d.model             = $('#filter_model').val();
                d.accessory_type    = $('#filter_accessory_type').val();
                d.date_from         = $('#filter_date_from').val();
                d.date_to           = $('#filter_date_to').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex',     orderable: false, searchable: false },
            { data: 'movement_no' },
            { data: 'item_code',        orderable: false, searchable: false },
            { data: 'item_name',        orderable: false, searchable: false },
            { data: 'item_type',        orderable: false, searchable: false },
            { data: 'brand',            orderable: false, searchable: false },
            { data: 'model',            orderable: false, searchable: false },
            { data: 'router_ids',       orderable: false, searchable: false },
            {
                data: 'quantity', orderable: false, searchable: false,
                render: function (val) {
                    return '<span class="badge bg-info text-white">' + val + '</span>';
                }
            },
            { data: 'from_location',    orderable: false, searchable: false },
            { data: 'to_location',      orderable: false, searchable: false },
            { data: 'ticket_no',        orderable: false, searchable: false },
            { data: 'condition',        orderable: false, searchable: false },
            { data: 'stockreturn_date', orderable: false, searchable: false },
            { data: 'performed_by',     orderable: false, searchable: false },
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="text-center py-3"><div class="spinner-border text-info" role="status"></div></div>',
            emptyTable: '<div class="text-center py-3 text-muted"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No stock return records found.</div>',
            zeroRecords: '<div class="text-center py-3 text-muted"><i class="bi bi-search fs-2 d-block mb-2"></i>No records match your filter.</div>'
        },
        drawCallback: function () {
            $('#totalBadge').text(this.api().page.info().recordsTotal + ' records');
        }
    });

    $('#btn_apply_filter').on('click', function () { table.draw(); });
    $('#btn_reset_filter').on('click', function () {
        $('#filter_item_type, #filter_item_name, #filter_brand, #filter_model, #filter_accessory_type')
            .val(null).trigger('change.select2');
        $('#filter_date_from, #filter_date_to').val('');
        $('#accessoryTypeWrapper').hide();
        table.draw();
    });

});
</script>
@endpush
