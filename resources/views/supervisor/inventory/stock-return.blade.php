@extends('layouts.app')
@section('title', 'Stock Return')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-arrow-up text-info me-2"></i>Stock Return</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Stock Return</li>
                </ol>
            </nav>
        </div>
    </div>

    @php $roleName = explode('.', Route::currentRouteName())[0]; @endphp

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="alert alert-info d-flex align-items-center mb-4">
                <i class="bi bi-info-circle me-2"></i>
                <small>Return items back into warehouse inventory. Examples: faulty router returned, unused SIM card returned, unused antenna returned.</small>
            </div>

            <form action="{{ route($roleName . '.inventory.stock-return.process') }}" method="POST" id="stockReturnForm">
                @csrf
                <div class="row g-3">
                    {{-- Return From --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Return From <span class="text-danger">*</span></label>
                        <select name="from_holder_type" id="from_holder_type" class="form-select" required>
                            <option value="technician">Technician</option>
                            <option value="warehouse">External / Direct Return</option>
                        </select>
                    </div>

                    <div class="col-md-4" id="technician_wrapper">
                        <label class="form-label fw-semibold">Technician <span class="text-danger">*</span></label>
                        <select name="from_holder_id" id="from_holder_id" class="form-select select2">
                            <option value="">-- Select Technician --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->employee_id }} - {{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Item --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Inventory Item <span class="text-danger">*</span></label>
                        <select name="inventory_item_id" id="return_item_select" class="form-select select2" required>
                            <option value="">-- Select Item --</option>
                            @foreach($allItems as $item)
                                <option value="{{ $item->id }}" data-type="{{ $item->item_type }}">
                                    {{ $item->item_code }} - {{ $item->item_name }}
                                    @if($item->serial_number) ({{ $item->serial_number }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Quantity --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="return_qty" class="form-control" value="1" min="1" required>
                        <div class="form-text" id="qty_note">For routers, quantity is always 1.</div>
                    </div>

                    {{-- Condition --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Item Condition <span class="text-danger">*</span></label>
                        <select name="item_condition" class="form-select" required>
                            <option value="good">Good</option>
                            <option value="faulty">Faulty</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Movement Date</label>
                        <input type="date" name="movement_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Link to Ticket</label>
                        <input type="text" name="ticket_id" class="form-control" placeholder="Ticket ID (optional)">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g., Faulty router returned by technician" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </div>

                <hr class="my-4">
                <button type="submit" class="btn btn-info">
                    <i class="bi bi-box-arrow-up me-1"></i> Process Stock Return
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true, placeholder: '-- Select --' });

    // Toggle technician field
    $('#from_holder_type').on('change', function() {
        if ($(this).val() === 'technician') {
            $('#technician_wrapper').show();
            $('#from_holder_id').attr('required', true);
        } else {
            $('#technician_wrapper').hide();
            $('#from_holder_id').val('').trigger('change.select2').removeAttr('required');
        }
    });

    // Lock qty to 1 for routers
    $('#return_item_select').on('change.select2', function() {
        var type = $(this).find(':selected').data('type');
        if (type === 'router') {
            $('#return_qty').val(1).attr('readonly', true);
            $('#qty_note').text('For routers, quantity is always 1.');
        } else {
            $('#return_qty').removeAttr('readonly');
            $('#qty_note').text('Enter quantity to return.');
        }
    });
});
</script>
@endpush
