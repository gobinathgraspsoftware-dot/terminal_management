@extends('layouts.app')

@section('title', 'Edit Stock Return')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3 mb-0">Edit Stock Return - {{ $stockReturn->issue_no }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.stock-returns.index') }}">Stock Returns</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.stock-returns.show', $stockReturn->id) }}">{{ $stockReturn->issue_no }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($stockReturn->status !== 'draft')
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Only draft stock returns can be edited.
    </div>
    @else

    <form id="returnForm" action="{{ route('admin.stock-returns.update', $stockReturn->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row">
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
                                       value="{{ $stockReturn->issue_date->format('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label required">From Technician</label>
                                <select class="form-select" name="from_technician_id" id="from_technician_id" required>
                                    <option value="">Select Technician</option>
                                    @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}" {{ $stockReturn->from_technician_id == $tech->id ? 'selected' : '' }}>
                                        {{ $tech->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">To Depot</label>
                                <select class="form-select" name="to_depot_id" required>
                                    <option value="">Select Depot</option>
                                    @foreach($depots as $depot)
                                    <option value="{{ $depot->id }}" {{ $stockReturn->to_depot_id == $depot->id ? 'selected' : '' }}>
                                        {{ $depot->depot_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" name="remarks" rows="2">{{ $stockReturn->remarks }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Return Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="linesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Model</th>
                                        <th>Serial No</th>
                                        <th>Qty</th>
                                        <th>Condition</th>
                                        <th>Remarks</th>
                                        <th>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="addLine()">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="linesBody">
                                    @foreach($stockReturn->lines as $index => $line)
                                    <tr data-index="{{ $index }}">
                                        <td>
                                            <select class="form-select form-select-sm" name="lines[{{ $index }}][model_id]" required>
                                                @foreach($models as $model)
                                                <option value="{{ $model->id }}" {{ $line->model_id == $model->id ? 'selected' : '' }}>
                                                    {{ $model->model_name }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="lines[{{ $index }}][serial_id]">
                                                <option value="{{ $line->serial_id }}">{{ $line->serial_no }}</option>
                                            </select>
                                            <input type="hidden" name="lines[{{ $index }}][serial_no]" value="{{ $line->serial_no }}">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm quantity" 
                                                   name="lines[{{ $index }}][quantity]" value="{{ $line->quantity }}" required>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm condition-select" name="lines[{{ $index }}][condition]" required>
                                                <option value="good" {{ $line->condition == 'good' ? 'selected' : '' }}>Good</option>
                                                <option value="damaged" {{ $line->condition == 'damaged' ? 'selected' : '' }}>Damaged</option>
                                                <option value="defective" {{ $line->condition == 'defective' ? 'selected' : '' }}>Defective</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" 
                                                   name="lines[{{ $index }}][remarks]" value="{{ $line->remarks }}">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)">
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
            </div>

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
                        <hr>
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-check-circle me-1"></i> Update Return
                        </button>
                        <a href="{{ route('admin.stock-returns.show', $stockReturn->id) }}" class="btn btn-secondary w-100">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
let lineIndex = {{ $stockReturn->lines->count() }};

$(document).ready(function() {
    updateSummary();
    
    $('.quantity, .condition-select').on('change', updateSummary);
});

function addLine() {
    const html = `
        <tr data-index="${lineIndex}">
            <td>
                <select class="form-select form-select-sm" name="lines[${lineIndex}][model_id]" required>
                    <option value="">Select Model</option>
                    @foreach($models as $model)
                    <option value="{{ $model->id }}">{{ $model->model_name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select class="form-select form-select-sm" name="lines[${lineIndex}][serial_id]">
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
    $(`tr[data-index="${lineIndex}"] .quantity, tr[data-index="${lineIndex}"] .condition-select`).on('change', updateSummary);
    lineIndex++;
    updateSummary();
}

function removeLine(btn) {
    $(btn).closest('tr').remove();
    updateSummary();
}

function updateSummary() {
    let total = 0;
    $('#linesBody tr').each(function() {
        total += parseFloat($(this).find('.quantity').val()) || 0;
    });
    $('#totalItems').text(total.toFixed(2));
}

$('#returnForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: $(this).attr('action'),
        method: 'PUT',
        data: $(this).serialize(),
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
