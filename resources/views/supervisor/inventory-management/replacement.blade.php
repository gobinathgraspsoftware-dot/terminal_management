@extends('layouts.app')

@section('title', 'Device Replacement')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-arrow-left-right text-warning me-2"></i>Device Replacement</h4>
            <p class="text-muted mb-0">Swap old device with new unit for your team</p>
        </div>
        <a href="{{ route('supervisor.inventory-management.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="replacementForm">
                @csrf
                <h6 class="fw-semibold mb-3 text-primary"><i class="bi bi-1-circle me-1"></i> Context</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="replacement_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ticket</label>
                        <select name="ticket_id" id="ticketSelect" class="form-select select2-ticket">
                            <option value="">Optional</option>
                            @foreach($tickets as $t)
                            <option value="{{ $t->id }}" data-router="{{ $t->router_id }}" data-tech="{{ $t->technician_id }}">{{ $t->ticket_no }} - {{ $t->merchant_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Technician</label>
                        <select name="technician_id" id="techSelect" class="form-select select2-tech">
                            <option value="">Select</option>
                            @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Site</label>
                        <select name="site_id" class="form-select select2-site">
                            <option value="">Select</option>
                            @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->site_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h6 class="fw-semibold mb-3 text-danger"><i class="bi bi-2-circle me-1"></i> Old Device</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Old Serial No</label>
                        <input type="text" name="old_serial_no" id="oldSerialNo" class="form-control">
                        <input type="hidden" name="old_serial_id" id="oldSerialId">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Model</label>
                        <select name="old_model_id" class="form-select">
                            <option value="">Select</option>
                            @foreach($models as $m)<option value="{{ $m->id }}">{{ $m->model_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Condition <span class="text-danger">*</span></label>
                        <select name="old_device_condition" class="form-select" required>
                            <option value="damaged">Damaged</option><option value="defective">Defective</option>
                            <option value="good">Good</option><option value="wasted">Wasted</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Destination <span class="text-danger">*</span></label>
                        <select name="old_device_destination" id="oldDest" class="form-select" required>
                            @foreach(\App\Models\Replacement::DESTINATION_OPTIONS as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-4" id="returnDepotRow">
                    <div class="col-md-3">
                        <label class="form-label">Return Depot</label>
                        <select name="return_depot_id" class="form-select">
                            <option value="">Select</option>
                            @foreach($depots as $d)<option value="{{ $d->id }}">{{ $d->depot_name }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <h6 class="fw-semibold mb-3 text-success"><i class="bi bi-3-circle me-1"></i> New Device</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Model <span class="text-danger">*</span></label>
                        <select name="new_model_id" id="newModelSelect" class="form-select" required>
                            <option value="">Select</option>
                            @foreach($models as $m)<option value="{{ $m->id }}">{{ $m->model_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Serial <span class="text-danger">*</span></label>
                        <select name="new_serial_id" id="newSerialSelect" class="form-select" required>
                            <option value="">Select model first</option>
                        </select>
                        <input type="hidden" name="new_serial_no" id="newSerialNo">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="saveBtn"><i class="bi bi-arrow-left-right me-1"></i> Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2-ticket, .select2-tech, .select2-site').select2({ theme: 'bootstrap-5', width: '100%' });

    $('#ticketSelect').on('change', function() {
        let opt = $(this).find(':selected');
        if (opt.data('router')) $('#oldSerialNo').val(opt.data('router'));
        if (opt.data('tech')) $('#techSelect').val(opt.data('tech')).trigger('change.select2');
    });

    $('#oldDest').on('change', function() { $('#returnDepotRow').toggleClass('d-none', $(this).val() !== 'return_to_depot'); });

    $('#newModelSelect').on('change', function() {
        let modelId = $(this).val();
        if (!modelId) return;
        let params = { model_id: modelId };
        let techId = $('#techSelect').val();
        if (techId) params.technician_id = techId;

        $.get('{{ route("supervisor.inventory-management.available-serials") }}', params, function(res) {
            let opts = '<option value="">Select</option>';
            res.serials.forEach(s => opts += `<option value="${s.id}" data-sn="${s.serial_no}">${s.serial_no}</option>`);
            $('#newSerialSelect').html(opts);
        });
    });

    $('#newSerialSelect').on('change', function() { $('#newSerialNo').val($(this).find(':selected').data('sn') || ''); });

    $('#replacementForm').on('submit', function(e) {
        e.preventDefault();
        $('#saveBtn').prop('disabled', true);
        $.ajax({
            url: '{{ route("supervisor.inventory-management.replacement.store") }}',
            method: 'POST', data: new FormData(this), processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) { if (res.success) { showToast(res.message, 'success'); setTimeout(() => window.location.href = '{{ route("supervisor.inventory-management.index") }}', 1500); } },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error', 'error'); },
            complete: function() { $('#saveBtn').prop('disabled', false); }
        });
    });
});
</script>
@endpush
