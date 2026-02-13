@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Edit GRN - {{ $grn->grn_no }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.grns.index') }}">GRNs</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.grns.show', $grn) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Note:</strong> You are editing an existing GRN. The Purchase Order cannot be changed.
    </div>

    <form id="grnForm" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <!-- PO Information (Read-only) -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-cart-check me-2"></i>Purchase Order (Read-Only)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="fw-bold">PO Number:</label>
                                <div>{{ $grn->purchaseOrder->po_no }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold">Vendor:</label>
                                <div>{{ $grn->vendor->name }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GRN Lines -->
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Edit Received Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="linesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="25%">Item</th>
                                        <th width="10%">Ordered</th>
                                        <th width="10%">Prev Received</th>
                                        <th width="10%">Outstanding</th>
                                        <th width="12%">Receive Qty</th>
                                        <th width="10%">Unit</th>
                                        <th width="13%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grn->purchaseOrder->lines as $index => $poLine)
                                        @php
                                            $grnLine = $grn->lines->where('po_line_id', $poLine->id)->first();
                                            $previouslyReceived = $poLine->quantity_received - ($grnLine->quantity_received ?? 0);
                                            $outstanding = $poLine->quantity_ordered - $previouslyReceived - $poLine->quantity_cancelled;
                                        @endphp
                                        <tr data-line-index="{{ $index }}">
                                            <td>{{ $poLine->line_no }}</td>
                                            <td>
                                                <strong>{{ $poLine->model->name ?? '' }}</strong><br>
                                                <small class="text-muted">{{ $poLine->description }}</small>
                                                <input type="hidden" name="lines[{{ $index }}][po_line_id]" value="{{ $poLine->id }}">
                                            </td>
                                            <td class="text-end">{{ $poLine->quantity_ordered }}</td>
                                            <td class="text-end">{{ $previouslyReceived }}</td>
                                            <td class="text-end"><strong>{{ $outstanding }}</strong></td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm receive-qty"
                                                       name="lines[{{ $index }}][quantity_received]"
                                                       data-line-index="{{ $index }}"
                                                       data-is-serialized="{{ $poLine->model->is_serialized ?? false }}"
                                                       data-model-name="{{ $poLine->model->name ?? '' }}"
                                                       value="{{ $grnLine->quantity_received ?? 0 }}"
                                                       min="0" max="{{ $outstanding }}" step="0.01">
                                            </td>
                                            <td>{{ $poLine->unit }}</td>
                                            <td>
                                                @if($poLine->model->is_serialized ?? false)
                                                    <button type="button" class="btn btn-sm btn-primary enter-serials-btn"
                                                            data-line-index="{{ $index }}">
                                                        <i class="bi bi-upc-scan"></i> Serials
                                                        <span class="badge bg-light text-dark serial-count-{{ $index }}">
                                                            {{ $grnLine ? $grnLine->serials->count() : 0 }}
                                                        </span>
                                                    </button>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- GRN Details -->
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>GRN Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="grn_date" class="form-label required">GRN Date</label>
                            <input type="date" class="form-control" id="grn_date" name="grn_date"
                                   value="{{ $grn->grn_date->format('Y-m-d') }}"
                                   max="{{ date('Y-m-d') }}" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="receiving_depot_id" class="form-label required">Receiving Depot</label>
                            <select class="form-select" id="receiving_depot_id" name="receiving_depot_id" required>
                                @foreach($depots as $depot)
                                    <option value="{{ $depot->id }}" {{ $grn->receiving_depot_id == $depot->id ? 'selected' : '' }}>
                                        {{ $depot->depot_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="delivery_note_no" class="form-label">Delivery Note #</label>
                            <input type="text" class="form-control" id="delivery_note_no" name="delivery_note_no"
                                   value="{{ $grn->delivery_note_no }}">
                        </div>

                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3">{{ $grn->remarks }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-save me-1"></i> Update GRN
                            </button>
                            <a href="{{ route('admin.grns.show', $grn) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Serial Entry Modal (Same as create.blade.php) -->
<div class="modal fade" id="serialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-upc-scan me-2"></i>Enter Serial Numbers
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <small>
                        <strong>Item:</strong> <span id="modalItemName"></span><br>
                        <strong>Required Quantity:</strong> <span id="modalRequiredQty"></span><br>
                        <strong>Current Count:</strong> <span id="modalCurrentCount" class="badge bg-warning">0</span>
                    </small>
                </div>

                <!-- Serial Entry Methods -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="manual-tab" data-bs-toggle="tab"
                                data-bs-target="#manual-panel" type="button">
                            <i class="bi bi-keyboard me-1"></i>Manual Entry
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="scan-tab" data-bs-toggle="tab"
                                data-bs-target="#scan-panel" type="button">
                            <i class="bi bi-upc-scan me-1"></i>Scan Entry
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="bulk-tab" data-bs-toggle="tab"
                                data-bs-target="#bulk-panel" type="button">
                            <i class="bi bi-list-ul me-1"></i>Bulk Paste
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="manual-panel">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="manualSerialInput"
                                   placeholder="Enter serial number and press Enter">
                            <button class="btn btn-primary" type="button" id="addManualSerial">
                                <i class="bi bi-plus-lg"></i> Add
                            </button>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="scan-panel">
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle me-2"></i>
                            Focus on the input below and scan your barcodes
                        </div>
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                            <input type="text" class="form-control form-control-lg" id="scanSerialInput"
                                   placeholder="Scan barcode here..." autocomplete="off">
                        </div>
                    </div>

                    <div class="tab-pane fade" id="bulk-panel">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Paste multiple serial numbers separated by commas or line breaks
                        </div>
                        <textarea class="form-control mb-2" id="bulkSerialInput" rows="5"
                                  placeholder="Paste serials here (comma or line separated)"></textarea>
                        <button class="btn btn-primary btn-sm" type="button" id="addBulkSerials">
                            <i class="bi bi-plus-lg me-1"></i> Add All Serials
                        </button>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label fw-bold">Serial Numbers List:</label>
                    <div class="border rounded p-2" style="max-height: 300px; overflow-y: auto;" id="serialsList">
                        <div class="text-muted text-center py-3">No serials added yet</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveSerials">
                    <i class="bi bi-check-lg me-1"></i> Save Serials
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let currentLineIndex = -1;
    let serialsData = @json($grn->lines->mapWithKeys(function($line, $index) use ($grn) {
        $poLineIndex = $grn->purchaseOrder->lines->search(function($poLine) use ($line) {
            return $poLine->id === $line->po_line_id;
        });
        return [$poLineIndex !== false ? $poLineIndex : $index => $line->serials->map(function($serial) {
            return ['serial_no' => $serial->serial_no];
        })->toArray()];
    }));

    // Initialize serial counts
    Object.keys(serialsData).forEach(lineIndex => {
        $(`.serial-count-${lineIndex}`).text(serialsData[lineIndex].length);
    });

    // Enable/disable serial button based on quantity
    $(document).on('input', '.receive-qty', function() {
        const lineIndex = $(this).data('line-index');
        const isSerialized = $(this).data('is-serialized');
        const qty = parseFloat($(this).val()) || 0;

        if (isSerialized) {
            const serialBtn = $(`.enter-serials-btn[data-line-index="${lineIndex}"]`);
            serialBtn.prop('disabled', qty <= 0);

            const currentSerialCount = serialsData[lineIndex] ? serialsData[lineIndex].length : 0;
            if (qty > 0 && currentSerialCount !== qty) {
                $(this).addClass('is-invalid');
                serialBtn.addClass('btn-warning').removeClass('btn-primary btn-success');
            } else if (qty > 0 && currentSerialCount === qty) {
                $(this).removeClass('is-invalid');
                serialBtn.addClass('btn-success').removeClass('btn-primary btn-warning');
            } else {
                $(this).removeClass('is-invalid');
                serialBtn.addClass('btn-primary').removeClass('btn-warning btn-success');
            }
        }
    });

    // Trigger initial validation
    $('.receive-qty').trigger('input');

    // Serial modal and management (same as create view)
    $(document).on('click', '.enter-serials-btn', function() {
        currentLineIndex = $(this).data('line-index');
        const qtyInput = $(`.receive-qty[data-line-index="${currentLineIndex}"]`);
        const requiredQty = parseInt(qtyInput.val()) || 0;
        const modelName = qtyInput.data('model-name');

        $('#modalItemName').text(modelName);
        $('#modalRequiredQty').text(requiredQty);

        const existingSerials = serialsData[currentLineIndex] || [];
        renderSerialsList(existingSerials);
        updateSerialCount();

        $('#serialModal').modal('show');
        setTimeout(() => $('#manualSerialInput').focus(), 500);
    });

    $('#addManualSerial, #manualSerialInput').on('click keypress', function(e) {
        if (e.type === 'click' || (e.type === 'keypress' && e.which === 13)) {
            e.preventDefault();
            const serialNo = $('#manualSerialInput').val().trim();
            if (serialNo) {
                addSerial(serialNo);
                $('#manualSerialInput').val('').focus();
            }
        }
    });

    $('#scanSerialInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const serialNo = $(this).val().trim();
            if (serialNo) {
                addSerial(serialNo);
                $(this).val('');
            }
        }
    });

    $('#addBulkSerials').on('click', function() {
        const input = $('#bulkSerialInput').val().trim();
        if (!input) return;
        const serials = input.split(/[,\n\r]+/).map(s => s.trim()).filter(s => s);
        serials.forEach(serial => addSerial(serial));
        $('#bulkSerialInput').val('');
        Swal.fire({
            icon: 'success',
            title: 'Serials Added',
            text: `Added ${serials.length} serial numbers`,
            timer: 1500,
            showConfirmButton: false
        });
    });

    function addSerial(serialNo) {
        if (!serialsData[currentLineIndex]) {
            serialsData[currentLineIndex] = [];
        }

        const requiredQty = parseInt($('#modalRequiredQty').text());

        if (serialsData[currentLineIndex].some(s => s.serial_no === serialNo)) {
            Swal.fire({
                icon: 'warning',
                title: 'Duplicate',
                text: 'Serial number already added',
                timer: 1500,
                showConfirmButton: false
            });
            return;
        }

        if (serialsData[currentLineIndex].length >= requiredQty) {
            Swal.fire({
                icon: 'warning',
                title: 'Limit Reached',
                text: `Maximum ${requiredQty} serials allowed`,
                timer: 1500,
                showConfirmButton: false
            });
            return;
        }

        $.ajax({
            url: '{{ route("admin.grns.validate-serial") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                serial_no: serialNo
            },
            success: function(response) {
                if (response.valid) {
                    serialsData[currentLineIndex].push({ serial_no: serialNo });
                    renderSerialsList(serialsData[currentLineIndex]);
                    updateSerialCount();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Serial',
                        text: response.message,
                        timer: 2000
                    });
                }
            }
        });
    }

    function renderSerialsList(serials) {
        const container = $('#serialsList');
        container.empty();

        if (serials.length === 0) {
            container.html('<div class="text-muted text-center py-3">No serials added yet</div>');
            return;
        }

        serials.forEach((serial, index) => {
            const item = `
                <div class="d-flex justify-content-between align-items-center p-2 mb-1 border-bottom serial-item">
                    <div>
                        <span class="badge bg-primary me-2">${index + 1}</span>
                        <strong>${serial.serial_no}</strong>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger remove-serial" data-index="${index}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            container.append(item);
        });
    }

    function updateSerialCount() {
        const count = serialsData[currentLineIndex] ? serialsData[currentLineIndex].length : 0;
        const required = parseInt($('#modalRequiredQty').text());

        $('#modalCurrentCount').text(count);

        if (count === required) {
            $('#modalCurrentCount').removeClass('bg-warning').addClass('bg-success');
        } else {
            $('#modalCurrentCount').removeClass('bg-success').addClass('bg-warning');
        }

        $(`.serial-count-${currentLineIndex}`).text(count);
    }

    $(document).on('click', '.remove-serial', function() {
        const index = $(this).data('index');
        serialsData[currentLineIndex].splice(index, 1);
        renderSerialsList(serialsData[currentLineIndex]);
        updateSerialCount();
    });

    $('#saveSerials').on('click', function() {
        const requiredQty = parseInt($('#modalRequiredQty').text());
        const currentCount = serialsData[currentLineIndex] ? serialsData[currentLineIndex].length : 0;

        if (currentCount !== requiredQty) {
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete',
                text: `Please enter exactly ${requiredQty} serial numbers (current: ${currentCount})`,
            });
            return;
        }

        $('#serialModal').modal('hide');
        const serialBtn = $(`.enter-serials-btn[data-line-index="${currentLineIndex}"]`);
        serialBtn.addClass('btn-success').removeClass('btn-primary btn-warning');
        $(`.receive-qty[data-line-index="${currentLineIndex}"]`).removeClass('is-invalid');
    });

    $('#grnForm').on('submit', function(e) {
        e.preventDefault();

        let hasItems = false;
        $('.receive-qty').each(function() {
            if (parseFloat($(this).val()) > 0) {
                hasItems = true;
                return false;
            }
        });

        if (!hasItems) {
            Swal.fire('Error', 'Please enter received quantity for at least one item', 'error');
            return;
        }

        let serialsValid = true;
        $('.receive-qty').each(function() {
            const lineIndex = $(this).data('line-index');
            const isSerialized = $(this).data('is-serialized');
            const qty = parseFloat($(this).val()) || 0;

            if (isSerialized && qty > 0) {
                const serialCount = serialsData[lineIndex] ? serialsData[lineIndex].length : 0;
                if (serialCount !== qty) {
                    serialsValid = false;
                    return false;
                }
            }
        });

        if (!serialsValid) {
            Swal.fire('Error', 'Serial numbers count must match received quantity for all items', 'error');
            return;
        }

        const formData = new FormData(this);

        Object.keys(serialsData).forEach(lineIndex => {
            if (serialsData[lineIndex] && serialsData[lineIndex].length > 0) {
                serialsData[lineIndex].forEach((serial, serialIndex) => {
                    formData.append(`lines[${lineIndex}][serials][${serialIndex}][serial_no]`, serial.serial_no);
                });
            }
        });

        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

        $.ajax({
            url: '{{ route("admin.grns.update", $grn) }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                }
            },
            error: function(xhr) {
                let message = 'An error occurred';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire('Error', message, 'error');
                $('#submitBtn').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Update GRN');
            }
        });
    });
});
</script>
@endpush
