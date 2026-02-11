<!-- Quotation Header -->
<div class="row mb-3">
    <div class="col-md-3">
        <label class="form-label">Quotation Type <span class="text-danger">*</span></label>
        <select name="quotation_type" id="quotation-type" class="form-select" required>
            <option value="">Select Type</option>
            <option value="customer" {{ old('quotation_type', $quotation->quotation_type ?? '') == 'customer' ? 'selected' : '' }}>Customer Quotation</option>
            <option value="vendor" {{ old('quotation_type', $quotation->quotation_type ?? '') == 'vendor' ? 'selected' : '' }}>Vendor Quotation</option>
        </select>
    </div>
    
    <div class="col-md-3">
        <label class="form-label">Quotation Date <span class="text-danger">*</span></label>
        <input type="date" name="quotation_date" class="form-control" 
               value="{{ old('quotation_date', $quotation->quotation_date ?? now()->format('Y-m-d')) }}" required>
    </div>
    
    <div class="col-md-3">
        <label class="form-label">Valid Until <span class="text-danger">*</span></label>
        <input type="date" name="valid_until" class="form-control" 
               value="{{ old('valid_until', $quotation->valid_until ?? now()->addDays(30)->format('Y-m-d')) }}" required>
    </div>
    
    <div class="col-md-3">
        <label class="form-label">Reference</label>
        <input type="text" name="reference" class="form-control" 
               value="{{ old('reference', $quotation->reference ?? '') }}" placeholder="External Reference">
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6" id="client-field" style="display: none;">
        <label class="form-label">Client <span class="text-danger">*</span></label>
        <select name="client_id" id="client-select" class="form-select select2">
            <option value="">Select Client</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ old('client_id', $quotation->client_id ?? '') == $client->id ? 'selected' : '' }}>
                    {{ $client->client_name }}
                </option>
            @endforeach
        </select>
    </div>
    
    <div class="col-md-6" id="vendor-field" style="display: none;">
        <label class="form-label">Vendor <span class="text-danger">*</span></label>
        <select name="vendor_id" id="vendor-select" class="form-select select2">
            <option value="">Select Vendor</option>
            @foreach($vendors as $vendor)
                <option value="{{ $vendor->id }}" {{ old('vendor_id', $quotation->vendor_id ?? '') == $vendor->id ? 'selected' : '' }}>
                    {{ $vendor->vendor_name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<!-- Line Items -->
<div class="row mb-3">
    <div class="col-md-12">
        <h5>Line Items</h5>
        <div class="table-responsive">
            <table class="table table-bordered" id="line-items-table">
                <thead class="table-light">
                    <tr>
                        <th width="10%">Item Type</th>
                        <th width="20%">Item</th>
                        <th width="25%">Description</th>
                        <th width="8%">Qty</th>
                        <th width="10%">Unit Price</th>
                        <th width="8%">Disc %</th>
                        <th width="8%">Tax %</th>
                        <th width="10%">Total</th>
                        <th width="5%">Action</th>
                    </tr>
                </thead>
                <tbody id="line-items-body">
                    @if(isset($quotation) && $quotation->lines->count() > 0)
                        @foreach($quotation->lines as $index => $line)
                        <tr data-index="{{ $index }}">
                            <td>
                                <select name="lines[{{ $index }}][item_type]" class="form-select form-select-sm item-type-select" required>
                                    <option value="model" {{ $line->item_type == 'model' ? 'selected' : '' }}>Model</option>
                                    <option value="charge" {{ $line->item_type == 'charge' ? 'selected' : '' }}>Charge</option>
                                    <option value="custom" {{ $line->item_type == 'custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                            </td>
                            <td>
                                <select name="lines[{{ $index }}][model_id]" class="form-select form-select-sm model-select" style="display: {{ $line->item_type == 'model' ? 'block' : 'none' }}">
                                    <option value="">Select Model</option>
                                    @foreach($models as $model)
                                        <option value="{{ $model->id }}" {{ $line->model_id == $model->id ? 'selected' : '' }}>{{ $model->model_name }}</option>
                                    @endforeach
                                </select>
                                <select name="lines[{{ $index }}][charge_id]" class="form-select form-select-sm charge-select" style="display: {{ $line->item_type == 'charge' ? 'block' : 'none' }}">
                                    <option value="">Select Charge</option>
                                    @foreach($charges as $charge)
                                        <option value="{{ $charge->id }}" {{ $line->charge_id == $charge->id ? 'selected' : '' }}>{{ $charge->charge_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="text" name="lines[{{ $index }}][description]" class="form-control form-control-sm description" value="{{ $line->description }}" required></td>
                            <td><input type="number" name="lines[{{ $index }}][quantity]" class="form-control form-control-sm quantity" value="{{ $line->quantity }}" step="0.01" min="0.01" required></td>
                            <td><input type="number" name="lines[{{ $index }}][unit_price]" class="form-control form-control-sm unit-price" value="{{ $line->unit_price }}" step="0.01" min="0" required></td>
                            <td><input type="number" name="lines[{{ $index }}][discount_percent]" class="form-control form-control-sm discount-percent" value="{{ $line->discount_percent }}" step="0.01" min="0" max="100"></td>
                            <td><input type="number" name="lines[{{ $index }}][tax_rate]" class="form-control form-control-sm tax-rate" value="{{ $line->tax_rate }}" step="0.01" min="0" max="100"></td>
                            <td><input type="text" class="form-control form-control-sm line-total" value="{{ number_format($line->line_total, 2) }}" readonly></td>
                            <td><button type="button" class="btn btn-sm btn-danger remove-line-btn"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn-success" id="add-line-btn">
            <i class="bi bi-plus-circle"></i> Add Line
        </button>
    </div>
</div>

<!-- Totals -->
<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label">Terms & Conditions</label>
        <textarea name="terms_conditions" class="form-control" rows="3">{{ old('terms_conditions', $quotation->terms_conditions ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
        <table class="table table-sm">
            <tr>
                <th>Subtotal:</th>
                <td class="text-end"><span id="display-subtotal">0.00</span></td>
            </tr>
            <tr>
                <th>Tax:</th>
                <td class="text-end"><span id="display-tax">0.00</span></td>
            </tr>
            <tr>
                <th>Discount:</th>
                <td class="text-end">
                    <input type="number" name="discount_amount" id="header-discount" class="form-control form-control-sm text-end" 
                           value="{{ old('discount_amount', $quotation->discount_amount ?? 0) }}" step="0.01" min="0">
                </td>
            </tr>
            <tr class="table-primary">
                <th>Total:</th>
                <th class="text-end"><strong id="display-total">0.00</strong></th>
            </tr>
        </table>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $quotation->notes ?? '') }}</textarea>
    </div>
</div>

@push('scripts')
<script>
let lineIndex = {{ isset($quotation) && $quotation->lines->count() > 0 ? $quotation->lines->count() : 0 }};

$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Show/hide client/vendor based on quotation type
    function togglePartyFields() {
        var type = $('#quotation-type').val();
        if (type === 'customer') {
            $('#client-field').show();
            $('#vendor-field').hide();
            $('#client-select').prop('required', true);
            $('#vendor-select').prop('required', false);
        } else if (type === 'vendor') {
            $('#client-field').hide();
            $('#vendor-field').show();
            $('#client-select').prop('required', false);
            $('#vendor-select').prop('required', true);
        } else {
            $('#client-field').hide();
            $('#vendor-field').hide();
        }
    }

    $('#quotation-type').change(togglePartyFields);
    togglePartyFields();

    // Add line item
    $('#add-line-btn').click(function() {
        addLineItem();
    });

    function addLineItem() {
        var row = `
            <tr data-index="${lineIndex}">
                <td>
                    <select name="lines[${lineIndex}][item_type]" class="form-select form-select-sm item-type-select" required>
                        <option value="model">Model</option>
                        <option value="charge">Charge</option>
                        <option value="custom">Custom</option>
                    </select>
                </td>
                <td>
                    <select name="lines[${lineIndex}][model_id]" class="form-select form-select-sm model-select">
                        <option value="">Select Model</option>
                        @foreach($models as $model)
                            <option value="{{ $model->id }}">{{ $model->model_name }}</option>
                        @endforeach
                    </select>
                    <select name="lines[${lineIndex}][charge_id]" class="form-select form-select-sm charge-select" style="display:none;">
                        <option value="">Select Charge</option>
                        @foreach($charges as $charge)
                            <option value="{{ $charge->id }}">{{ $charge->charge_name }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="text" name="lines[${lineIndex}][description]" class="form-control form-control-sm description" required></td>
                <td><input type="number" name="lines[${lineIndex}][quantity]" class="form-control form-control-sm quantity" value="1" step="0.01" min="0.01" required></td>
                <td><input type="number" name="lines[${lineIndex}][unit_price]" class="form-control form-control-sm unit-price" value="0" step="0.01" min="0" required></td>
                <td><input type="number" name="lines[${lineIndex}][discount_percent]" class="form-control form-control-sm discount-percent" value="0" step="0.01" min="0" max="100"></td>
                <td><input type="number" name="lines[${lineIndex}][tax_rate]" class="form-control form-control-sm tax-rate" value="6" step="0.01" min="0" max="100"></td>
                <td><input type="text" class="form-control form-control-sm line-total" value="0.00" readonly></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-line-btn"><i class="bi bi-trash"></i></button></td>
            </tr>
        `;
        $('#line-items-body').append(row);
        lineIndex++;
        calculateGrandTotal();
    }

    // Remove line item
    $(document).on('click', '.remove-line-btn', function() {
        $(this).closest('tr').remove();
        calculateGrandTotal();
    });

    // Item type change
    $(document).on('change', '.item-type-select', function() {
        var row = $(this).closest('tr');
        var type = $(this).val();
        
        row.find('.model-select').hide().prop('required', false);
        row.find('.charge-select').hide().prop('required', false);
        
        if (type === 'model') {
            row.find('.model-select').show().prop('required', true);
        } else if (type === 'charge') {
            row.find('.charge-select').show().prop('required', true);
        }
    });

    // Model/Charge selection - auto-fill price
    $(document).on('change', '.model-select', function() {
        var modelId = $(this).val();
        var row = $(this).closest('tr');
        
        if (modelId) {
            $.get('{{ route("admin.quotations.model-price", ":id") }}'.replace(':id', modelId), function(data) {
                if (data.success) {
                    row.find('.unit-price').val(data.price);
                    row.find('.description').val(data.description);
                    calculateLineTotal(row);
                }
            });
        }
    });

    $(document).on('change', '.charge-select', function() {
        var chargeId = $(this).val();
        var row = $(this).closest('tr');
        
        if (chargeId) {
            $.get('{{ route("admin.quotations.charge-price", ":id") }}'.replace(':id', chargeId), function(data) {
                if (data.success) {
                    row.find('.unit-price').val(data.price);
                    row.find('.description').val(data.description);
                    calculateLineTotal(row);
                }
            });
        }
    });

    // Calculate line total on input change
    $(document).on('input', '.quantity, .unit-price, .discount-percent, .tax-rate', function() {
        var row = $(this).closest('tr');
        calculateLineTotal(row);
    });

    $(document).on('input', '#header-discount', function() {
        calculateGrandTotal();
    });

    function calculateLineTotal(row) {
        var qty = parseFloat(row.find('.quantity').val()) || 0;
        var price = parseFloat(row.find('.unit-price').val()) || 0;
        var discountPercent = parseFloat(row.find('.discount-percent').val()) || 0;
        var taxRate = parseFloat(row.find('.tax-rate').val()) || 0;
        
        var subtotal = qty * price;
        var discount = subtotal * (discountPercent / 100);
        var afterDiscount = subtotal - discount;
        var lineTotal = afterDiscount; // Tax added at total level
        
        row.find('.line-total').val(lineTotal.toFixed(2));
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        var subtotal = 0;
        var tax = 0;
        
        $('#line-items-body tr').each(function() {
            var qty = parseFloat($(this).find('.quantity').val()) || 0;
            var price = parseFloat($(this).find('.unit-price').val()) || 0;
            var discountPercent = parseFloat($(this).find('.discount-percent').val()) || 0;
            var taxRate = parseFloat($(this).find('.tax-rate').val()) || 0;
            
            var lineSubtotal = qty * price;
            var lineDiscount = lineSubtotal * (discountPercent / 100);
            var afterDiscount = lineSubtotal - lineDiscount;
            var lineTax = afterDiscount * (taxRate / 100);
            
            subtotal += afterDiscount;
            tax += lineTax;
        });
        
        var headerDiscount = parseFloat($('#header-discount').val()) || 0;
        var total = subtotal + tax - headerDiscount;
        
        $('#display-subtotal').text(subtotal.toFixed(2));
        $('#display-tax').text(tax.toFixed(2));
        $('#display-total').text(total.toFixed(2));
    }

    // Initial calculation
    $('#line-items-body tr').each(function() {
        calculateLineTotal($(this));
    });
});
</script>
@endpush
