@extends('layouts.app')

@section('title', 'Edit Stock Issue')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil text-warning"></i> Edit Stock Issue
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.stock-issues.index') }}">Stock Issues</a></li>
                    <li class="breadcrumb-item active">Edit {{ $stockIssue->issue_no }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <form id="stockIssueForm">
        @csrf
        @method('PUT')
        
        <!-- Header Card -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0"><i class="bi bi-info-circle"></i> Issue Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Issue No</label>
                        <input type="text" class="form-control" value="{{ $stockIssue->issue_no }}" readonly>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label required">Issue Date</label>
                        <input type="date" name="issue_date" id="issue_date" class="form-control" value="{{ $stockIssue->issue_date->format('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Issue Type</label>
                        <input type="text" class="form-control" value="{{ ucwords(str_replace('_', ' ', $stockIssue->issue_type)) }}" readonly>
                        <input type="hidden" name="issue_type" id="issue_type" value="{{ $stockIssue->issue_type }}">
                    </div>

                    <!-- Fields for Issue to Tech -->
                    @if($stockIssue->issue_type === 'issue_to_tech')
                    <div class="col-md-3">
                        <label class="form-label required">From Depot</label>
                        <select name="from_depot_id" id="from_depot_id" class="form-select" required>
                            <option value="">Select Depot</option>
                            @foreach($depots as $depot)
                                <option value="{{ $depot->id }}" {{ $stockIssue->from_depot_id == $depot->id ? 'selected' : '' }}>{{ $depot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label required">To Technician</label>
                        <select name="to_technician_id" id="to_technician_id" class="form-select" required>
                            <option value="">Select Technician</option>
                            @foreach($teamTechnicians as $tech)
                                <option value="{{ $tech->id }}" {{ $stockIssue->to_technician_id == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <!-- Fields for Return from Tech -->
                    <div class="col-md-3">
                        <label class="form-label required">From Technician</label>
                        <select name="from_technician_id" id="from_technician_id" class="form-select" required>
                            <option value="">Select Technician</option>
                            @foreach($teamTechnicians as $tech)
                                <option value="{{ $tech->id }}" {{ $stockIssue->from_technician_id == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label required">To Depot</label>
                        <select name="to_depot_id" id="to_depot_id" class="form-select" required>
                            <option value="">Select Depot</option>
                            @foreach($depots as $depot)
                                <option value="{{ $depot->id }}" {{ $stockIssue->to_depot_id == $depot->id ? 'selected' : '' }}>{{ $depot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    
                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="2">{{ $stockIssue->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Card -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-list-ul"></i> Line Items</h5>
                <button type="button" id="addLineBtn" class="btn btn-sm btn-light">
                    <i class="bi bi-plus-circle"></i> Add Line
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="linesTable">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="25%">Model <span class="text-danger">*</span></th>
                                <th width="25%">Serial No</th>
                                <th width="15%">Quantity <span class="text-danger">*</span></th>
                                <th width="25%">Remarks</th>
                                <th width="5%">Action</th>
                            </tr>
                        </thead>
                        <tbody id="linesTableBody">
                            @foreach($stockIssue->lines as $index => $line)
                            <tr data-line-index="{{ $index + 1 }}">
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <select name="lines[{{ $index + 1 }}][model_id]" class="form-select form-select-sm line-model" required>
                                        <option value="">Select Model</option>
                                        @foreach($models as $model)
                                            <option value="{{ $model->id }}" {{ $line->model_id == $model->id ? 'selected' : '' }}>{{ $model->model_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="hidden" name="lines[{{ $index + 1 }}][serial_id]" class="line-serial-id" value="{{ $line->serial_id }}">
                                        <input type="text" name="lines[{{ $index + 1 }}][serial_no]" class="form-control line-serial-no" value="{{ $line->serial_no }}" readonly placeholder="Optional">
                                        <button type="button" class="btn btn-outline-secondary select-serial-btn" data-line-index="{{ $index + 1 }}">
                                            <i class="bi bi-upc-scan"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" name="lines[{{ $index + 1 }}][quantity]" class="form-control form-control-sm line-quantity" value="{{ $line->quantity }}" min="0.0001" step="0.0001" required>
                                </td>
                                <td>
                                    <input type="text" name="lines[{{ $index + 1 }}][remarks]" class="form-control form-control-sm" value="{{ $line->remarks }}">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-line-btn" data-line-index="{{ $index + 1 }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3" id="noLinesAlert" style="display:none;">
                    <i class="bi bi-info-circle"></i> No line items added yet. Click "Add Line" to start.
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('supervisor.stock-issues.show', $stockIssue->id) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-warning" id="submitBtn">
                        <i class="bi bi-save"></i> Update Stock Issue
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Serial Selection Modal -->
<div class="modal fade" id="serialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Serial Number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Scan or Enter Serial</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                        <input type="text" id="serialScanInput" class="form-control" placeholder="Scan barcode or type serial number...">
                        <button type="button" class="btn btn-primary" id="serialScanBtn">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
                <div id="serialListContainer" style="display:none;">
                    <h6>Available Serials:</h6>
                    <div class="list-group" id="serialList"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let lineCounter = {{ $stockIssue->lines->count() }};
let currentLineIndex = null;

$(document).ready(function() {
    updateLinesDisplay();
    
    // Attach event handlers to existing lines
    $('.select-serial-btn').on('click', function() {
        openSerialModal($(this).data('line-index'));
    });
    
    $('.remove-line-btn').on('click', function() {
        removeLine($(this).data('line-index'));
    });

    // Add line button
    $('#addLineBtn').on('click', function() {
        addLine();
    });

    // Form submission
    $('#stockIssueForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }

        const formData = getFormData();
        
        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');
        
        $.ajax({
            url: '{{ route("supervisor.stock-issues.update", $stockIssue->id) }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                Swal.fire('Success!', response.message, 'success').then(() => {
                    window.location.href = response.redirect;
                });
            },
            error: function(xhr) {
                Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to update stock issue', 'error');
                $('#submitBtn').prop('disabled', false).html('<i class="bi bi-save"></i> Update Stock Issue');
            }
        });
    });
});

function addLine() {
    lineCounter++;
    const issueType = $('#issue_type').val();
    
    const row = `
        <tr data-line-index="${lineCounter}">
            <td class="text-center">${lineCounter}</td>
            <td>
                <select name="lines[${lineCounter}][model_id]" class="form-select form-select-sm line-model" required>
                    <option value="">Select Model</option>
                    @foreach($models as $model)
                        <option value="{{ $model->id }}">{{ $model->model_name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="hidden" name="lines[${lineCounter}][serial_id]" class="line-serial-id">
                    <input type="text" name="lines[${lineCounter}][serial_no]" class="form-control line-serial-no" readonly placeholder="Optional">
                    <button type="button" class="btn btn-outline-secondary select-serial-btn" data-line-index="${lineCounter}">
                        <i class="bi bi-upc-scan"></i>
                    </button>
                </div>
            </td>
            <td>
                <input type="number" name="lines[${lineCounter}][quantity]" class="form-control form-control-sm line-quantity" value="1" min="0.0001" step="0.0001" required>
            </td>
            <td>
                <input type="text" name="lines[${lineCounter}][remarks]" class="form-control form-control-sm">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger remove-line-btn" data-line-index="${lineCounter}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    $('#linesTableBody').append(row);
    updateLinesDisplay();
    
    // Attach event handlers
    $(`.select-serial-btn[data-line-index="${lineCounter}"]`).on('click', function() {
        openSerialModal($(this).data('line-index'));
    });
    
    $(`.remove-line-btn[data-line-index="${lineCounter}"]`).on('click', function() {
        removeLine($(this).data('line-index'));
    });
}

function removeLine(index) {
    $(`tr[data-line-index="${index}"]`).remove();
    updateLinesDisplay();
}

function updateLinesDisplay() {
    const lineCount = $('#linesTableBody tr').length;
    $('#noLinesAlert').toggle(lineCount === 0);
}

function openSerialModal(lineIndex) {
    const issueType = $('#issue_type').val();
    const modelId = $(`tr[data-line-index="${lineIndex}"] .line-model`).val();
    
    if (!modelId) {
        Swal.fire('Warning', 'Please select a model first', 'warning');
        return;
    }
    
    currentLineIndex = lineIndex;
    $('#serialScanInput').val('');
    $('#serialListContainer').hide();
    $('#serialList').empty();
    $('#serialModal').modal('show');
    
    loadAvailableSerials(modelId);
}

function loadAvailableSerials(modelId) {
    const issueType = $('#issue_type').val();
    let url, locationId;
    
    if (issueType === 'issue_to_tech') {
        url = '{{ route("supervisor.stock-issues.get-available-serials") }}';
        locationId = $('#from_depot_id').val();
        if (!locationId) {
            Swal.fire('Warning', 'Please select From Depot first', 'warning');
            return;
        }
    } else {
        url = '{{ route("supervisor.stock-issues.get-technician-serials") }}';
        locationId = $('#from_technician_id').val();
        if (!locationId) {
            Swal.fire('Warning', 'Please select From Technician first', 'warning');
            return;
        }
    }
    
    $.ajax({
        url: url,
        type: 'GET',
        data: {
            depot_id: locationId,
            technician_id: locationId,
            model_id: modelId
        },
        success: function(response) {
            displaySerials(response.serials);
        }
    });
}

function displaySerials(serials) {
    $('#serialList').empty();
    
    if (serials.length === 0) {
        $('#serialListContainer').show();
        $('#serialList').html('<div class="alert alert-warning">No available serials found</div>');
        return;
    }
    
    serials.forEach(serial => {
        const item = `
            <button type="button" class="list-group-item list-group-item-action" onclick="selectSerial(${serial.id}, '${serial.serial_number}')">
                <div class="d-flex justify-content-between">
                    <strong>${serial.serial_number}</strong>
                    <span class="badge bg-success">${serial.model.model_name}</span>
                </div>
            </button>
        `;
        $('#serialList').append(item);
    });
    
    $('#serialListContainer').show();
}

function selectSerial(serialId, serialNo) {
    $(`tr[data-line-index="${currentLineIndex}"] .line-serial-id`).val(serialId);
    $(`tr[data-line-index="${currentLineIndex}"] .line-serial-no`).val(serialNo);
    $(`tr[data-line-index="${currentLineIndex}"] .line-quantity`).val(1).prop('readonly', true);
    $('#serialModal').modal('hide');
}

function validateForm() {
    if ($('#linesTableBody tr').length === 0) {
        Swal.fire('Warning', 'Please add at least one line item', 'warning');
        return false;
    }
    return true;
}

function getFormData() {
    const formData = {
        _token: '{{ csrf_token() }}',
        _method: 'PUT',
        issue_date: $('#issue_date').val(),
        issue_type: $('#issue_type').val(),
        remarks: $('#remarks').val(),
        lines: []
    };
    
    if (formData.issue_type === 'issue_to_tech') {
        formData.from_depot_id = $('#from_depot_id').val();
        formData.to_technician_id = $('#to_technician_id').val();
    } else {
        formData.from_technician_id = $('#from_technician_id').val();
        formData.to_depot_id = $('#to_depot_id').val();
    }
    
    $('#linesTableBody tr').each(function() {
        const line = {
            model_id: $(this).find('.line-model').val(),
            serial_id: $(this).find('.line-serial-id').val() || null,
            serial_no: $(this).find('.line-serial-no').val() || null,
            quantity: $(this).find('.line-quantity').val(),
            remarks: $(this).find('input[name$="[remarks]"]').val()
        };
        formData.lines.push(line);
    });
    
    return formData;
}
</script>
@endpush

@push('styles')
<style>
.required::after {
    content: " *";
    color: red;
}
</style>
@endpush
