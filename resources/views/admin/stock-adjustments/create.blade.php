@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Create Stock Adjustment</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.stock-adjustments.index') }}">Stock Adjustments</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </nav>
        </div>
    </div>

    <form id="adjustmentForm" method="POST" action="{{ route('admin.stock-adjustments.store') }}">
        @csrf
        
        <!-- Header Information Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Adjustment Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="adjustment_date" class="form-label">Adjustment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('adjustment_date') is-invalid @enderror" 
                               id="adjustment_date" name="adjustment_date" 
                               value="{{ old('adjustment_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                        @error('adjustment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="adjustment_type" class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                        <select class="form-select @error('adjustment_type') is-invalid @enderror" 
                                id="adjustment_type" name="adjustment_type" required>
                            <option value="">Select Type</option>
                            <option value="count" {{ old('adjustment_type') == 'count' ? 'selected' : '' }}>Stock Count</option>
                            <option value="correction" {{ old('adjustment_type') == 'correction' ? 'selected' : '' }}>Correction</option>
                            <option value="write_off" {{ old('adjustment_type') == 'write_off' ? 'selected' : '' }}>Write Off</option>
                            <option value="other" {{ old('adjustment_type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('adjustment_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="depot_id" class="form-label">Depot <span class="text-danger">*</span></label>
                        <select class="form-select @error('depot_id') is-invalid @enderror" 
                                id="depot_id" name="depot_id" required>
                            <option value="">Select Depot</option>
                            @foreach($depots as $depot)
                                <option value="{{ $depot->id }}" {{ old('depot_id') == $depot->id ? 'selected' : '' }}>
                                    {{ $depot->depot_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('depot_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('reason') is-invalid @enderror" 
                                  id="reason" name="reason" rows="3" required>{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjustment Lines Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Adjustment Lines</h5>
                <button type="button" class="btn btn-sm btn-primary" id="addLineBtn">
                    <i class="bi bi-plus-circle me-1"></i>Add Line
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="linesTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 25%">Terminal Model <span class="text-danger">*</span></th>
                                <th style="width: 15%">Serial No</th>
                                <th style="width: 10%">System Qty <span class="text-danger">*</span></th>
                                <th style="width: 10%">Physical Qty <span class="text-danger">*</span></th>
                                <th style="width: 10%">Variance</th>
                                <th style="width: 10%">Unit Cost</th>
                                <th style="width: 10%">Var. Value</th>
                                <th style="width: 5%">Action</th>
                            </tr>
                        </thead>
                        <tbody id="linesTableBody">
                            <!-- Lines will be added here dynamically -->
                        </tbody>
                    </table>
                </div>
                <div id="noLinesMessage" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    <p>No adjustment lines added. Click "Add Line" to begin.</p>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.stock-adjustments.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </a>
                    <button type="submit" name="status" value="draft" class="btn btn-outline-primary">
                        <i class="bi bi-save me-1"></i>Save as Draft
                    </button>
                    <button type="submit" name="status" value="pending_approval" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Submit for Approval
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Line Template (Hidden) -->
<template id="lineTemplate">
    <tr class="adjustment-line">
        <td class="text-center line-number">1</td>
        <td>
            <select class="form-select form-select-sm model-select" name="lines[INDEX][model_id]" required>
                <option value="">Select Model</option>
                @foreach($models as $model)
                    <option value="{{ $model->id }}" data-has-serial="{{ $model->has_serial_tracking ? '1' : '0' }}">
                        {{ $model->model_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm serial-input" name="lines[INDEX][serial_no]" placeholder="Serial No">
            <input type="hidden" name="lines[INDEX][serial_id]" class="serial-id-input">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm system-qty" name="lines[INDEX][system_quantity]" 
                   step="0.01" min="0" value="0" required>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm physical-qty" name="lines[INDEX][physical_quantity]" 
                   step="0.01" min="0" value="0" required>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm variance-qty" readonly tabindex="-1">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm unit-cost" name="lines[INDEX][unit_cost]" 
                   step="0.01" min="0" value="0">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm variance-value" name="lines[INDEX][variance_value]" readonly tabindex="-1">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger remove-line-btn" title="Remove">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let lineIndex = 0;

    // Add line button
    $('#addLineBtn').click(function() {
        addNewLine();
    });

    // Add initial line
    addNewLine();

    // Function to add new line
    function addNewLine() {
        let template = $('#lineTemplate').html();
        template = template.replace(/INDEX/g, lineIndex);
        
        let $newRow = $(template);
        $('#linesTableBody').append($newRow);
        
        updateLineNumbers();
        toggleNoLinesMessage();
        
        lineIndex++;
    }

    // Remove line
    $(document).on('click', '.remove-line-btn', function() {
        if ($('#linesTableBody tr').length > 1) {
            $(this).closest('tr').remove();
            updateLineNumbers();
            toggleNoLinesMessage();
        } else {
            Swal.fire('Warning', 'At least one line is required', 'warning');
        }
    });

    // Calculate variance on quantity change
    $(document).on('input', '.system-qty, .physical-qty, .unit-cost', function() {
        let $row = $(this).closest('tr');
        calculateVariance($row);
    });

    // Calculate variance
    function calculateVariance($row) {
        let systemQty = parseFloat($row.find('.system-qty').val()) || 0;
        let physicalQty = parseFloat($row.find('.physical-qty').val()) || 0;
        let unitCost = parseFloat($row.find('.unit-cost').val()) || 0;
        
        let variance = physicalQty - systemQty;
        let varianceValue = variance * unitCost;
        
        $row.find('.variance-qty').val(variance.toFixed(2));
        $row.find('.variance-value').val(varianceValue.toFixed(2));
        
        // Color code variance
        if (variance > 0) {
            $row.find('.variance-qty').removeClass('text-danger').addClass('text-success');
        } else if (variance < 0) {
            $row.find('.variance-qty').removeClass('text-success').addClass('text-danger');
        } else {
            $row.find('.variance-qty').removeClass('text-success text-danger');
        }
    }

    // Update line numbers
    function updateLineNumbers() {
        $('#linesTableBody tr').each(function(index) {
            $(this).find('.line-number').text(index + 1);
        });
    }

    // Toggle no lines message
    function toggleNoLinesMessage() {
        if ($('#linesTableBody tr').length === 0) {
            $('#noLinesMessage').show();
            $('#linesTable').hide();
        } else {
            $('#noLinesMessage').hide();
            $('#linesTable').show();
        }
    }

    // Get current stock when depot and model selected
    $(document).on('change', '.model-select', function() {
        let $row = $(this).closest('tr');
        let depotId = $('#depot_id').val();
        let modelId = $(this).val();
        
        if (depotId && modelId) {
            loadCurrentStock($row, depotId, modelId);
        }
    });

    $('#depot_id').change(function() {
        // Reload stock for all lines
        $('#linesTableBody tr').each(function() {
            let $row = $(this);
            let modelId = $row.find('.model-select').val();
            let depotId = $('#depot_id').val();
            
            if (depotId && modelId) {
                loadCurrentStock($row, depotId, modelId);
            }
        });
    });

    // Load current stock from depot
    function loadCurrentStock($row, depotId, modelId) {
        $.ajax({
            url: "{{ route('admin.stock-adjustments.get-depot-stock') }}",
            type: 'GET',
            data: { depot_id: depotId, model_id: modelId },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let stock = response.data[0];
                    $row.find('.system-qty').val(stock.quantity_on_hand);
                    calculateVariance($row);
                }
            }
        });
    }

    // Form submission
    $('#adjustmentForm').submit(function(e) {
        e.preventDefault();
        
        if ($('#linesTableBody tr').length === 0) {
            Swal.fire('Error', 'Please add at least one adjustment line', 'error');
            return false;
        }
        
        let formData = $(this).serialize();
        let submitBtn = $(document.activeElement);
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success', response.message, 'success').then(() => {
                        window.location.href = response.redirect;
                    });
                }
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html(submitBtn.data('original-text'));
                
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = Object.values(errors).flat().join('<br>');
                    Swal.fire('Validation Error', errorMessage, 'error');
                } else {
                    Swal.fire('Error', xhr.responseJSON?.message || 'An error occurred', 'error');
                }
            }
        });
    });

    // Store original button text
    $('button[type="submit"]').each(function() {
        $(this).data('original-text', $(this).html());
    });
});
</script>
@endpush
