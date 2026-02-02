@extends('layouts.app')

@section('title', 'Create Rate Card')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Create Rate Card</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.rate-cards.index') }}">Rate Cards</a></li>
                            <li class="breadcrumb-item active">Create</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.rate-cards.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.rate-cards.store') }}" method="POST" id="rateCardForm">
        @csrf
        <div class="row">
            <!-- Main Form -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Rate Card Information</h5>
                    </div>
                    <div class="card-body">
                        <!-- Rate Card Name -->
                        <div class="mb-3">
                            <label for="rate_card_name" class="form-label">Rate Card Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('rate_card_name') is-invalid @enderror" 
                                   id="rate_card_name" name="rate_card_name" value="{{ old('rate_card_name') }}" 
                                   placeholder="e.g., KL Installation Premium" required>
                            @error('rate_card_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Code will be auto-generated</small>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Optional description">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Job Type -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="job_type" class="form-label">Job Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('job_type') is-invalid @enderror" 
                                            id="job_type" name="job_type" required>
                                        @foreach($jobTypes as $key => $value)
                                            <option value="{{ $key }}" {{ old('job_type') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('job_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Terminal Model -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="model_id" class="form-label">Terminal Model</label>
                                    <select class="form-select @error('model_id') is-invalid @enderror" 
                                            id="model_id" name="model_id">
                                        <option value="">All Models</option>
                                        @foreach($models as $model)
                                            <option value="{{ $model->id }}" {{ old('model_id') == $model->id ? 'selected' : '' }}>
                                                {{ $model->model_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('model_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Leave blank for all models</small>
                                </div>
                            </div>

                            <!-- State -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="state" class="form-label">State</label>
                                    <select class="form-select @error('state') is-invalid @enderror" 
                                            id="state" name="state">
                                        <option value="">All States</option>
                                        @foreach($states as $key => $value)
                                            <option value="{{ $value }}" {{ old('state') == $value ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('state')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Leave blank for all states</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">Commission Calculation</h6>

                        <div class="row">
                            <!-- Calculation Type -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="calculation_type" class="form-label">Calculation Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('calculation_type') is-invalid @enderror" 
                                            id="calculation_type" name="calculation_type" required>
                                        @foreach($calculationTypes as $key => $value)
                                            <option value="{{ $key }}" {{ old('calculation_type', 'flat') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('calculation_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Rate Amount -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rate_amount" class="form-label">
                                        <span id="rate_amount_label">Rate Amount (RM)</span> 
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" step="0.01" class="form-control @error('rate_amount') is-invalid @enderror" 
                                           id="rate_amount" name="rate_amount" value="{{ old('rate_amount') }}" 
                                           placeholder="0.00" required>
                                    @error('rate_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Min Amount -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="min_amount" class="form-label">Minimum Amount (RM)</label>
                                    <input type="number" step="0.01" class="form-control @error('min_amount') is-invalid @enderror" 
                                           id="min_amount" name="min_amount" value="{{ old('min_amount') }}" 
                                           placeholder="Optional">
                                    @error('min_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Floor value for commission</small>
                                </div>
                            </div>

                            <!-- Max Amount -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="max_amount" class="form-label">Maximum Amount (RM)</label>
                                    <input type="number" step="0.01" class="form-control @error('max_amount') is-invalid @enderror" 
                                           id="max_amount" name="max_amount" value="{{ old('max_amount') }}" 
                                           placeholder="Optional">
                                    @error('max_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Ceiling value for commission</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">Effective Dates</h6>

                        <div class="row">
                            <!-- Effective From -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="effective_from" class="form-label">Effective From <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('effective_from') is-invalid @enderror" 
                                           id="effective_from" name="effective_from" 
                                           value="{{ old('effective_from', now()->format('Y-m-d')) }}" required>
                                    @error('effective_from')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Effective To -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="effective_to" class="form-label">Effective To</label>
                                    <input type="date" class="form-control @error('effective_to') is-invalid @enderror" 
                                           id="effective_to" name="effective_to" value="{{ old('effective_to') }}">
                                    @error('effective_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Leave blank for no end date</small>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" name="status" required>
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-save"></i> Create Rate Card
                        </button>
                        <a href="{{ route('admin.rate-cards.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Preview Panel -->
            <div class="col-md-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0"><i class="bi bi-calculator"></i> Commission Preview</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Test Job Value (RM)</label>
                            <input type="number" step="0.01" class="form-control" id="test_job_value" value="1000.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Test Terminal Count</label>
                            <input type="number" class="form-control" id="test_terminal_count" value="1" min="1">
                        </div>

                        <button type="button" class="btn btn-info w-100 mb-3" id="calculatePreviewBtn">
                            <i class="bi bi-calculator"></i> Calculate
                        </button>

                        <div id="previewResult" class="alert alert-secondary d-none">
                            <h6 class="mb-2">Calculation:</h6>
                            <p class="mb-2" id="previewCalculation">-</p>
                            <hr>
                            <h5 class="mb-0">Commission: <span id="previewAmount" class="text-success">-</span></h5>
                            <div id="previewLimits" class="mt-2 d-none">
                                <small class="text-muted">Limits applied</small>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <small>
                                <strong>Note:</strong> This is a preview calculation. 
                                Actual commission will be calculated when jobs are completed.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Update rate amount label based on calculation type
    function updateRateAmountLabel() {
        const calcType = $('#calculation_type').val();
        let label = 'Rate Amount (RM)';
        
        switch(calcType) {
            case 'flat':
                label = 'Rate Amount (RM)';
                break;
            case 'per_terminal':
                label = 'Rate Per Terminal (RM)';
                break;
            case 'percentage':
                label = 'Rate Percentage (%)';
                break;
        }
        
        $('#rate_amount_label').text(label);
    }

    $('#calculation_type').on('change', updateRateAmountLabel);
    updateRateAmountLabel();

    // Calculate Preview
    $('#calculatePreviewBtn').on('click', function() {
        const calculationType = $('#calculation_type').val();
        const rateAmount = parseFloat($('#rate_amount').val()) || 0;
        const minAmount = parseFloat($('#min_amount').val()) || null;
        const maxAmount = parseFloat($('#max_amount').val()) || null;
        const jobValue = parseFloat($('#test_job_value').val()) || 0;
        const terminalCount = parseInt($('#test_terminal_count').val()) || 1;

        if (!rateAmount) {
            toastr.warning('Please enter rate amount');
            return;
        }

        $.ajax({
            url: '{{ route('admin.rate-cards.calculate-preview') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                calculation_type: calculationType,
                rate_amount: rateAmount,
                min_amount: minAmount,
                max_amount: maxAmount,
                job_value: jobValue,
                terminal_count: terminalCount
            },
            success: function(response) {
                $('#previewCalculation').text(response.calculation);
                $('#previewAmount').text(response.formatted_amount);
                $('#previewResult').removeClass('d-none');
                
                if (response.limits_applied) {
                    $('#previewLimits').removeClass('d-none');
                } else {
                    $('#previewLimits').addClass('d-none');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to calculate preview');
            }
        });
    });

    // Form Validation
    $('#rateCardForm').on('submit', function(e) {
        const minAmount = parseFloat($('#min_amount').val()) || 0;
        const maxAmount = parseFloat($('#max_amount').val()) || 0;
        
        if (minAmount && maxAmount && minAmount >= maxAmount) {
            e.preventDefault();
            toastr.error('Minimum amount must be less than maximum amount');
            return false;
        }

        const calcType = $('#calculation_type').val();
        const rateAmount = parseFloat($('#rate_amount').val()) || 0;
        
        if (calcType === 'percentage' && rateAmount > 100) {
            e.preventDefault();
            toastr.error('Percentage rate cannot exceed 100%');
            return false;
        }

        $('#submitBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Creating...');
    });
});
</script>
@endpush
