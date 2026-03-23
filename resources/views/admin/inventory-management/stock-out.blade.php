@extends('layouts.app')

@section('title', 'Stock Out - Inventory Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-arrow-up text-danger me-2"></i>Stock Out</h4>
            <p class="text-muted mb-0">Direct stock out — wastage, return to vendor, direct to site</p>
        </div>
        <a href="{{ route('admin.inventory-management.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Hub
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="stockOutForm">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Stock Out Date <span class="text-danger">*</span></label>
                        <input type="date" name="stock_out_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Out Type <span class="text-danger">*</span></label>
                        <select name="out_type" id="outType" class="form-select" required>
                            <option value="">Select Type</option>
                            @foreach(\App\Models\StockOut::OUT_TYPE_OPTIONS as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">From Depot <span class="text-danger">*</span></label>
                        <select name="from_depot_id" class="form-select select2-depot" required>
                            <option value="">Select Depot</option>
                            @foreach($depots as $depot)
                            <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-none" id="toSiteGroup">
                        <label class="form-label fw-semibold">Destination Site</label>
                        <select name="to_site_id" class="form-select select2-site">
                            <option value="">Select Site</option>
                            @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-none" id="toVendorGroup">
                        <label class="form-label fw-semibold">Destination Vendor</label>
                        <select name="to_vendor_id" class="form-select select2-vendor">
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->vendor_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Reason for stock out..."></textarea>
                    </div>
                </div>

                <hr>
                <h6 class="fw-semibold mb-3">Line Items</h6>
                <div class="table-responsive">
                    <table class="table table-sm" id="outLinesTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:30px">#</th>
                                <th>Model <span class="text-danger">*</span></th>
                                <th>Serial</th>
                                <th style="width:100px">Qty <span class="text-danger">*</span></th>
                                <th style="width:120px">Condition</th>
                                <th style="width:150px">Remarks</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="outLinesBody">
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
                                <td>
                                    <select name="lines[0][serial_id]" class="form-select form-select-sm serial-select">
                                        <option value="">N/A</option>
                                    </select>
                                    <input type="hidden" name="lines[0][serial_no]" class="serial-no-hidden">
                                </td>
                                <td><input type="number" name="lines[0][quantity]" class="form-control form-control-sm" value="1" min="0.0001" step="1" required></td>
                                <td>
                                    <select name="lines[0][condition]" class="form-select form-select-sm">
                                        <option value="good">Good</option>
                                        <option value="damaged">Damaged</option>
                                        <option value="defective">Defective</option>
                                    </select>
                                </td>
                                <td><input type="text" name="lines[0][remarks]" class="form-control form-control-sm"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-line" disabled><i class="bi bi-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addOutLineBtn">
                    <i class="bi bi-plus-circle me-1"></i> Add Line
                </button>

                <hr class="mt-4">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="saveOutBtn">
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

    function initSelect2() {
        $('.select2-depot, .select2-site, .select2-vendor, .select2-model').select2({ theme: 'bootstrap-5', width: '100%' });
    }
    initSelect2();

    // Show/hide destination fields based on out type
    $('#outType').on('change', function() {
        let type = $(this).val();
        $('#toSiteGroup').toggleClass('d-none', type !== 'direct_to_site');
        $('#toVendorGroup').toggleClass('d-none', type !== 'return_to_vendor');
    });

    // Load serials when model + depot selected
    $(document).on('change', '.select2-model', function() {
        let row = $(this).closest('tr');
        let modelId = $(this).val();
        let depotId = $('[name="from_depot_id"]').val();
        let isSerial = $(this).find(':selected').data('serial');

        if (isSerial && modelId && depotId) {
            $.get('{{ route("admin.inventory-management.available-serials") }}', {
                model_id: modelId, depot_id: depotId
            }, function(res) {
                let opts = '<option value="">Select Serial</option>';
                res.serials.forEach(s => opts += `<option value="${s.id}" data-sn="${s.serial_no}">${s.serial_no}</option>`);
                row.find('.serial-select').html(opts).prop('disabled', false);
                row.find('[name*="quantity"]').val(1).prop('readonly', true);
            });
        } else {
            row.find('.serial-select').html('<option value="">N/A</option>');
            row.find('[name*="quantity"]').prop('readonly', false);
        }
    });

    $(document).on('change', '.serial-select', function() {
        let sn = $(this).find(':selected').data('sn') || '';
        $(this).closest('tr').find('.serial-no-hidden').val(sn);
    });

    // Add line
    $('#addOutLineBtn').on('click', function() {
        let html = `<tr class="line-row" data-index="${lineIndex}">
            <td class="row-num">${lineIndex + 1}</td>
            <td>
                <select name="lines[${lineIndex}][model_id]" class="form-select form-select-sm select2-model" required>
                    <option value="">Select Model</option>
                    @foreach($models as $model)
                    <option value="{{ $model->id }}" data-serial="{{ $model->is_serial_tracked ? 1 : 0 }}">{{ $model->model_name }} ({{ $model->category->category_name ?? '' }})</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="lines[${lineIndex}][serial_id]" class="form-select form-select-sm serial-select"><option value="">N/A</option></select>
                <input type="hidden" name="lines[${lineIndex}][serial_no]" class="serial-no-hidden">
            </td>
            <td><input type="number" name="lines[${lineIndex}][quantity]" class="form-control form-control-sm" value="1" min="0.0001" step="1" required></td>
            <td>
                <select name="lines[${lineIndex}][condition]" class="form-select form-select-sm">
                    <option value="good">Good</option><option value="damaged">Damaged</option><option value="defective">Defective</option>
                </select>
            </td>
            <td><input type="text" name="lines[${lineIndex}][remarks]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
        </tr>`;
        $('#outLinesBody').append(html);
        initSelect2();
        lineIndex++;
        updateRemoveButtons();
    });

    $(document).on('click', '.remove-line', function() { $(this).closest('tr').remove(); updateRemoveButtons(); });

    function updateRemoveButtons() {
        let rows = $('#outLinesBody .line-row');
        rows.find('.remove-line').prop('disabled', rows.length <= 1);
        rows.each(function(i) { $(this).find('.row-num').text(i + 1); });
    }

    // Submit
    $('#stockOutForm').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        $('#saveOutBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url: '{{ route("admin.inventory-management.stock-out.store") }}',
            method: 'POST', data: formData, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(() => window.location.href = '{{ route("admin.inventory-management.index") }}', 1500);
                }
            },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error', 'error'); },
            complete: function() { $('#saveOutBtn').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Save as Draft'); }
        });
    });
});
</script>
@endpush
