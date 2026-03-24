@extends('layouts.app')
@section('title', isset($mode) && $mode === 'return' ? 'Stock Return' : 'Stock Out')

@php
    $roleName = explode('.', Route::currentRouteName())[0];
    $isReturn = isset($mode) && $mode === 'return';
    $pageTitle = $isReturn ? 'Stock Return' : 'Stock Out';
    $headerColor = $isReturn ? 'info' : 'danger';
    $headerIcon = $isReturn ? 'bi-box-arrow-up' : 'bi-box-arrow-right';
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi {{ $headerIcon }} me-2 text-{{ $headerColor }}"></i>{{ $pageTitle }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route($roleName . '.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route($roleName . '.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">{{ $pageTitle }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-{{ $headerColor }} text-white">
                    <h6 class="mb-0">
                        <i class="bi {{ $headerIcon }} me-2"></i>
                        {{ $isReturn ? 'Return Stock from Technician to Warehouse' : 'Issue Stock from Warehouse to Technician' }}
                    </h6>
                </div>
                <div class="card-body">
                    <form id="stock-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Inventory Item <span class="text-danger">*</span></label>
                                <select name="inventory_item_id" id="inventory_item_id" class="form-select select2-field" required>
                                    <option value="">Select Item</option>
                                    @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-type="{{ $item->item_type }}">
                                        {{ $item->item_code }} - {{ $item->item_name }}
                                        @if($item->serial_number) ({{ $item->serial_number }}) @endif
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Technician <span class="text-danger">*</span></label>
                                <select name="technician_id" id="technician_id" class="form-select select2-field" required>
                                    <option value="">Select Technician</option>
                                    @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}">{{ $tech->name }} ({{ $tech->employee_id ?? $tech->email }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Available Stock</label>
                                <input type="text" id="available-stock" class="form-control" readonly value="-">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Movement Date <span class="text-danger">*</span></label>
                                <input type="date" name="movement_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>

                            @if(!$isReturn)
                            <div class="col-md-3">
                                <label class="form-label">Linked Ticket</label>
                                <input type="text" name="ticket_id" class="form-control" placeholder="Ticket ID (optional)">
                            </div>
                            @endif

                            <div class="col-md-12">
                                <label class="form-label">Reason</label>
                                <input type="text" name="reason" class="form-control" placeholder="{{ $isReturn ? 'e.g., Unused, Faulty, Job completed' : 'e.g., Job assignment, Replacement' }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-{{ $headerColor }}" id="btn-submit">
                                <i class="bi {{ $headerIcon }} me-1"></i> Process {{ $pageTitle }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Technician Stock Panel -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm" id="tech-stock-panel" style="display:none;">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Technician Stock</h6>
                </div>
                <div class="card-body" id="tech-stock-content">
                </div>
            </div>

            <div class="card border-0 shadow-sm bg-light mt-3">
                <div class="card-body">
                    <h6><i class="bi bi-question-circle me-2"></i>{{ $pageTitle }} Help</h6>
                    <hr>
                    @if($isReturn)
                    <p class="small text-muted mb-2">Stock Return moves items from <strong>Technician</strong> back to <strong>Warehouse</strong>.</p>
                    <p class="small text-muted mb-0">Accessories can be returned if unused (via stock return). One ticket = one item / one quantity.</p>
                    @else
                    <p class="small text-muted mb-2">Stock Out issues items from <strong>Warehouse</strong> to a <strong>Technician</strong>.</p>
                    <p class="small text-muted mb-0">For Routers, quantity is typically 1 (individually tracked). Accessories can be issued in bulk.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var isReturn = {{ $isReturn ? 'true' : 'false' }};

    $('.select2-field').select2({ theme: 'bootstrap-5', width: '100%' });

    function loadStock() {
        let itemId = $('#inventory_item_id').val();
        let techId = $('#technician_id').val();

        if (!itemId) { $('#available-stock').val('-'); return; }

        $.get('{{ route($roleName . ".inventory.get-item-stock") }}', { item_id: itemId }, function(res) {
            if (res.success) {
                if (isReturn && techId) {
                    // Show technician's stock for return
                    let techStock = res.tech_stock.find(t => t.technician_id == techId);
                    $('#available-stock').val(techStock ? techStock.quantity : 0);
                } else {
                    // Show warehouse stock for stock out
                    $('#available-stock').val(res.warehouse_stock);
                }

                // Router = qty 1
                if (res.item_type === 'router') {
                    $('input[name="quantity"]').val(1).prop('max', 1);
                } else {
                    $('input[name="quantity"]').prop('max', '');
                }

                // Show tech stock panel
                if (res.tech_stock && res.tech_stock.length > 0) {
                    let html = '<table class="table table-sm mb-0"><thead><tr><th>Technician</th><th class="text-center">Qty</th></tr></thead><tbody>';
                    res.tech_stock.forEach(function(t) {
                        html += '<tr><td>' + t.technician_name + '</td><td class="text-center">' + t.quantity + '</td></tr>';
                    });
                    html += '</tbody></table>';
                    $('#tech-stock-content').html(html);
                    $('#tech-stock-panel').show();
                } else {
                    $('#tech-stock-panel').hide();
                }
            }
        });
    }

    $('#inventory_item_id').on('change.select2', loadStock);
    $('#technician_id').on('change.select2', loadStock);

    // Form submit
    $('#stock-form').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btn-submit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

        let url = isReturn
            ? '{{ route($roleName . ".inventory.stock-return.process") }}'
            : '{{ route($roleName . ".inventory.stock-out.process") }}';

        $.ajax({
            url: url,
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                if (res.success) {
                    showToast('success', res.message);
                    setTimeout(function() { location.reload(); }, 1500);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi {{ $headerIcon }} me-1"></i> Process {{ $pageTitle }}');
                showToast('error', xhr.responseJSON?.message || '{{ $pageTitle }} failed.');
            }
        });
    });
});
</script>
@endpush
