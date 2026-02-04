@extends('layouts.app')

@section('title', 'Bulk Update Serials')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="bi bi-arrow-repeat"></i> Bulk Update Serials</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Update Settings</h5>
                </div>
                <div class="card-body">
                    <form id="bulkUpdateForm">
                        @csrf
                        <input type="hidden" name="serial_ids" value="{{ implode(',', $serials->pluck('id')->toArray()) }}">
                        
                        <div class="mb-3">
                            <label class="form-label">Operation Type</label>
                            <select class="form-select" name="operation" id="operationType" required>
                                <option value="">-- Select Operation --</option>
                                <option value="update_status">Update Status</option>
                                <option value="transfer">Transfer Location</option>
                            </select>
                        </div>

                        <!-- Status Update Fields -->
                        <div id="statusUpdateFields" class="d-none">
                            <div class="mb-3">
                                <label class="form-label">New Status</label>
                                <select class="form-select" name="new_status">
                                    <option value="">-- Select Status --</option>
                                    @foreach($statuses as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Transfer Fields -->
                        <div id="transferFields" class="d-none">
                            <div class="mb-3">
                                <label class="form-label">Destination Type</label>
                                <select class="form-select" name="to_location_type" id="locationType">
                                    <option value="">-- Select Type --</option>
                                    @foreach($locationTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3" id="depotField" style="display: none;">
                                <label class="form-label">Depot</label>
                                <select class="form-select" name="to_location_id_depot">
                                    <option value="">-- Select Depot --</option>
                                    @foreach($depots as $depot)
                                        <option value="{{ $depot->id }}">[{{ $depot->depot_code }}] {{ $depot->depot_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3" id="technicianField" style="display: none;">
                                <label class="form-label">Technician</label>
                                <select class="form-select" name="to_location_id_tech">
                                    <option value="">-- Select Technician --</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}">[{{ $tech->employee_id }}] {{ $tech->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="3" placeholder="Optional remarks about this bulk operation"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" onclick="previewChanges()">
                                <i class="bi bi-eye"></i> Preview Changes
                            </button>
                            <button type="button" class="btn btn-success" id="submitBtn" disabled>
                                <i class="bi bi-check-circle"></i> Apply Changes
                            </button>
                            <a href="{{ route('supervisor.bulk-serials.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Selected Serials ({{ count($serials) }})</h5>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <div class="list-group">
                        @foreach($serials as $serial)
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $serial->serial_no }}</h6>
                                    <small>{!! $serial->status_badge !!}</small>
                                </div>
                                <small class="text-muted">
                                    {{ $serial->terminalModel->model_name ?? 'Unknown' }}<br>
                                    <i class="bi bi-geo-alt"></i> {{ $serial->location_name }}
                                </small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Preview Changes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="confirmSubmit()">
                    <i class="bi bi-check-circle"></i> Confirm & Apply
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle fields based on operation type
    $('#operationType').change(function() {
        const operation = $(this).val();
        
        $('#statusUpdateFields').addClass('d-none');
        $('#transferFields').addClass('d-none');
        $('#submitBtn').prop('disabled', true);
        
        if (operation === 'update_status') {
            $('#statusUpdateFields').removeClass('d-none');
        } else if (operation === 'transfer') {
            $('#transferFields').removeClass('d-none');
        }
    });

    // Toggle location fields based on type
    $('#locationType').change(function() {
        const type = $(this).val();
        
        $('#depotField, #technicianField').hide();
        
        if (type === 'depot') {
            $('#depotField').show();
        } else if (type === 'technician') {
            $('#technicianField').show();
        }
    });
});

function previewChanges() {
    const operation = $('#operationType').val();
    
    if (!operation) {
        toastr.error('Please select an operation type');
        return;
    }

    // Validate required fields
    if (operation === 'update_status' && !$('[name="new_status"]').val()) {
        toastr.error('Please select a new status');
        return;
    }

    if (operation === 'transfer') {
        const locationType = $('[name="to_location_type"]').val();
        if (!locationType) {
            toastr.error('Please select a destination type');
            return;
        }

        const locationId = locationType === 'depot' 
            ? $('[name="to_location_id_depot"]').val() 
            : $('[name="to_location_id_tech"]').val();
            
        if (!locationId) {
            toastr.error('Please select a destination location');
            return;
        }
    }

    // Generate preview
    let html = '<div class="alert alert-warning">';
    html += '<h6><i class="bi bi-exclamation-triangle"></i> You are about to apply the following changes:</h6>';
    html += '<ul>';
    html += '<li><strong>Operation:</strong> ' + $('#operationType option:selected').text() + '</li>';
    html += '<li><strong>Serials Affected:</strong> {{ count($serials) }}</li>';
    
    if (operation === 'update_status') {
        html += '<li><strong>New Status:</strong> ' + $('[name="new_status"] option:selected').text() + '</li>';
    } else if (operation === 'transfer') {
        html += '<li><strong>Destination Type:</strong> ' + $('[name="to_location_type"] option:selected').text() + '</li>';
        const locType = $('[name="to_location_type"]').val();
        if (locType === 'depot') {
            html += '<li><strong>Destination:</strong> ' + $('[name="to_location_id_depot"] option:selected').text() + '</li>';
        } else {
            html += '<li><strong>Destination:</strong> ' + $('[name="to_location_id_tech"] option:selected').text() + '</li>';
        }
    }
    
    html += '</ul>';
    html += '<p class="mb-0"><strong>This action cannot be undone easily. Please confirm to proceed.</strong></p>';
    html += '</div>';

    $('#previewContent').html(html);
    $('#previewModal').modal('show');
    $('#submitBtn').prop('disabled', false);
}

function confirmSubmit() {
    const formData = new FormData($('#bulkUpdateForm')[0]);
    
    // Set correct location_id based on type
    const locationType = $('[name="to_location_type"]').val();
    if (locationType === 'depot') {
        formData.set('to_location_id', $('[name="to_location_id_depot"]').val());
    } else if (locationType === 'technician') {
        formData.set('to_location_id', $('[name="to_location_id_tech"]').val());
    }
    
    $.ajax({
        url: '{{ route("supervisor.bulk-serials.bulk-update") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#previewModal').modal('hide');
            
            if (response.success) {
                toastr.success(response.updated + ' serials updated successfully!');
                setTimeout(() => {
                    window.location.href = '{{ route("supervisor.bulk-serials.index") }}';
                }, 1500);
            } else {
                toastr.error(response.message || 'Update failed');
            }
        },
        error: function(xhr) {
            $('#previewModal').modal('hide');
            toastr.error('Update failed. Please try again.');
        }
    });
}
</script>
@endpush
@endsection
