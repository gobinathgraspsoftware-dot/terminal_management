@extends('layouts.app')

@section('title', 'Create Stock Return')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3 mb-0">Create Stock Return</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.stock-returns.index') }}">Stock Returns</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <form id="returnForm" action="{{ route('admin.stock-returns.store') }}" method="POST">
        @csrf

        <div class="row">
            <!-- Main Form -->
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Return Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Return Date</label>
                                <input type="date" class="form-control" name="issue_date"
                                       value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">From Technician</label>
                                <select class="form-select" name="from_technician_id" id="from_technician_id" required>
                                    <option value="">Select Technician</option>
                                    @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">To Depot</label>
                                <select class="form-select" name="to_depot_id" required>
                                    <option value="">Select Depot</option>
                                    @foreach($depots as $depot)
                                    <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" name="remarks" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technician Inventory -->
                <div class="card mb-3" id="inventoryCard" style="display: none;">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-box-seam me-2"></i>Technician Current Inventory
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="inventoryList"></div>
                    </div>
                </div>

                <!-- Return Lines -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Return Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="linesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="25%">Model</th>
                                        <th width="20%">Serial No</th>
                                        <th width="10%">Qty</th>
                                        <th width="15%">Condition</th>
                                        <th width="25%">Remarks</th>
                                        <th width="5%">
                                            <button type="button" class="btn btn-sm btn-primary" onclick="addLine()">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="linesBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Sidebar -->
            <div class="col-md-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small">Total Items</label>
                            <h3 class="mb-0" id="totalItems">0</h3>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Good Condition</label>
                            <h4 class="mb-0 text-success" id="goodItems">0</h4>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Damaged Items</label>
                            <h4 class="mb-0 text-warning" id="damagedItems">0</h4>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Defective Items</label>
                            <h4 class="mb-0 text-danger" id="defectiveItems">0</h4>
                        </div>
                        <hr>
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-check-circle me-1"></i> Save Return
                        </button>
                        <a href="{{ route('admin.stock-returns.index') }}" class="btn btn-secondary w-100">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let lineIndex = 0;
let technicianSerials = [];

$(document).ready(function() {
    // addLine();

    $('#from_technician_id').on('change', function() {
        const techId = $(this).val();
        if (techId) {
            loadTechnicianInventory(techId);
        } else {
            $('#inventoryCard').hide();
            technicianSerials = [];
        }
    });
});

function loadTechnicianInventory(techId) {
    $.ajax({
        url: "{{ route('admin.stock-returns.technician-summary') }}",
        data: {technician_id: techId},
        success: function(response) {
            if (response.success && response.summary.length > 0) {
                displayInventory(response.summary);
                $('#inventoryCard').show();
            } else {
                Swal.fire('No Inventory', 'This technician has no items to return.', 'info');
                $('#inventoryCard').hide();
            }
        }
    });
}

function displayInventory(summary) {
    let html = '<div class="table-responsive"><table class="table table-sm table-hover">';
    html += '<thead><tr><th>Model</th><th>Status</th><th>Qty</th><th>Serials</th></tr></thead><tbody>';

    summary.forEach(item => {
        const statusBadge = item.status === 'issued' ? 'bg-primary' :
                          item.status === 'deployed' ? 'bg-success' : 'bg-warning';
        html += `<tr>
            <td>${item.model_name}</td>
            <td><span class="badge ${statusBadge}">${item.status}</span></td>
            <td>${item.quantity}</td>
            <td><small class="text-muted">${item.serial_numbers}</small></td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    $('#inventoryList').html(html);
}

function addLine() {
    const techId = $('#from_technician_id').val();
    if (!techId) {
        Swal.fire('Error', 'Please select a technician first', 'error');
        return;
    }

    const html = `
        <tr data-index="${lineIndex}">
            <td>
                <select class="form-select form-select-sm model-select" name="lines[${lineIndex}][model_id]" required>
                    <option value="">Select Model</option>
                    @foreach($models as $model)
                    <option value="{{ $model->id }}">{{ $model->model_name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select class="form-select form-select-sm serial-select" name="lines[${lineIndex}][serial_id]">
                    <option value="">Select Serial</option>
                </select>
                <input type="hidden" name="lines[${lineIndex}][serial_no]" class="serial-no">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm quantity"
                       name="lines[${lineIndex}][quantity]" value="1" min="0.01" step="0.01" required>
            </td>
            <td>
                <select class="form-select form-select-sm condition-select" name="lines[${lineIndex}][condition]" required>
                    <option value="good">Good</option>
                    <option value="damaged">Damaged</option>
                    <option value="defective">Defective</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="lines[${lineIndex}][remarks]">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;

    $('#linesBody').append(html);

    // Add event listeners
    $(`tr[data-index="${lineIndex}"] .model-select`).on('change', function() {
        loadSerials($(this).closest('tr'), techId, $(this).val());
    });

    $(`tr[data-index="${lineIndex}"] .serial-select`).on('change', function() {
        const serialNo = $(this).find('option:selected').text();
        $(this).closest('tr').find('.serial-no').val(serialNo);
    });

    $(`tr[data-index="${lineIndex}"] .quantity, tr[data-index="${lineIndex}"] .condition-select`).on('change', updateSummary);

    lineIndex++;
    updateSummary();
}

function loadSerials(row, techId, modelId) {
    if (!modelId) return;

    $.ajax({
        url: "{{ route('admin.stock-returns.technician-inventory') }}",
        data: {technician_id: techId, model_id: modelId},
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Select Serial</option>';
                response.inventory.forEach(serial => {
                    options += `<option value="${serial.id}">${serial.serial_no}</option>`;
                });
                row.find('.serial-select').html(options);
            }
        }
    });
}

function removeLine(btn) {
    $(btn).closest('tr').remove();
    updateSummary();
}

function updateSummary() {
    let total = 0, good = 0, damaged = 0, defective = 0;

    $('#linesBody tr').each(function() {
        const qty = parseFloat($(this).find('.quantity').val()) || 0;
        const condition = $(this).find('.condition-select').val();

        total += qty;
        if (condition === 'good') good += qty;
        else if (condition === 'damaged') damaged += qty;
        else if (condition === 'defective') defective += qty;
    });

    $('#totalItems').text(total.toFixed(2));
    $('#goodItems').text(good.toFixed(2));
    $('#damagedItems').text(damaged.toFixed(2));
    $('#defectiveItems').text(defective.toFixed(2));
}

$('#returnForm').on('submit', function(e) {
    e.preventDefault();

    if ($('#linesBody tr').length === 0) {
        Swal.fire('Error', 'Please add at least one item to return', 'error');
        return;
    }

    const formData = $(this).serialize();

    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: formData,
        success: function(response) {
            Swal.fire('Success!', response.message, 'success');
            setTimeout(() => window.location.href = response.redirect, 1500);
        },
        error: function(xhr) {
            Swal.fire('Error!', xhr.responseJSON.message, 'error');
        }
    });
});
</script>
@endpush

@push('styles')
<style>
.required::after {
    content: '*';
    color: red;
    margin-left: 4px;
}
</style>
@endpush
