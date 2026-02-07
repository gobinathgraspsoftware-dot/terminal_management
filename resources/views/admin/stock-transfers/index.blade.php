@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-arrow-left-right"></i> Stock Transfers</h2>
        @can('create_stock_transfers')
        <a href="{{ route('admin.stock-transfers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Transfer
        </a>
        @endcan
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Depot</label>
                        <select name="from_depot_id" class="form-select">
                            <option value="">All Depots</option>
                            @foreach($depots as $depot)
                            <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Depot</label>
                        <select name="to_depot_id" class="form-select">
                            <option value="">All Depots</option>
                            @foreach($depots as $depot)
                            <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            @foreach($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="button" id="btnFilter" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <button type="button" id="btnReset" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </button>
                        <button type="button" id="btnExport" class="btn btn-success">
                            <i class="bi bi-file-excel"></i> Export
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="transfersTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Transfer No</th>
                            <th>Date</th>
                            <th>From Depot</th>
                            <th>To Depot</th>
                            <th>Total Items</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    const table = $('#transfersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.stock-transfers.index") }}',
            data: function(d) {
                d.from_depot_id = $('select[name="from_depot_id"]').val();
                d.to_depot_id = $('select[name="to_depot_id"]').val();
                d.status = $('select[name="status"]').val();
                d.date_from = $('input[name="date_from"]').val();
                d.date_to = $('input[name="date_to"]').val();
            }
        },
        columns: [
            {data: 'transfer_no', name: 'transfer_no'},
            {data: 'transfer_date', name: 'transfer_date'},
            {data: 'from_depot_name', name: 'fromDepot.depot_name'},
            {data: 'to_depot_name', name: 'toDepot.depot_name'},
            {data: 'total_items_display', name: 'total_items', searchable: false},
            {data: 'status_badge', name: 'status'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[1, 'desc']]
    });

    $('#btnFilter').click(function() {
        table.draw();
    });

    $('#btnReset').click(function() {
        $('#filterForm')[0].reset();
        table.draw();
    });

    $('#btnExport').click(function() {
        const params = new URLSearchParams($('#filterForm').serialize());
        window.location.href = '{{ route("admin.stock-transfers.export") }}?' + params.toString();
    });
});
</script>
@endpush
@endsection
