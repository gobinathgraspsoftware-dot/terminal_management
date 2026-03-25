@extends('layouts.app')
@section('title', 'Stock In')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-arrow-in-down text-success me-2"></i>Stock In</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('supervisor.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Stock In</li>
                </ol>
            </nav>
        </div>
    </div>

    @php $roleName = explode('.', Route::currentRouteName())[0]; @endphp

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            {{-- Stock Type Selection --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Stock Category <span class="text-danger">*</span></label>
                    <div class="d-flex gap-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="stock_type_toggle" id="type_router" value="router" checked>
                            <label class="form-check-label fw-semibold" for="type_router">
                                <i class="bi bi-router text-primary"></i> Router
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="stock_type_toggle" id="type_accessory" value="accessory">
                            <label class="form-check-label fw-semibold" for="type_accessory">
                                <i class="bi bi-sim text-info"></i> Accessory
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════ ROUTER FORM ═══════════ --}}
            <form action="{{ route($roleName . '.inventory.stock-in.process') }}" method="POST" id="routerStockInForm">
                @csrf
                <input type="hidden" name="stock_type" value="router">
                <div id="router_section">
                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>Each router is individually tracked. Key in the Terminal ID and details for each router.</small>
                    </div>

                    <div class="row g-3">
                        {{-- Existing or New --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Router Item</label>
                            <select name="inventory_item_id" id="router_item_select" class="form-select select2">
                                <option value="">-- Create New Router --</option>
                                @foreach($routerItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->item_code }} - {{ $item->item_name }} ({{ $item->serial_number }})</option>
                                @endforeach
                            </select>
                            <div class="form-text">Select existing router to restock, or leave blank to create new.</div>
                        </div>

                        <div id="new_router_fields">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Terminal ID <span class="text-danger">*</span></label>
                                    <input type="text" name="serial_number" class="form-control" placeholder="Enter Terminal ID" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label>
                                    <input type="text" name="item_name" class="form-control" placeholder="e.g., TP-Link Router" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                                    <select name="job_category_id" class="form-select" required>
                                        <option value="">-- Select --</option>
                                        @foreach($jobCategories as $cat)
                                            @if($cat->slug === 'router')
                                            <option value="{{ $cat->id }}" selected>{{ $cat->category_name }}</option>
                                            @else
                                            <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Brand</label>
                                    <input type="text" name="brand" class="form-control" placeholder="e.g., TP-Link">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Model</label>
                                    <input type="text" name="model" class="form-control" placeholder="e.g., Archer C6">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Movement Date</label>
                            <input type="date" name="movement_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Condition</label>
                            <select name="item_condition" class="form-select">
                                <option value="good">Good</option>
                                <option value="faulty">Faulty</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
                        </div>
                    </div>

                    <hr class="my-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Process Stock In (Router)
                    </button>
                </div>
            </form>

            {{-- ═══════════ ACCESSORY FORM ═══════════ --}}
            <form action="{{ route($roleName . '.inventory.stock-in.process') }}" method="POST" id="accessoryStockInForm" style="display:none;">
                @csrf
                <input type="hidden" name="stock_type" value="accessory">
                <div id="accessory_section">
                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>Accessories (SIM Card / Antenna) are quantity-based. Select the item and enter quantity to add.</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Accessory Item <span class="text-danger">*</span></label>
                            <select name="inventory_item_id" id="accessory_item_select" class="form-select select2" required>
                                <option value="">-- Select Accessory --</option>
                                @foreach($accessoryItems as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->item_code }} - {{ $item->item_name }}
                                        ({{ $item->accessory_type === 'sim_card' ? 'SIM Card' : 'Antenna' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Current Stock</label>
                            <input type="text" id="current_stock_display" class="form-control" readonly value="-">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Quantity to Add <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Movement Date</label>
                            <input type="date" name="movement_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
                        </div>
                    </div>

                    <hr class="my-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Process Stock In (Accessory)
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
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true, placeholder: '-- Select --' });

    // Toggle stock type sections
    $('input[name="stock_type_toggle"]').on('change', function() {
        var val = $(this).val();
        if (val === 'router') {
            $('#routerStockInForm').show();
            $('#accessoryStockInForm').hide();
        } else {
            $('#routerStockInForm').hide();
            $('#accessoryStockInForm').show();
        }
    });

    // Toggle new router fields
    $('#router_item_select').on('change.select2', function() {
        if ($(this).val()) {
            $('#new_router_fields').hide();
            $('#new_router_fields input, #new_router_fields select').removeAttr('required');
        } else {
            $('#new_router_fields').show();
            $('#new_router_fields input[name="serial_number"], #new_router_fields input[name="item_name"]').attr('required', true);
        }
    });

    // Fetch current stock for accessory
    $('#accessory_item_select').on('change.select2', function() {
        var itemId = $(this).val();
        if (!itemId) {
            $('#current_stock_display').val('-');
            return;
        }
        $.get('{{ route($roleName . ".inventory.get-item-stock") }}', { item_id: itemId }, function(data) {
            $('#current_stock_display').val(data.warehouse_stock + ' in warehouse');
        });
    });
});
</script>
@endpush
