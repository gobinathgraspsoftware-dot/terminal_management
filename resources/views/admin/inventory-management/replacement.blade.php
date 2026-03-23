@extends('layouts.app')

@section('title', 'Replacement - Inventory Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-arrow-left-right text-warning me-2"></i>Device Replacement</h4>
            <p class="text-muted mb-0">Swap old device with new unit — updates ticket &amp; inventory</p>
        </div>
        <a href="{{ route('admin.inventory-management.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Hub
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="replacementForm">
                @csrf
                {{-- Step 1: Context --}}
                <h6 class="fw-semibold mb-3 text-primary"><i class="bi bi-1-circle me-1"></i> Context</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Replacement Date <span class="text-danger">*</span></label>
                        <input type="date" name="replacement_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Linked Ticket</label>
                        <select name="ticket_id" id="ticketSelect" class="form-select select2-ticket">
                            <option value="">Select Ticket (optional)</option>
                            @foreach($tickets as $ticket)
                            <option value="{{ $ticket->id }}" data-router="{{ $ticket->router_id }}" data-terminal="{{ $ticket->terminal_id }}" data-tech="{{ $ticket->technician_id }}">
                                {{ $ticket->ticket_no }} - {{ $ticket->merchant_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Technician</label>
                        <select name="technician_id" id="techSelect" class="form-select select2-tech">
                            <option value="">Select Technician</option>
                            @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Site</label>
                        <select name="site_id" class="form-select select2-site">
                            <option value="">Select Site</option>
                            @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Step 2: Old Device --}}
                <h6 class="fw-semibold mb-3 text-danger"><i class="bi bi-2-circle me-1"></i> Old Device (Being Removed)</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Old Serial No</label>
                        <input type="text" name="old_serial_no" id="oldSerialNo" class="form-control" placeholder="Enter or auto-fill from ticket">
                        <input type="hidden" name="old_serial_id" id="oldSerialId">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Old Model</label>
                        <select name="old_model_id" class="form-select select2-model">
                            <option value="">Select Model</option>
                            @foreach($models as $model)
                            <option value="{{ $model->id }}">{{ $model->model_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Condition <span class="text-danger">*</span></label>
                        <select name="old_device_condition" class="form-select" required>
                            <option value="damaged">Damaged</option>
                            <option value="defective">Defective</option>
                            <option value="good">Good</option>
                            <option value="wasted">Wasted</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Destination <span class="text-danger">*</span></label>
                        <select name="old_device_destination" id="oldDestination" class="form-select" required>
                            @foreach(\App\Models\Replacement::DESTINATION_OPTIONS as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-4" id="returnDepotRow">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Return to Depot</label>
                        <select name="return_depot_id" class="form-select select2-depot">
                            <option value="">Select Depot</option>
                            @foreach($depots as $depot)
                            <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Step 3: New Device --}}
                <h6 class="fw-semibold mb-3 text-success"><i class="bi bi-3-circle me-1"></i> New Device (Being Installed)</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">New Model <span class="text-danger">*</span></label>
                        <select name="new_model_id" id="newModelSelect" class="form-select select2-model" required>
                            <option value="">Select Model</option>
                            @foreach($models as $model)
                            <option value="{{ $model->id }}">{{ $model->model_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">New Serial <span class="text-danger">*</span></label>
                        <select name="new_serial_id" id="newSerialSelect" class="form-select" required>
                            <option value="">Select model first</option>
                        </select>
                        <input type="hidden" name="new_serial_no" id="newSerialNo">
                    </div>
                </div>

                {{-- Reason --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="2" required placeholder="Why is this device being replaced?"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="saveRplBtn">
                        <i class="bi bi-arrow-left-right me-1"></i> Create Replacement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2-ticket, .select2-tech, .select2-site, .select2-model, .select2-depot').select2({ theme: 'bootstrap-5', width: '100%' });

    // Auto-fill from ticket
    $('#ticketSelect').on('change', function() {
        let opt = $(this).find(':selected');
        let routerId = opt.data('router');
        let techId = opt.data('tech');

        if (routerId) $('#oldSerialNo').val(routerId);
        if (techId) $('#techSelect').val(techId).trigger('change.select2');

        // Look up serial ID
        if (routerId) {
            // Try to find serial by number
            $.get('{{ route("admin.inventory-management.available-serials") }}', { serial_no: routerId }, function(res) {
                if (res.serials && res.serials.length > 0) {
                    $('#oldSerialId').val(res.serials[0].id);
                }
            });
        }
    });

    // Toggle return depot row
    $('#oldDestination').on('change', function() {
        $('#returnDepotRow').toggleClass('d-none', $(this).val() !== 'return_to_depot');
    });

    // Load available serials for new device
    $('#newModelSelect').on('change', function() {
        let modelId = $(this).val();
        let techId = $('#techSelect').val();

        if (!modelId) return;

        let params = { model_id: modelId };
        if (techId) params.technician_id = techId;

        $.get('{{ route("admin.inventory-management.available-serials") }}', params, function(res) {
            let opts = '<option value="">Select Serial</option>';
            res.serials.forEach(s => opts += `<option value="${s.id}" data-sn="${s.serial_no}">${s.serial_no}</option>`);
            $('#newSerialSelect').html(opts);
        });
    });

    $('#newSerialSelect').on('change', function() {
        $('#newSerialNo').val($(this).find(':selected').data('sn') || '');
    });

    // Submit
    $('#replacementForm').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        $('#saveRplBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

        $.ajax({
            url: '{{ route("admin.inventory-management.replacement.store") }}',
            method: 'POST', data: formData, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(() => window.location.href = '{{ route("admin.inventory-management.index") }}', 1500);
                }
            },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error', 'error'); },
            complete: function() { $('#saveRplBtn').prop('disabled', false).html('<i class="bi bi-arrow-left-right me-1"></i> Create Replacement'); }
        });
    });
});
</script>
@endpush
