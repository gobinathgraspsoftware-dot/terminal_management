@extends('layouts.app')

@section('title', 'Edit Serial - ' . $serial->serial_no)

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-pencil me-2"></i>Edit Serial: {{ $serial->serial_no }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory-serials.index') }}">Serial Tracking</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory-serials.show', $serial->id) }}">{{ $serial->serial_no }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.inventory-serials.show', $serial->id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<form id="editSerialForm" action="{{ route('admin.inventory-serials.update', $serial->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-upc-scan me-2"></i>Serial Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="serial_no" class="form-label fw-bold">Serial Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('serial_no') is-invalid @enderror"
                                   id="serial_no" name="serial_no" value="{{ old('serial_no', $serial->serial_no) }}" required>
                            @error('serial_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="model_id" class="form-label fw-bold">Terminal Model <span class="text-danger">*</span></label>
                            <select class="form-select @error('model_id') is-invalid @enderror"
                                    id="model_id" name="model_id" required>
                                <option value="">-- Select Model --</option>
                                @foreach($models as $model)
                                    <option value="{{ $model->id }}" {{ old('model_id', $serial->model_id) == $model->id ? 'selected' : '' }}>
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
                                   value="{{ old('hardware_type', $serial->hardware_type) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="device_type" class="form-label">Device Type</label>
                            <input type="text" class="form-control" id="device_type" name="device_type"
                                   value="{{ old('device_type', $serial->device_type) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="purchase_price" class="form-label">Purchase Price (RM)</label>
                            <input type="number" class="form-control" id="purchase_price" name="purchase_price"
                                   value="{{ old('purchase_price', $serial->purchase_price) }}" step="0.01" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-sim me-2"></i>SIM / Telco</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="telco" class="form-label">Telco</label>
                            <input type="text" class="form-control" id="telco" name="telco"
                                   value="{{ old('telco', $serial->telco) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="sim_quota" class="form-label">SIM Quota</label>
                            <input type="text" class="form-control" id="sim_quota" name="sim_quota"
                                   value="{{ old('sim_quota', $serial->sim_quota) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-shield-check me-2"></i>Warranty</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="warranty_start" class="form-label">Warranty Start</label>
                            <input type="date" class="form-control" id="warranty_start" name="warranty_start"
                                   value="{{ old('warranty_start', $serial->warranty_start?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="warranty_end" class="form-label">Warranty End</label>
                            <input type="date" class="form-control" id="warranty_end" name="warranty_end"
                                   value="{{ old('warranty_end', $serial->warranty_end?->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-geo me-2"></i>Status & Location</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="current_status" required>
                            @foreach(\App\Models\InventorySerial::STATUS_OPTIONS as $key => $label)
                                <option value="{{ $key }}" {{ old('current_status', $serial->current_status) == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Location Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="current_location_type" required>
                            @foreach(\App\Models\InventorySerial::LOCATION_TYPE_OPTIONS as $key => $label)
                                <option value="{{ $key }}" {{ old('current_location_type', $serial->current_location_type) == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Location ID <span class="text-danger">*</span></label>
                        <select class="form-select" name="current_location_id" id="current_location_id" required>
                            <option value="">-- Select --</option>
                            @foreach($depots as $depot)
                                <option value="{{ $depot->id }}" {{ old('current_location_id', $serial->current_location_id) == $depot->id ? 'selected' : '' }}>
                                    {{ $depot->depot_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-receipt me-2"></i>Procurement</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">GRN</label>
                        <input type="text" class="form-control" value="{{ $serial->grn?->grn_no ?? '-' }}" disabled>
                        <input type="hidden" name="grn_id" value="{{ $serial->grn_id }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GRN Date</label>
                        <input type="date" class="form-control" name="grn_date"
                               value="{{ old('grn_date', $serial->grn_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PO</label>
                        <input type="text" class="form-control" value="{{ $serial->purchaseOrder?->po_no ?? '-' }}" disabled>
                        <input type="hidden" name="po_id" value="{{ $serial->po_id }}">
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-chat-text me-2"></i>Remarks</div>
                <div class="card-body">
                    <textarea class="form-control" name="remarks" rows="3">{{ old('remarks', $serial->remarks) }}</textarea>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit">
                    <i class="bi bi-check-lg me-1"></i> Update Serial
                </button>
                <a href="{{ route('admin.inventory-serials.show', $serial->id) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#model_id').select2({ theme: 'bootstrap-5', placeholder: '-- Select Model --', allowClear: true });

    $('#editSerialForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(function() {
                        window.location.href = '{{ route("admin.inventory-serials.show", $serial->id) }}';
                    }, 1000);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Update Serial');
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON?.errors || {};
                    Object.keys(errors).forEach(function(field) {
                        $('[name="' + field + '"]').addClass('is-invalid')
                            .siblings('.invalid-feedback').text(errors[field][0]);
                    });
                    showToast('Please fix the validation errors.', 'error');
                } else {
                    showToast(xhr.responseJSON?.message || 'Update failed.', 'error');
                }
            }
        });
    });
});
</script>
@endpush
