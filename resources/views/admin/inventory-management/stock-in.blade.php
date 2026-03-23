@extends('layouts.app')

@section('title', 'Stock In - Inventory Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-arrow-in-down text-success me-2"></i>Stock In</h4>
            <p class="text-muted mb-0">Receive items into inventory (non-GRN manual entry)</p>
        </div>
        <a href="{{ route('admin.inventory-management.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Hub
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="stockInForm">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Stock In Date <span class="text-danger">*</span></label>
                        <input type="date" name="stock_in_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Receiving Depot <span class="text-danger">*</span></label>
                        <select name="depot_id" class="form-select select2-depot" required>
                            <option value="">Select Depot</option>
                            @foreach($depots as $depot)
                            <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Source Type <span class="text-danger">*</span></label>
                        <select name="source_type" class="form-select" required>
                            @foreach(\App\Models\StockIn::SOURCE_OPTIONS as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Source Reference</label>
                        <input type="text" name="source_reference" class="form-control" placeholder="e.g. delivery note">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>

                <hr>
                <h6 class="fw-semibold mb-3">Line Items</h6>
                <div class="table-responsive">
                    <table class="table table-sm" id="linesTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:30px">#</th>
                                <th>Model <span class="text-danger">*</span></th>
                                <th>Serial No</th>
                                <th style="width:100px">Qty <span class="text-danger">*</span></th>
                                <th style="width:120px">Unit Cost</th>
                                <th style="width:120px">Condition</th>
                                <th style="width:150px">Remarks</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="linesBody">
                            <tr class="line-row" data-index="0">
                                <td class="row-num">1</td>
                                <td>
                                    <select name="lines[0][model_id]" class="form-select form-select-sm select2-model" required>
                                        <option value="">Select Model</option>
                                        @foreach($models as $model)
                                        <option value="{{ $model->id }}" data-serial="{{ $model->is_serial_tracked ? 1 : 0 }}">
                                            {{ $model->model_name }} ({{ $model->category->category_name ?? '' }})
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="lines[0][serial_no]" class="form-control form-control-sm serial-field" placeholder="Serial No"></td>
                                <td><input type="number" name="lines[0][quantity]" class="form-control form-control-sm" value="1" min="0.0001" step="1" required></td>
                                <td><input type="number" name="lines[0][unit_cost]" class="form-control form-control-sm" step="0.01" placeholder="0.00"></td>
                                <td>
                                    <select name="lines[0][condition]" class="form-select form-select-sm">
                                        <option value="new">New</option>
                                        <option value="good">Good</option>
                                        <option value="damaged">Damaged</option>
                                        <option value="defective">Defective</option>
                                    </select>
                                </td>
                                <td><input type="text" name="lines[0][remarks]" class="form-control form-control-sm" placeholder=""></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-line" disabled><i class="bi bi-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addLineBtn">
                    <i class="bi bi-plus-circle me-1"></i> Add Line
                </button>

                <hr class="mt-4">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn btn-success" id="saveBtn">
                        <i class="bi bi-check-circle me-1"></i> Save as Draft
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
    let lineIndex = 1;

    // Initialize Select2
    function initSelect2() {
        $('.select2-depot').select2({ theme: 'bootstrap-5', width: '100%' });
        $('.select2-model').select2({ theme: 'bootstrap-5', width: '100%' });
    }
    initSelect2();

    // Toggle serial field based on model selection
    $(document).on('change', '.select2-model', function() {
        let row = $(this).closest('tr');
        let isSerial = $(this).find(':selected').data('serial');
        let serialField = row.find('.serial-field');
        if (isSerial) {
            serialField.prop('required', true).prop('disabled', false).attr('placeholder', 'Required');
            row.find('[name*="quantity"]').val(1).prop('readonly', true);
        } else {
            serialField.prop('required', false).prop('disabled', false).attr('placeholder', 'Optional');
            row.find('[name*="quantity"]').prop('readonly', false);
        }
    });

    // Add line
    $('#addLineBtn').on('click', function() {
        let html = `<tr class="line-row" data-index="${lineIndex}">
            <td class="row-num">${lineIndex + 1}</td>
            <td>
                <select name="lines[${lineIndex}][model_id]" class="form-select form-select-sm select2-model" required>
                    <option value="">Select Model</option>
                    @foreach($models as $model)
                    <option value="{{ $model->id }}" data-serial="{{ $model->is_serial_tracked ? 1 : 0 }}">
                        {{ $model->model_name }} ({{ $model->category->category_name ?? '' }})
                    </option>
                    @endforeach
                </select>
            </td>
            <td><input type="text" name="lines[${lineIndex}][serial_no]" class="form-control form-control-sm serial-field" placeholder="Serial No"></td>
            <td><input type="number" name="lines[${lineIndex}][quantity]" class="form-control form-control-sm" value="1" min="0.0001" step="1" required></td>
            <td><input type="number" name="lines[${lineIndex}][unit_cost]" class="form-control form-control-sm" step="0.01" placeholder="0.00"></td>
            <td>
                <select name="lines[${lineIndex}][condition]" class="form-select form-select-sm">
                    <option value="new">New</option>
                    <option value="good">Good</option>
                    <option value="damaged">Damaged</option>
                    <option value="defective">Defective</option>
                </select>
            </td>
            <td><input type="text" name="lines[${lineIndex}][remarks]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
        </tr>`;
        $('#linesBody').append(html);
        initSelect2();
        updateRemoveButtons();
        lineIndex++;
    });

    // Remove line
    $(document).on('click', '.remove-line', function() {
        $(this).closest('tr').remove();
        updateRowNumbers();
        updateRemoveButtons();
    });

    function updateRowNumbers() {
        $('#linesBody .line-row').each(function(i) {
            $(this).find('.row-num').text(i + 1);
        });
    }

    function updateRemoveButtons() {
        let rows = $('#linesBody .line-row');
        rows.find('.remove-line').prop('disabled', rows.length <= 1);
    }

    // Submit form
    $('#stockInForm').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        $('#saveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url: '{{ route("admin.inventory-management.stock-in.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(() => window.location.href = '{{ route("admin.inventory-management.index") }}', 1500);
                }
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || 'An error occurred.';
                showToast(msg, 'error');
            },
            complete: function() {
                $('#saveBtn').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Save as Draft');
            }
        });
    });
});
</script>
@endpush
