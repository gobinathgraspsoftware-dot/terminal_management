@extends('layouts.app')
@section('title', 'Stock Return')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Stock Return</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Stock Return</li>
                </ol>
            </nav>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#returnFormSection">
            <i class="bi bi-plus-circle me-1"></i> Manual Stock Return
        </button>
    </div>

    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
        <i class="bi bi-info-circle-fill fs-5 me-3"></i>
        <div>
            <strong>Auto + Manual:</strong> Stock returns are auto-triggered on <strong>Replacement</strong> ticket creation.
            You can also process manual returns below.
        </div>
    </div>

    <!-- Manual Return Form -->
    <div class="collapse mb-4" id="returnFormSection">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-bold"><i class="bi bi-box-arrow-up me-1"></i> Manual Stock Return</div>
            <div class="card-body">
                <form action="{{ route('supervisor.inventory.stock-return.process') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="inventory_item_id" class="form-label">Item <span class="text-danger">*</span></label>
                            <select name="inventory_item_id" id="inventory_item_id" class="form-select select2 @error('inventory_item_id') is-invalid @enderror" required>
                                <option value="">-- Select Item --</option>
                                @foreach($allItems as $item)
                                <option value="{{ $item->id }}" {{ old('inventory_item_id') == $item->id ? 'selected' : '' }}>{{ $item->item_code }} - {{ $item->item_name }}</option>
                                @endforeach
                            </select>
                            @error('inventory_item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="stockreturn_date" class="form-label">Return Date <span class="text-danger">*</span></label>
                            <input type="date" name="stockreturn_date" id="stockreturn_date" class="form-control @error('stockreturn_date') is-invalid @enderror"
                                   value="{{ old('stockreturn_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                            @error('stockreturn_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror"
                                   value="{{ old('quantity', 1) }}" min="1" required>
                            @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12" id="router-ids-section">
                            <label class="form-label fw-bold">Router IDs <small class="text-muted">(one per quantity)</small></label>
                            <div id="router-ids-container"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="item_condition" class="form-label">Condition <span class="text-danger">*</span></label>
                            <select name="item_condition" id="item_condition" class="form-select" required>
                                <option value="good" {{ old('item_condition') === 'good' ? 'selected' : '' }}>Good</option>
                                <option value="faulty" {{ old('item_condition') === 'faulty' ? 'selected' : '' }}>Faulty</option>
                                <option value="damaged" {{ old('item_condition') === 'damaged' ? 'selected' : '' }}>Damaged</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                            <input type="text" name="reason" id="reason" class="form-control @error('reason') is-invalid @enderror"
                                   value="{{ old('reason', 'Manual Stock Return') }}" maxlength="500" required>
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="2" maxlength="1000">{{ old('remarks') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-success"><i class="bi bi-box-arrow-up me-1"></i> Process Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" id="filter-date-from" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" id="filter-date-to" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button id="btn-filter" class="btn btn-primary btn-sm me-2"><i class="bi bi-search me-1"></i>Filter</button>
                    <button id="btn-reset" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle me-1"></i>Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent fw-bold">Return History</div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="stockreturn-table" class="table table-hover table-sm align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr><th>#</th><th>Movement #</th><th>Item</th><th>Qty</th><th>Router IDs</th><th>Ticket</th><th>Condition</th><th>Reason</th><th>Return Date</th><th>By</th></tr>
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
$(function() {
    var table = $('#stockreturn-table').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: '{{ route("supervisor.inventory.stock-return.datatable") }}',
            data: function(d) { d.date_from = $('#filter-date-from').val(); d.date_to = $('#filter-date-to').val(); }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
            { data: 'movement_no' },
            { data: null, render: function(data) { return '<span class="fw-semibold">' + data.item_code + '</span><br><small class="text-muted">' + data.item_name + '</small>'; } },
            { data: 'quantity', className: 'text-center fw-bold text-success' },
            { data: 'router_ids', render: function(data) { return (!data || data === '-') ? '-' : '<small class="text-primary">' + data + '</small>'; } },
            { data: 'ticket_no', render: function(data) { return (!data || data === '-') ? 'Manual' : '<span class="badge bg-outline-info border">' + data + '</span>'; } },
            { data: 'condition', orderable: false },
            { data: 'reason' },
            { data: 'stockreturn_date' },
            { data: 'performed_by' }
        ],
        order: [[1, 'desc']], pageLength: 25, language: { search: '', searchPlaceholder: 'Search...' }
    });

    $('#btn-filter').on('click', function() { table.ajax.reload(); });
    $('#btn-reset').on('click', function() { $('#filter-date-from, #filter-date-to').val(''); table.ajax.reload(); });

    function updateRouterIdFields() {
        var qty = parseInt($('#quantity').val()) || 0;
        var $c = $('#router-ids-container'); $c.empty();
        for (var i = 0; i < qty; i++) {
            $c.append('<div class="row mb-2"><div class="col-md-6"><div class="input-group input-group-sm"><span class="input-group-text">Router ID #' + (i + 1) + '</span><input type="text" name="router_ids[]" class="form-control" placeholder="Enter Router ID" maxlength="100"></div></div></div>');
        }
    }
    $('#quantity').on('change input', updateRouterIdFields);
    updateRouterIdFields();

    @if($errors->any())
    new bootstrap.Collapse(document.getElementById('returnFormSection'), { show: true });
    @endif

    if ($.fn.select2) { $('#inventory_item_id').select2({ theme: 'bootstrap-5', placeholder: '-- Select --', allowClear: true }); }
});
</script>
@endpush
