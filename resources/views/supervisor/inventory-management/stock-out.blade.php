@extends('layouts.app')

@section('title', 'Stock Out - Inventory Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-arrow-up text-danger me-2"></i>Stock Out</h4>
            <p class="text-muted mb-0">Direct stock out for your team</p>
        </div>
        <a href="{{ route('supervisor.inventory-management.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="stockOutForm">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="stock_out_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Out Type <span class="text-danger">*</span></label>
                        <select name="out_type" id="outType" class="form-select" required>
                            <option value="">Select</option>
                            @foreach(\App\Models\StockOut::OUT_TYPE_OPTIONS as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">From Depot</label>
                        <select name="from_depot_id" class="form-select">
                            <option value="">Select Depot</option>
                            @foreach($depots as $depot)
                            <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">From Technician</label>
                        <select name="from_technician_id" class="form-select">
                            <option value="">Select</option>
                            @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <hr>
                <h6 class="fw-semibold mb-3">Line Items</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>#</th><th>Model <span class="text-danger">*</span></th>
                                <th style="width:100px">Qty <span class="text-danger">*</span></th>
                                <th style="width:120px">Condition</th><th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="outLinesBody">
                            <tr class="line-row" data-index="0">
                                <td class="row-num">1</td>
                                <td>
                                    <select name="lines[0][model_id]" class="form-select form-select-sm" required>
                                        <option value="">Select</option>
                                        @foreach($models as $model)
                                        <option value="{{ $model->id }}">{{ $model->model_name }} ({{ $model->category->category_name ?? '' }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="lines[0][quantity]" class="form-control form-control-sm" value="1" min="1" required></td>
                                <td>
                                    <select name="lines[0][condition]" class="form-select form-select-sm">
                                        <option value="good">Good</option><option value="damaged">Damaged</option><option value="defective">Defective</option>
                                    </select>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-line" disabled><i class="bi bi-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addLineBtn"><i class="bi bi-plus-circle me-1"></i> Add Line</button>

                <hr class="mt-4">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="saveBtn"><i class="bi bi-check-circle me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let lineIdx = 1;

    $('#addLineBtn').on('click', function() {
        let html = `<tr class="line-row" data-index="${lineIdx}">
            <td class="row-num">${lineIdx + 1}</td>
            <td><select name="lines[${lineIdx}][model_id]" class="form-select form-select-sm" required>
                <option value="">Select</option>
                @foreach($models as $model)<option value="{{ $model->id }}">{{ $model->model_name }} ({{ $model->category->category_name ?? '' }})</option>@endforeach
            </select></td>
            <td><input type="number" name="lines[${lineIdx}][quantity]" class="form-control form-control-sm" value="1" min="1" required></td>
            <td><select name="lines[${lineIdx}][condition]" class="form-select form-select-sm">
                <option value="good">Good</option><option value="damaged">Damaged</option><option value="defective">Defective</option>
            </select></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
        </tr>`;
        $('#outLinesBody').append(html);
        lineIdx++;
        $('#outLinesBody .remove-line').prop('disabled', $('#outLinesBody .line-row').length <= 1);
    });

    $(document).on('click', '.remove-line', function() {
        $(this).closest('tr').remove();
        $('#outLinesBody .line-row').each(function(i) { $(this).find('.row-num').text(i + 1); });
        $('#outLinesBody .remove-line').prop('disabled', $('#outLinesBody .line-row').length <= 1);
    });

    $('#stockOutForm').on('submit', function(e) {
        e.preventDefault();
        $('#saveBtn').prop('disabled', true);
        $.ajax({
            url: '{{ route("supervisor.inventory-management.stock-out.store") }}',
            method: 'POST', data: new FormData(this), processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) { if (res.success) { showToast(res.message, 'success'); setTimeout(() => window.location.href = '{{ route("supervisor.inventory-management.index") }}', 1500); } },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error', 'error'); },
            complete: function() { $('#saveBtn').prop('disabled', false); }
        });
    });
});
</script>
@endpush
