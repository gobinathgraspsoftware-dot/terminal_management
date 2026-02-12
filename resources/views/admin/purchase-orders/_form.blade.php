<div class="row">
    <!-- Left Column: Vendor & Basic Info -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-building"></i> Vendor Information
            </div>
            <div class="card-body">
                <!-- Vendor -->
                <div class="mb-3">
                    <label for="vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                    <select name="vendor_id" id="vendor_id" class="form-select select2" required 
                            {{ isset($purchaseOrder) && $purchaseOrder->status !== 'draft' ? 'disabled' : '' }}>
                        <option value="">Select Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" 
                                    {{ (old('vendor_id') ?? $purchaseOrder->vendor_id ?? $quotation->vendor_id ?? '') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->vendor_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('vendor_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- PO Date -->
                <div class="mb-3">
                    <label for="po_date" class="form-label">PO Date <span class="text-danger">*</span></label>
                    <input type="date" name="po_date" id="po_date" class="form-control" 
                           value="{{ old('po_date') ?? ($purchaseOrder->po_date ?? now())->format('Y-m-d') }}" required>
                    @error('po_date')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Reference -->
                <div class="mb-3">
                    <label for="reference" class="form-label">Reference / Quotation No</label>
                    <input type="text" name="reference" id="reference" class="form-control" 
                           value="{{ old('reference') ?? $purchaseOrder->reference ?? $quotation->quotation_no ?? '' }}"
                           placeholder="Enter reference number">
                    @error('reference')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Currency -->
                <div class="mb-3">
                    <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                    <select name="currency" id="currency" class="form-select" required>
                        <option value="MYR" {{ (old('currency') ?? $purchaseOrder->currency ?? 'MYR') == 'MYR' ? 'selected' : '' }}>MYR - Malaysian Ringgit</option>
                        <option value="USD" {{ (old('currency') ?? $purchaseOrder->currency ?? '') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                        <option value="SGD" {{ (old('currency') ?? $purchaseOrder->currency ?? '') == 'SGD' ? 'selected' : '' }}>SGD - Singapore Dollar</option>
                        <option value="EUR" {{ (old('currency') ?? $purchaseOrder->currency ?? '') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                    </select>
                    @error('currency')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Delivery Info -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-truck"></i> Delivery Information
            </div>
            <div class="card-body">
                <!-- Delivery Address -->
                <div class="mb-3">
                    <label for="delivery_address" class="form-label">Delivery Address <span class="text-danger">*</span></label>
                    <textarea name="delivery_address" id="delivery_address" class="form-control" rows="3" required
                              placeholder="Enter complete delivery address">{{ old('delivery_address') ?? $purchaseOrder->delivery_address ?? '' }}</textarea>
                    @error('delivery_address')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Delivery Date -->
                <div class="mb-3">
                    <label for="delivery_date" class="form-label">Delivery Date <span class="text-danger">*</span></label>
                    <input type="date" name="delivery_date" id="delivery_date" class="form-control" 
                           value="{{ old('delivery_date') ?? ($purchaseOrder->delivery_date ?? now()->addDays(7))->format('Y-m-d') }}" required>
                    @error('delivery_date')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Receiving Depot -->
                <div class="mb-3">
                    <label for="receiving_depot_id" class="form-label">Receiving Depot <span class="text-danger">*</span></label>
                    <select name="receiving_depot_id" id="receiving_depot_id" class="form-select select2" required>
                        <option value="">Select Depot</option>
                        @foreach($depots as $depot)
                            <option value="{{ $depot->id }}" 
                                    {{ (old('receiving_depot_id') ?? $purchaseOrder->receiving_depot_id ?? '') == $depot->id ? 'selected' : '' }}>
                                {{ $depot->depot_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('receiving_depot_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Line Items Section -->
<div class="card mb-3">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-check"></i> Line Items</span>
        <button type="button" class="btn btn-sm btn-light" onclick="addNewLine()">
            <i class="bi bi-plus-circle"></i> Add Line Item
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="lines-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 25%;">Terminal Model <span class="text-danger">*</span></th>
                        <th style="width: 20%;">Description</th>
                        <th style="width: 8%;">Qty <span class="text-danger">*</span></th>
                        <th style="width: 8%;">Unit</th>
                        <th style="width: 10%;">Unit Price <span class="text-danger">*</span></th>
                        <th style="width: 8%;">Disc %</th>
                        <th style="width: 8%;">Tax %</th>
                        <th style="width: 10%;">Line Total</th>
                        <th style="width: 3%;"></th>
                    </tr>
                </thead>
                <tbody id="lines-container">
                    @if(isset($purchaseOrder) && $purchaseOrder->lines->count() > 0)
                        @foreach($purchaseOrder->lines as $line)
                            <tr>
                                <td>
                                    <select name="lines[model_id][]" class="form-select form-select-sm model-select" required>
                                        <option value="">Select</option>
                                        @foreach($models as $model)
                                            <option value="{{ $model->id }}" 
                                                    data-price="{{ $model->selling_price ?? 0 }}"
                                                    {{ $line->model_id == $model->id ? 'selected' : '' }}>
                                                {{ $model->model_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="lines[description][]" class="form-control form-control-sm" value="{{ $line->description }}"></td>
                                <td><input type="number" name="lines[quantity][]" class="form-control form-control-sm qty-input" min="1" step="1" value="{{ $line->quantity_ordered }}" required></td>
                                <td>
                                    <select name="lines[unit][]" class="form-select form-select-sm">
                                        <option value="pcs" {{ $line->unit == 'pcs' ? 'selected' : '' }}>Pcs</option>
                                        <option value="unit" {{ $line->unit == 'unit' ? 'selected' : '' }}>Unit</option>
                                        <option value="box" {{ $line->unit == 'box' ? 'selected' : '' }}>Box</option>
                                    </select>
                                </td>
                                <td><input type="number" name="lines[unit_price][]" class="form-control form-control-sm price-input" min="0" step="0.01" value="{{ $line->unit_price }}" required></td>
                                <td><input type="number" name="lines[discount_percent][]" class="form-control form-control-sm discount-input" min="0" max="100" step="0.01" value="{{ $line->discount_percent ?? 0 }}"></td>
                                <td><input type="number" name="lines[tax_rate][]" class="form-control form-control-sm tax-input" min="0" max="100" step="0.01" value="{{ $line->tax_rate ?? 0 }}"></td>
                                <td><input type="number" name="lines[line_total][]" class="form-control form-control-sm line-total" readonly value="{{ $line->line_total }}"></td>
                                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)"><i class="bi bi-trash"></i></button></td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div id="lines-empty-message" class="text-center text-muted py-4" style="{{ isset($purchaseOrder) && $purchaseOrder->lines->count() > 0 ? 'display:none;' : '' }}">
            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
            <p class="mt-2">No line items added yet. Click "Add Line Item" to start.</p>
        </div>
    </div>
</div>

<!-- Totals Section -->
<div class="row">
    <div class="col-md-6">
        <!-- Payment Terms & Notes -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-file-text"></i> Additional Information
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="payment_terms" class="form-label">Payment Terms</label>
                    <textarea name="payment_terms" id="payment_terms" class="form-control" rows="2"
                              placeholder="e.g., Net 30 days, 50% advance payment">{{ old('payment_terms') ?? $purchaseOrder->payment_terms ?? 'Net 30 days' }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                    <textarea name="terms_conditions" id="terms_conditions" class="form-control" rows="3"
                              placeholder="Enter terms and conditions">{{ old('terms_conditions') ?? $purchaseOrder->terms_conditions ?? '' }}</textarea>
                </div>

                <div class="mb-0">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2"
                              placeholder="Internal notes">{{ old('notes') ?? $purchaseOrder->notes ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Summary Totals -->
        <div class="card mb-3">
            <div class="card-header bg-dark text-white">
                <i class="bi bi-calculator"></i> Order Summary
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-end fw-bold">Subtotal:</td>
                        <td class="text-end" style="width: 150px;">
                            <span id="currency-symbol">MYR</span> <span id="subtotal-display">0.00</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-end fw-bold">Tax Amount:</td>
                        <td class="text-end">
                            <span id="currency-symbol-2">MYR</span> <span id="tax-display">0.00</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-end fw-bold">Discount:</td>
                        <td class="text-end">
                            <span id="currency-symbol-3">MYR</span> <span id="discount-display">0.00</span>
                        </td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-end fw-bold fs-5">Grand Total:</td>
                        <td class="text-end fw-bold fs-5 text-primary">
                            <span id="currency-symbol-4">MYR</span> <span id="total-display">0.00</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="card">
            <div class="card-body">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-{{ $submitIcon ?? 'check-circle' }}"></i> {{ $submitText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Global variables
let lineCounter = 0;
const models = @json($models);

// Add new line
function addNewLine() {
    const row = $('<tr>');
    row.html(`
        <td>
            <select name="lines[model_id][]" class="form-select form-select-sm model-select" required>
                <option value="">Select Model</option>
                ${models.map(m => `<option value="${m.id}" data-price="${m.selling_price || 0}">${m.model_name}</option>`).join('')}
            </select>
        </td>
        <td><input type="text" name="lines[description][]" class="form-control form-control-sm"></td>
        <td><input type="number" name="lines[quantity][]" class="form-control form-control-sm qty-input" min="1" step="1" value="1" required></td>
        <td>
            <select name="lines[unit][]" class="form-select form-select-sm">
                <option value="pcs">Pcs</option>
                <option value="unit">Unit</option>
                <option value="box">Box</option>
            </select>
        </td>
        <td><input type="number" name="lines[unit_price][]" class="form-control form-control-sm price-input" min="0" step="0.01" value="0" required></td>
        <td><input type="number" name="lines[discount_percent][]" class="form-control form-control-sm discount-input" min="0" max="100" step="0.01" value="0"></td>
        <td><input type="number" name="lines[tax_rate][]" class="form-control form-control-sm tax-input" min="0" max="100" step="0.01" value="0"></td>
        <td><input type="number" name="lines[line_total][]" class="form-control form-control-sm line-total" readonly value="0.00"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)"><i class="bi bi-trash"></i></button></td>
    `);
    
    $('#lines-container').append(row);
    $('#lines-empty-message').hide();
    attachLineCalculations(row);
    lineCounter++;
}

// Add line from quotation (for create from quotation)
function addLineFromQuotation(data) {
    const row = $('<tr>');
    row.html(`
        <td>
            <select name="lines[model_id][]" class="form-select form-select-sm model-select" required>
                ${models.map(m => `<option value="${m.id}" data-price="${m.selling_price || 0}" ${m.id == data.model_id ? 'selected' : ''}>${m.model_name}</option>`).join('')}
            </select>
        </td>
        <td><input type="text" name="lines[description][]" class="form-control form-control-sm" value="${data.description || ''}"></td>
        <td><input type="number" name="lines[quantity][]" class="form-control form-control-sm qty-input" min="1" step="1" value="${data.quantity}" required></td>
        <td>
            <select name="lines[unit][]" class="form-select form-select-sm">
                <option value="pcs">Pcs</option>
                <option value="unit">Unit</option>
                <option value="box">Box</option>
            </select>
        </td>
        <td><input type="number" name="lines[unit_price][]" class="form-control form-control-sm price-input" min="0" step="0.01" value="${data.unit_price}" required></td>
        <td><input type="number" name="lines[discount_percent][]" class="form-control form-control-sm discount-input" min="0" max="100" step="0.01" value="${data.discount_percent || 0}"></td>
        <td><input type="number" name="lines[tax_rate][]" class="form-control form-control-sm tax-input" min="0" max="100" step="0.01" value="${data.tax_rate || 0}"></td>
        <td><input type="number" name="lines[line_total][]" class="form-control form-control-sm line-total" readonly value="0.00"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)"><i class="bi bi-trash"></i></button></td>
    `);
    
    $('#lines-container').append(row);
    $('#lines-empty-message').hide();
    attachLineCalculations(row);
    calculateLineTotal(row);
}

// Remove line
function removeLine(btn) {
    $(btn).closest('tr').remove();
    if ($('#lines-container tr').length === 0) {
        $('#lines-empty-message').show();
    }
    calculateAllTotals();
}

// Attach calculation events to a row
function attachLineCalculations(row) {
    row.find('.model-select').on('change', function() {
        const price = $(this).find(':selected').data('price');
        row.find('.price-input').val(price);
        calculateLineTotal(row);
    });

    row.find('.qty-input, .price-input, .discount-input, .tax-input').on('input', function() {
        calculateLineTotal(row);
    });
}

// Calculate single line total
function calculateLineTotal(row) {
    const qty = parseFloat(row.find('.qty-input').val()) || 0;
    const price = parseFloat(row.find('.price-input').val()) || 0;
    const discountPercent = parseFloat(row.find('.discount-input').val()) || 0;
    const taxRate = parseFloat(row.find('.tax-input').val()) || 0;

    const subtotal = qty * price;
    const discountAmount = subtotal * (discountPercent / 100);
    const afterDiscount = subtotal - discountAmount;
    const taxAmount = afterDiscount * (taxRate / 100);
    const lineTotal = afterDiscount + taxAmount;

    row.find('.line-total').val(lineTotal.toFixed(2));
    calculateAllTotals();
}

// Calculate all totals
function calculateAllTotals() {
    let subtotal = 0;
    let totalDiscount = 0;
    let totalTax = 0;

    $('#lines-container tr').each(function() {
        const qty = parseFloat($(this).find('.qty-input').val()) || 0;
        const price = parseFloat($(this).find('.price-input').val()) || 0;
        const discountPercent = parseFloat($(this).find('.discount-input').val()) || 0;
        const taxRate = parseFloat($(this).find('.tax-input').val()) || 0;

        const lineSubtotal = qty * price;
        const lineDiscount = lineSubtotal * (discountPercent / 100);
        const afterDiscount = lineSubtotal - lineDiscount;
        const lineTax = afterDiscount * (taxRate / 100);

        subtotal += lineSubtotal;
        totalDiscount += lineDiscount;
        totalTax += lineTax;
    });

    const grandTotal = subtotal - totalDiscount + totalTax;

    $('#subtotal-display').text(subtotal.toFixed(2));
    $('#discount-display').text(totalDiscount.toFixed(2));
    $('#tax-display').text(totalTax.toFixed(2));
    $('#total-display').text(grandTotal.toFixed(2));
}

// Currency change
$('#currency').on('change', function() {
    const currency = $(this).val();
    $('#currency-symbol, #currency-symbol-2, #currency-symbol-3, #currency-symbol-4').text(currency);
});

// Initialize on page load
$(document).ready(function() {
    // Attach events to existing rows
    $('#lines-container tr').each(function() {
        attachLineCalculations($(this));
    });

    // Calculate initial totals
    calculateAllTotals();

    // Set currency symbols
    const currency = $('#currency').val();
    $('#currency-symbol, #currency-symbol-2, #currency-symbol-3, #currency-symbol-4').text(currency);
});
</script>
@endpush
