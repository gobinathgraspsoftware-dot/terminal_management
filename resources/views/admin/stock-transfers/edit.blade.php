@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil"></i> Edit Stock Transfer</h2>
        <a href="{{ route('admin.stock-transfers.show', $stockTransfer) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form id="transferForm" method="POST" action="{{ route('admin.stock-transfers.update', $stockTransfer) }}">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Transfer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required">Transfer Date</label>
                            <input type="date" name="transfer_date" class="form-control @error('transfer_date') is-invalid @enderror" 
                                   value="{{ old('transfer_date', $stockTransfer->transfer_date->format('Y-m-d')) }}" required>
                            @error('transfer_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">From Depot</label>
                            <select name="from_depot_id" id="fromDepot" class="form-select @error('from_depot_id') is-invalid @enderror" required>
                                <option value="">Select Source Depot</option>
                                @foreach($depots as $depot)
                                <option value="{{ $depot->id }}" {{ old('from_depot_id', $stockTransfer->from_depot_id) == $depot->id ? 'selected' : '' }}>
                                    {{ $depot->depot_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('from_depot_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">To Depot</label>
                            <select name="to_depot_id" id="toDepot" class="form-select @error('to_depot_id') is-invalid @enderror" required>
                                <option value="">Select Destination Depot</option>
                                @foreach($depots as $depot)
                                <option value="{{ $depot->id }}" {{ old('to_depot_id', $stockTransfer->to_depot_id) == $depot->id ? 'selected' : '' }}>
                                    {{ $depot->depot_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('to_depot_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3">{{ old('remarks', $stockTransfer->remarks) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Available Stock</h5>
                    </div>
                    <div class="card-body">
                        <div id="availableStockInfo">
                            <p class="text-muted">Select source depot to view available stock</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Transfer Items</h5>
                <button type="button" id="btnAddLine" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Item
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="linesTable">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="30%">Model</th>
                                <th width="25%">Serial No</th>
                                <th width="15%">Qty Requested</th>
                                <th width="20%">Remarks</th>
                                <th width="5%">Action</th>
                            </tr>
                        </thead>
                        <tbody id="linesContainer">
                            @foreach($stockTransfer->lines as $index => $line)
                            <tr class="transfer-line" data-line="{{ $loop->iteration }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <select name="lines[{{ $loop->iteration }}][model_id]" class="form-select form-select-sm model-select" required>
                                        @foreach($models as $model)
                                        <option value="{{ $model->id }}" {{ $line->model_id == $model->id ? 'selected' : '' }}>
                                            {{ $model->category->category_name }} - {{ $model->model_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" value="{{ $line->serial_no ?? 'N/A' }}" readonly>
                                    <input type="hidden" name="lines[{{ $loop->iteration }}][serial_id]" value="{{ $line->serial_id }}">
                                </td>
                                <td>
                                    <input type="number" name="lines[{{ $loop->iteration }}][quantity_requested]" 
                                           class="form-control form-control-sm" value="{{ $line->quantity_requested }}" 
                                           min="0.0001" step="0.0001" required>
                                </td>
                                <td>
                                    <input type="text" name="lines[{{ $loop->iteration }}][remarks]" 
                                           class="form-control form-control-sm" value="{{ $line->remarks }}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger btn-remove-line">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.stock-transfers.show', $stockTransfer) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Transfer
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
let lineCounter = {{ $stockTransfer->lines->count() }};
let availableSerials = [];

$(document).ready(function() {
    $('#fromDepot, #toDepot').select2({ theme: 'bootstrap-5', width: '100%' });
    $('.model-select').select2({ theme: 'bootstrap-5', width: '100%' });
    
    $('#fromDepot, #toDepot').change(function() {
        const fromDepot = $('#fromDepot').val();
        const toDepot = $('#toDepot').val();
        
        if (fromDepot && toDepot && fromDepot === toDepot) {
            alert('Source and destination depots must be different');
            $(this).val('').trigger('change');
        }
    });

    $('#fromDepot').change(function() {
        const depotId = $(this).val();
        if (depotId) {
            $.get('{{ route("admin.stock-transfers.available-stock") }}', { from_depot_id: depotId })
            .done(function(response) {
                availableSerials = response.serials || [];
                displayAvailableStock(availableSerials);
            });
        }
    });
    
    $('#btnAddLine').click(function() {
        if (!$('#fromDepot').val()) {
            alert('Please select source depot first');
            return;
        }
        addLine();
    });
    
    $(document).on('click', '.btn-remove-line', function() {
        if ($('.transfer-line').length > 1) {
            $(this).closest('tr').remove();
            renumberLines();
        } else {
            alert('At least one line is required');
        }
    });

    // Trigger initial stock load
    if ($('#fromDepot').val()) {
        $('#fromDepot').trigger('change');
    }
});

function displayAvailableStock(serials) {
    if (!serials || serials.length === 0) {
        $('#availableStockInfo').html('<div class="alert alert-warning">No stock available</div>');
        return;
    }

    let html = '<div class="table-responsive"><table class="table table-sm">';
    html += '<thead><tr><th>Model</th><th>Available</th></tr></thead><tbody>';
    
    const grouped = {};
    serials.forEach(s => {
        if (!grouped[s.model_id]) {
            grouped[s.model_id] = { name: s.model.model_name, count: 0 };
        }
        grouped[s.model_id].count++;
    });

    Object.values(grouped).forEach(item => {
        html += `<tr><td>${item.name}</td><td>${item.count}</td></tr>`;
    });
    
    html += '</tbody></table></div>';
    $('#availableStockInfo').html(html);
}

function addLine() {
    lineCounter++;
    
    const html = `
        <tr class="transfer-line" data-line="${lineCounter}">
            <td>${lineCounter}</td>
            <td>
                <select name="lines[${lineCounter}][model_id]" class="form-select form-select-sm model-select" required>
                    <option value="">Select Model</option>
                    @foreach($models as $model)
                    <option value="{{ $model->id }}">{{ $model->category->category_name }} - {{ $model->model_name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="lines[${lineCounter}][serial_id]" class="form-select form-select-sm serial-select">
                    <option value="">Optional</option>
                </select>
            </td>
            <td>
                <input type="number" name="lines[${lineCounter}][quantity_requested]" 
                       class="form-control form-control-sm" value="1" min="0.0001" step="0.0001" required>
            </td>
            <td>
                <input type="text" name="lines[${lineCounter}][remarks]" class="form-control form-control-sm">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger btn-remove-line">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    $('#linesContainer').append(html);
    
    // Initialize Select2 for new row
    $(`.transfer-line[data-line="${lineCounter}"] .model-select`).select2({ theme: 'bootstrap-5', width: '100%' });
    $(`.transfer-line[data-line="${lineCounter}"] .serial-select`).select2({ theme: 'bootstrap-5', width: '100%' });
}

function renumberLines() {
    $('.transfer-line').each(function(index) {
        $(this).find('td:first').text(index + 1);
    });
}
</script>
@endpush
@endsection
