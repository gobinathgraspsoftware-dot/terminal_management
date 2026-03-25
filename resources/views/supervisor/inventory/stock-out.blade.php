@extends('layouts.app')
@section('title', 'Stock Out')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-arrow-right text-danger me-2"></i>Stock Out</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Stock Out</li>
                </ol>
            </nav>
        </div>
    </div>

    @php $roleName = explode('.', Route::currentRouteName())[0]; @endphp

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="alert alert-warning d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <small>Stock Out must be linked with a Ticket. Select the ticket, then choose router / accessories to deduct.</small>
            </div>

            <form action="{{ route($roleName . '.inventory.stock-out.process') }}" method="POST" id="stockOutForm">
                @csrf
                <div class="row g-3">
                    {{-- Ticket Selection --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Link to Ticket</label>
                        <select name="ticket_id" id="ticket_select" class="form-select select2">
                            <option value="">-- Select Ticket (Optional) --</option>
                            @foreach($tickets as $ticket)
                                <option value="{{ $ticket->id }}" data-technician="{{ $ticket->technician_id }}">
                                    {{ $ticket->ticket_no }} - {{ $ticket->merchant_name ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Linking to a ticket will auto-populate the technician.</div>
                    </div>

                    {{-- Technician --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Assign to Technician</label>
                        <select name="technician_id" id="technician_select" class="form-select select2">
                            <option value="">-- Select Technician (Optional) --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->employee_id }} - {{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Item Type Toggle --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Item Category <span class="text-danger">*</span></label>
                        <select id="stock_out_type" class="form-select">
                            <option value="router">Router</option>
                            <option value="accessory">Accessory</option>
                        </select>
                    </div>

                    {{-- Router Selection --}}
                    <div class="col-md-8" id="router_select_wrapper">
                        <label class="form-label fw-semibold">Select Router (Terminal ID) <span class="text-danger">*</span></label>
                        <select name="inventory_item_id" id="router_out_select" class="form-select select2">
                            <option value="">-- Choose Router by Terminal ID --</option>
                            @foreach($availableRouters as $router)
                                <option value="{{ $router->id }}">
                                    {{ $router->serial_number }} — {{ $router->item_name }}
                                    (Stock: {{ $router->stockBalances->first()->quantity ?? 0 }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Accessory Selection --}}
                    <div class="col-md-5" id="accessory_select_wrapper" style="display:none;">
                        <label class="form-label fw-semibold">Select Accessory <span class="text-danger">*</span></label>
                        <select name="inventory_item_id" id="accessory_out_select" class="form-select select2" disabled>
                            <option value="">-- Choose Accessory --</option>
                            @foreach($availableAccessories as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->item_name }}
                                    ({{ $acc->accessory_type === 'sim_card' ? 'SIM' : 'Antenna' }} — Stock: {{ $acc->stockBalances->first()->quantity ?? 0 }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3" id="qty_wrapper" style="display:none;">
                        <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="stock_out_qty" class="form-control" value="1" min="1">
                        <div class="form-text">One ticket = one item / one quantity.</div>
                    </div>

                    {{-- Hidden qty for router (always 1) --}}
                    <input type="hidden" id="router_qty" name="" value="1">

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Movement Date</label>
                        <input type="date" name="movement_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Reason</label>
                        <input type="text" name="reason" class="form-control" value="Stock Out for Ticket" placeholder="Reason for stock out">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
                    </div>
                </div>

                <hr class="my-4">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right me-1"></i> Process Stock Out
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

    // When ticket changes, auto-fill technician
    $('#ticket_select').on('change.select2', function() {
        var techId = $(this).find(':selected').data('technician');
        if (techId) {
            $('#technician_select').val(techId).trigger('change.select2');
        }
    });

    // Toggle router vs accessory fields
    $('#stock_out_type').on('change', function() {
        var val = $(this).val();
        if (val === 'router') {
            $('#router_select_wrapper').show();
            $('#router_out_select').prop('disabled', false);
            $('#accessory_select_wrapper, #qty_wrapper').hide();
            $('#accessory_out_select').prop('disabled', true).val('').trigger('change.select2');
            // Router uses hidden qty = 1, remove name from visible qty
            $('#stock_out_qty').removeAttr('name');
            $('#router_qty').attr('name', 'quantity');
            // Set item name to router select
            $('#router_out_select').attr('name', 'inventory_item_id');
            $('#accessory_out_select').removeAttr('name');
        } else {
            $('#router_select_wrapper').hide();
            $('#router_out_select').prop('disabled', true).val('').trigger('change.select2');
            $('#accessory_select_wrapper, #qty_wrapper').show();
            $('#accessory_out_select').prop('disabled', false);
            $('#stock_out_qty').attr('name', 'quantity');
            $('#router_qty').removeAttr('name');
            $('#accessory_out_select').attr('name', 'inventory_item_id');
            $('#router_out_select').removeAttr('name');
        }
    });

    // Init
    $('#stock_out_type').trigger('change');
});
</script>
@endpush
