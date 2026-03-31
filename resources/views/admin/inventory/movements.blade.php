@extends('layouts.app')

@section('title', 'Stock Movements — Inventory')

@section('content')
<div class="container-fluid py-4">

    {{-- ── Page Header ─────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="bi bi-arrow-left-right text-warning me-2"></i>Stock Movements
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Movements</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.inventory.movements.export') }}" id="btnExport"
               class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
            </a>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary btn-sm">
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
                <i class="bi bi-funnel me-2"></i>Filters
            </span>
            <i class="bi bi-chevron-down text-muted" id="filterChevron"></i>
        </div>
        <div class="collapse show" id="filterPanel">
            <div class="card-body pt-3 pb-4">
                <div class="row g-3">

                    {{-- Movement Type --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Movement Type</label>
                        <select id="filter_movement_type" class="form-select select2-filter">
                            <option value="">All Types</option>
                            @foreach($movementTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Item Type --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Item Type</label>
                        <select id="filter_item_type" class="form-select select2-filter">
                            <option value="">All Types</option>
                            <option value="router">Router</option>
                            <option value="accessory">Accessory</option>
                        </select>
                    </div>

                    {{-- Item Name --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Item Name</label>
                        <select id="filter_item_name" class="form-select select2-filter">
                            <option value="">All Items</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}">
                                    {{ $item->item_name }}
                                    @if($item->item_code) ({{ $item->item_code }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Brand --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Brand</label>
                        <select id="filter_brand" class="form-select select2-filter">
                            <option value="">All Brands</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand }}">{{ $brand }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Model --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Model</label>
                        <select id="filter_model" class="form-select select2-filter">
                            <option value="">All Models</option>
                            @foreach($models as $model)
                                <option value="{{ $model }}">{{ $model }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Accessory Type (conditional) --}}
                    <div class="col-md-3" id="accessoryTypeWrapper" style="display:none;">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Accessory Type</label>
                        <select id="filter_accessory_type" class="form-select select2-filter">
                            <option value="">All Accessory Types</option>
                            <option value="sim_card">SIM Card</option>
                            <option value="antenna">Antenna</option>
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Date From</label>
                        <input type="date" id="filter_date_from" class="form-control">
                    </div>

                    {{-- Date To --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted text-uppercase">Date To</label>
                        <input type="date" id="filter_date_to" class="form-control">
                    </div>

                    {{-- Buttons --}}
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button id="btn_apply_filter" class="btn btn-primary">
                            <i class="bi bi-funnel-fill me-1"></i>Apply
                        </button>
                        <button id="btn_reset_filter" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </button>
                    </div>

                </div>{{-- /.row --}}
            </div>{{-- /.card-body --}}
        </div>{{-- /.collapse --}}
    </div>{{-- /.card --}}

    {{-- ── DataTable Card ───────────────────────────────────────────────── --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <span class="fw-semibold">
                <i class="bi bi-table me-2 text-warning"></i>All Movement Records
            </span>
            <span class="badge bg-warning text-dark" id="totalBadge">0 records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="movementsTable" class="table table-hover table-striped align-middle mb-0" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th width="50">#</th>
                            <th>Movement No</th>
                            <th>Type</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Item Type</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Router IDs</th>
                            <th>Qty</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Ticket No</th>
                            <th>Condition</th>
                            <th>Reason</th>
                            <th>Date</th>
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

    // ── Select2 ───────────────────────────────────────────────────────────
    $('.select2-filter').select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        placeholder: function () {
            return $(this).find('option:first').text();
        }
    });

    // ── Collapse chevron ──────────────────────────────────────────────────
    $('#filterPanel').on('show.bs.collapse hide.bs.collapse', function () {
        $('#filterChevron').toggleClass('bi-chevron-down bi-chevron-up');
    });

    // ── Show/hide Accessory Type ──────────────────────────────────────────
    $('#filter_item_type').on('change', function () {
        if ($(this).val() === 'accessory') {
            $('#accessoryTypeWrapper').show();
        } else {
            $('#accessoryTypeWrapper').hide();
            $('#filter_accessory_type').val(null).trigger('change.select2');
        }
    });

    // ── DataTable ─────────────────────────────────────────────────────────
    var table = $('#movementsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.inventory.movements.datatable') }}',
            type: 'GET',
            data: function (d) {
                d.movement_type     = $('#filter_movement_type').val();
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
            { data: 'DT_RowIndex',   name: 'DT_RowIndex',  orderable: false, searchable: false },
            { data: 'movement_no',   name: 'movement_no' },
            { data: 'movement_type', name: 'movement_type', searchable: false, orderable: false },
            { data: 'item_code',     name: 'item_code',     searchable: false, orderable: false },
            { data: 'item_name',     name: 'item_name',     searchable: false, orderable: false },
            { data: 'item_type',     name: 'item_type',     searchable: false, orderable: false },
            { data: 'brand',         name: 'brand',         searchable: false, orderable: false },
            { data: 'model',         name: 'model',         searchable: false, orderable: false },
            { data: 'router_ids',    name: 'router_ids',    searchable: false, orderable: false },
            {
                data: 'quantity', name: 'quantity',
                searchable: false, orderable: false,
                render: function (val) {
                    var cls = val < 0 ? 'bg-danger' : 'bg-success';
                    return '<span class="badge ' + cls + '">' + val + '</span>';
                }
            },
            { data: 'from_location', name: 'from_location', searchable: false, orderable: false },
            { data: 'to_location',   name: 'to_location',   searchable: false, orderable: false },
            { data: 'ticket_no',     name: 'ticket_no',     searchable: false, orderable: false },
            { data: 'condition',     name: 'condition',     searchable: false, orderable: false },
            {
                data: 'reason', name: 'reason',
                searchable: false, orderable: false,
                render: function (val) {
                    if (!val || val === '-') return '-';
                    return val.length > 40
                        ? '<span title="' + val + '">' + val.substring(0, 40) + '…</span>'
                        : val;
                }
            },
            { data: 'movement_date', name: 'movement_date', searchable: false, orderable: false },
            { data: 'performed_by',  name: 'performed_by',  searchable: false, orderable: false },
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="text-center py-3"><div class="spinner-border text-warning" role="status"></div></div>',
            emptyTable: '<div class="text-center py-3 text-muted"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No movement records found.</div>',
            zeroRecords: '<div class="text-center py-3 text-muted"><i class="bi bi-search fs-2 d-block mb-2"></i>No records match your filter.</div>'
        },
        drawCallback: function (settings) {
            var info = this.api().page.info();
            $('#totalBadge').text(info.recordsTotal + ' records');
        }
    });

    // ── Apply / Reset ─────────────────────────────────────────────────────
    $('#btn_apply_filter').on('click', function () { table.draw(); });

    $('#btn_reset_filter').on('click', function () {
        $('#filter_movement_type, #filter_item_type, #filter_item_name, #filter_brand, #filter_model, #filter_accessory_type')
            .val(null).trigger('change.select2');
        $('#filter_date_from, #filter_date_to').val('');
        $('#accessoryTypeWrapper').hide();
        table.draw();
    });

    // ── Export button — append active filters to URL ──────────────────────
    $('#btnExport').on('click', function (e) {
        e.preventDefault();
        var base = '{{ route('admin.inventory.movements.export') }}';
        var params = {
            movement_type:     $('#filter_movement_type').val(),
            item_type:         $('#filter_item_type').val(),
            inventory_item_id: $('#filter_item_name').val(),
            brand:             $('#filter_brand').val(),
            model:             $('#filter_model').val(),
            accessory_type:    $('#filter_accessory_type').val(),
            date_from:         $('#filter_date_from').val(),
            date_to:           $('#filter_date_to').val(),
        };
        var query = Object.entries(params)
            .filter(function (p) { return p[1]; })
            .map(function (p) { return encodeURIComponent(p[0]) + '=' + encodeURIComponent(p[1]); })
            .join('&');
        window.location.href = base + (query ? '?' + query : '');
    });

});
</script>
@endpush
