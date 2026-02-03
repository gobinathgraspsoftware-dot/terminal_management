@extends('layouts.app')

@section('title', 'Add Serial Number - Admin')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-plus-circle me-2"></i>Add Serial Number</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory-serials.index') }}">Serial Tracking</a></li>
                    <li class="breadcrumb-item active">Add New</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.inventory-serials.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<form id="createSerialForm" action="{{ route('admin.inventory-serials.store') }}" method="POST">
    @csrf
    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <!-- Serial & Model Info -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-upc-scan me-2"></i>Serial Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="serial_no" class="form-label fw-bold">Serial Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('serial_no') is-invalid @enderror"
                                   id="serial_no" name="serial_no" value="{{ old('serial_no') }}"
                                   placeholder="Enter unique serial number" required autofocus>
                            @error('serial_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="serialValidation" class="form-text"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="model_id" class="form-label fw-bold">Terminal Model <span class="text-danger">*</span></label>
                            <select class="form-select @error('model_id') is-invalid @enderror"
                                    id="model_id" name="model_id" required>
                                <option value="">-- Select Model --</option>
                                @foreach($models as $model)
                                    <option value="{{ $model->id }}" {{ old('model_id') == $model->id ? 'selected' : '' }}>
                                        {{ $model->model_name }} ({{ $model->model_code ?? '' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('model_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="hardware_type" class="form-label">Hardware Type</label>
                            <input type="text" class="form-control" id="hardware_type" name="hardware_type"
                                   value="{{ old('hardware_type') }}" placeholder="e.g., POS, EDC">
                        </div>
                        <div class="col-md-4">
                            <label for="device_type" class="form-label">Device Type</label>
                            <input type="text" class="form-control" id="device_type" name="device_type"
                                   value="{{ old('device_type') }}" placeholder="e.g., Wired, Wireless">
                        </div>
                        <div class="col-md-4">
                            <label for="purchase_price" class="form-label">Purchase Price (RM)</label>
                            <input type="number" class="form-control" id="purchase_price" name="purchase_price"
                                   value="{{ old('purchase_price') }}" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SIM / Telco Info -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-sim me-2"></i>SIM / Telco Information (Optional)</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="telco" class="form-label">Telco Provider</label>
                            <input type="text" class="form-control" id="telco" name="telco"
                                   value="{{ old('telco') }}" placeholder="e.g., Celcom, Maxis">
                        </div>
                        <div class="col-md-6">
                            <label for="sim_quota" class="form-label">SIM Quota</label>
                            <input type="text" class="form-control" id="sim_quota" name="sim_quota"
                                   value="{{ old('sim_quota') }}" placeholder="e.g., 1GB/month">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warranty Info -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-shield-check me-2"></i>Warranty Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="warranty_start" class="form-label">Warranty Start</label>
                            <input type="date" class="form-control" id="warranty_start" name="warranty_start"
                                   value="{{ old('warranty_start') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="warranty_end" class="form-label">Warranty End</label>
                            <input type="date" class="form-control" id="warranty_end" name="warranty_end"
                                   value="{{ old('warranty_end') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-4">
            <!-- Status & Location -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-geo me-2"></i>Status & Location</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="current_status" class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('current_status') is-invalid @enderror"
                                id="current_status" name="current_status" required>
                            @foreach(\App\Models\InventorySerial::STATUS_OPTIONS as $key => $label)
                                <option value="{{ $key }}" {{ old('current_status', 'in_stock') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('current_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="current_location_type" class="form-label fw-bold">Location Type <span class="text-danger">*</span></label>
                        <select class="form-select @error('current_location_type') is-invalid @enderror"
                                id="current_location_type" name="current_location_type" required>
                            @foreach(\App\Models\InventorySerial::LOCATION_TYPE_OPTIONS as $key => $label)
                                <option value="{{ $key }}" {{ old('current_location_type', 'depot') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('current_location_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3" id="depotSelector">
                        <label for="current_location_id" class="form-label fw-bold">Location <span class="text-danger">*</span></label>
                        <select class="form-select @error('current_location_id') is-invalid @enderror"
                                id="current_location_id" name="current_location_id" required>
                            <option value="">-- Select Location --</option>
                            @foreach($depots as $depot)
                                <option value="{{ $depot->id }}" {{ old('current_location_id') == $depot->id ? 'selected' : '' }}>
                                    {{ $depot->depot_name }} ({{ $depot->depot_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('current_location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Procurement Reference -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-receipt me-2"></i>Procurement Reference</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="grn_id" class="form-label">GRN</label>
                        <select class="form-select" id="grn_id" name="grn_id">
                            <option value="">-- None --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="grn_date" class="form-label">GRN Date</label>
                        <input type="date" class="form-control" id="grn_date" name="grn_date"
                               value="{{ old('grn_date') }}">
                    </div>
                    <div class="mb-3">
                        <label for="po_id" class="form-label">Purchase Order</label>
                        <select class="form-select" id="po_id" name="po_id">
                            <option value="">-- None --</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-chat-text me-2"></i>Remarks</div>
                <div class="card-body">
                    <textarea class="form-control" id="remarks" name="remarks" rows="3"
                              placeholder="Optional notes...">{{ old('remarks') }}</textarea>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit">
                    <i class="bi bi-check-lg me-1"></i> Create Serial
                </button>
                <a href="{{ route('admin.inventory-serials.index') }}" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Real-time serial number uniqueness validation
    var serialTimer;
    $('#serial_no').on('input', function() {
        clearTimeout(serialTimer);
        var val = $(this).val().trim();
        var feedback = $('#serialValidation');

        if (val.length < 3) {
            feedback.html('').removeClass('text-success text-danger');
            return;
        }

        serialTimer = setTimeout(function() {
            $.ajax({
                url: '{{ route("api.serials.validate") }}',
                data: { serial_no: val },
                success: function(response) {
                    if (response.exists) {
                        feedback.html('<i class="bi bi-x-circle"></i> Serial number already exists!')
                                .removeClass('text-success').addClass('text-danger');
                        $('#serial_no').addClass('is-invalid');
                    } else {
                        feedback.html('<i class="bi bi-check-circle"></i> Serial number is available.')
                                .removeClass('text-danger').addClass('text-success');
                        $('#serial_no').removeClass('is-invalid');
                    }
                }
            });
        }, 500);
    });

    // Form AJAX submission
    $('#createSerialForm').on('submit', function(e) {
        e.preventDefault();

        var btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(function() {
                        window.location.href = '{{ route("admin.inventory-serials.index") }}';
                    }, 1000);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Create Serial');

                if (xhr.status === 422) {
                    var errors = xhr.responseJSON?.errors || {};
                    Object.keys(errors).forEach(function(field) {
                        var input = $('[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(errors[field][0]);
                    });
                    showToast('Please fix the validation errors.', 'error');
                } else {
                    showToast(xhr.responseJSON?.message || 'Failed to create serial.', 'error');
                }
            }
        });
    });

    // Initialize Select2 for model
    $('#model_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Select Model --',
        allowClear: true
    });
});
</script>
@endpush
