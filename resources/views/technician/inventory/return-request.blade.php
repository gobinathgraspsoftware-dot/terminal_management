@extends('layouts.app')

@section('title', 'Request Stock Return')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-arrow-return-left"></i> Request Stock Return</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('technician.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('technician.inventory.index') }}">My Inventory</a></li>
                    <li class="breadcrumb-item active">Return Request</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('technician.inventory.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Inventory
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Return Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Return Details</h5>
                </div>
                <div class="card-body">
                    <form id="returnRequestForm" action="{{ route('technician.stock-returns.store') }}" method="POST">
                        @csrf

                        <!-- Return Date -->
                        <div class="mb-3">
                            <label for="issue_date" class="form-label">Return Date <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control" 
                                   id="issue_date" 
                                   name="issue_date" 
                                   value="{{ date('Y-m-d') }}" 
                                   required>
                        </div>

                        <!-- Receiving Depot -->
                        <div class="mb-3">
                            <label for="to_depot_id" class="form-label">Return to Depot <span class="text-danger">*</span></label>
                            <select class="form-select" id="to_depot_id" name="to_depot_id" required>
                                <option value="">-- Select Depot --</option>
                                @foreach($depots as $depot)
                                    <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select the depot where you want to return the items</small>
                        </div>

                        <!-- Remarks -->
                        <div class="mb-4">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" 
                                      id="remarks" 
                                      name="remarks" 
                                      rows="3" 
                                      placeholder="Enter any additional notes or comments"></textarea>
                        </div>

                        <hr>

                        <!-- Line Items Header -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Items to Return</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="addLineBtn">
                                <i class="bi bi-plus-circle"></i> Add Item
                            </button>
                        </div>

                        <!-- Line Items Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered" id="linesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30%;">Model</th>
                                        <th style="width: 25%;">Serial Number</th>
                                        <th style="width: 15%;">Qty</th>
                                        <th style="width: 15%;">Condition</th>
                                        <th style="width: 10%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="linesTableBody">
                                    <!-- Lines will be added here dynamically -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('technician.inventory.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle"></i> Submit Return Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- My Current Inventory (Helper) -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-box-seam"></i> My Current Inventory</h5>
                </div>
                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                    @forelse($myInventory as $item)
                    <div class="mb-3 p-3 border rounded bg-light">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>{{ $item->model_name }}</strong><br>
                                <small class="text-muted">{{ $item->category_name }}</small>
                            </div>
                            <span class="badge bg-primary">{{ $item->quantity }}</span>
                        </div>
                        <div class="mb-2">
                            <strong>Status:</strong>
                            @if($item->status == 'issued')
                                <span class="badge bg-success">ISSUED</span>
                            @elseif($item->status == 'deployed')
                                <span class="badge bg-info">DEPLOYED</span>
                            @elseif($item->status == 'faulty')
                                <span class="badge bg-danger">FAULTY</span>
                            @endif
                        </div>
                        <div>
                            <small class="text-muted">
                                <strong>Serials:</strong><br>
                                {{ Str::limit($item->serial_numbers, 100) }}
                            </small>
                        </div>
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary mt-2 quick-add-btn" 
                                data-model-id="{{ $item->model_id }}"
                                data-model-name="{{ $item->model_name }}">
                            <i class="bi bi-plus"></i> Quick Add
                        </button>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No items in inventory
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Helper Info -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6><i class="bi bi-info-circle text-info"></i> Information</h6>
                    <ul class="small mb-0">
                        <li>You can only return items currently in your inventory</li>
                        <li>Select the condition of each item being returned</li>
                        <li>Faulty items will be marked for repair or disposal</li>
                        <li>This is a request - final approval pending</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Line Item Template (Hidden) -->
<template id="lineItemTemplate">
    <tr class="line-item">
        <td>
            <select class="form-select form-select-sm model-select" name="lines[__INDEX__][model_id]" required>
                <option value="">-- Select Model --</option>
                @foreach($myInventory as $item)
                    <option value="{{ $item->model_id }}" data-model-name="{{ $item->model_name }}">
                        {{ $item->model_name }} ({{ $item->quantity }} available)
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm serial-select" name="lines[__INDEX__][serial_id]" required>
                <option value="">-- Select Serial --</option>
            </select>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm" name="lines[__INDEX__][quantity]" value="1" min="1" readonly>
        </td>
        <td>
            <select class="form-select form-select-sm" name="lines[__INDEX__][condition]" required>
                <option value="good">Good</option>
                <option value="damaged">Damaged</option>
                <option value="defective">Defective</option>
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger remove-line-btn">
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

    // Add new line item
    $('#addLineBtn').on('click', function() {
        addLineItem();
    });

    // Quick add from inventory list
    $('.quick-add-btn').on('click', function() {
        const modelId = $(this).data('model-id');
        const modelName = $(this).data('model-name');
        addLineItem(modelId);
    });

    // Remove line item
    $(document).on('click', '.remove-line-btn', function() {
        $(this).closest('tr').remove();
        updateLineNumbers();
    });

    // Model change - load serials
    $(document).on('change', '.model-select', function() {
        const modelId = $(this).val();
        const serialSelect = $(this).closest('tr').find('.serial-select');

        if (!modelId) {
            serialSelect.html('<option value="">-- Select Serial --</option>');
            return;
        }

        // Load serials for this model
        $.ajax({
            url: '{{ route("technician.inventory.get-serial-details") }}',
            method: 'GET',
            data: { model_id: modelId },
            success: function(response) {
                if (response.success) {
                    let options = '<option value="">-- Select Serial --</option>';
                    response.serials.forEach(function(serial) {
                        const statusBadge = serial.status.toUpperCase();
                        options += `<option value="${serial.id}">${serial.serial_no} (${statusBadge})</option>`;
                    });
                    serialSelect.html(options);
                } else {
                    alert('Error loading serials');
                }
            },
            error: function() {
                alert('Error loading serials');
            }
        });
    });

    // Form submission
    $('#returnRequestForm').on('submit', function(e) {
        e.preventDefault();

        // Validate at least one line item
        if ($('.line-item').length === 0) {
            alert('Please add at least one item to return');
            return;
        }

        // Confirm submission
        if (!confirm('Are you sure you want to submit this return request?')) {
            return;
        }

        const formData = $(this).serialize();
        $('#submitBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Submitting...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Return request submitted successfully!');
                    window.location.href = response.redirect || '{{ route("technician.inventory.index") }}';
                } else {
                    alert('Error: ' + (response.message || 'Unknown error'));
                    $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Submit Return Request');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error submitting return request';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
                $('#submitBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Submit Return Request');
            }
        });
    });

    // Function to add line item
    function addLineItem(preselectedModelId = null) {
        const template = document.getElementById('lineItemTemplate');
        const clone = template.content.cloneNode(true);
        const newRow = $(clone).find('tr');

        // Replace __INDEX__ with actual index
        newRow.html(newRow.html().replace(/__INDEX__/g, lineIndex));

        // If model is preselected, set it
        if (preselectedModelId) {
            newRow.find('.model-select').val(preselectedModelId).trigger('change');
        }

        $('#linesTableBody').append(newRow);
        lineIndex++;
    }

    function updateLineNumbers() {
        $('.line-item').each(function(index) {
            $(this).find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\[\d+\]/, `[${index}]`);
                    $(this).attr('name', newName);
                }
            });
        });
        lineIndex = $('.line-item').length;
    }

    // Add one line item on load
    addLineItem();
});
</script>
@endpush

@push('styles')
<style>
    .card {
        border-radius: 10px;
    }

    .table-responsive {
        border-radius: 8px;
    }

    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .table td, .table th {
            padding: 0.5rem;
        }
    }
</style>
@endpush
