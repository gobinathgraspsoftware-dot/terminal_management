@extends('layouts.app')
@section('title', 'Stock In')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-arrow-in-down me-2 text-success"></i>Stock In</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route(explode('.', Route::currentRouteName())[0] . '.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route(explode('.', Route::currentRouteName())[0] . '.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Stock In</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add Stock to Warehouse</h6>
                </div>
                <div class="card-body">
                    <form id="stock-in-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-8">
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
                            <div class="col-md-4">
                                <label class="form-label">Current Warehouse Stock</label>
                                <input type="text" id="current-stock" class="form-control" readonly value="-">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Movement Date <span class="text-danger">*</span></label>
                                <input type="date" name="movement_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Reason</label>
                                <input type="text" name="reason" class="form-control" placeholder="e.g., Purchase, GRN, Transfer from another location">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success" id="btn-submit">
                                <i class="bi bi-box-arrow-in-down me-1"></i> Process Stock In
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Panel -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h6><i class="bi bi-question-circle me-2"></i>Stock In Help</h6>
                    <hr>
                    <p class="small text-muted mb-2">Stock In adds items to the <strong>Warehouse</strong>.</p>
                    <p class="small text-muted mb-2">For <strong>Routers</strong>: Each unit is individually tracked by Terminal ID. Quantity is typically 1.</p>
                    <p class="small text-muted mb-0">For <strong>Accessories</strong>: Enter the quantity being added (SIM cards, antennas, etc.).</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @php $roleName = explode('.', Route::currentRouteName())[0]; @endphp

    $('.select2-field').select2({ theme: 'bootstrap-5', width: '100%' });

    // Fetch current stock on item change
    $('#inventory_item_id').on('change.select2', function() {
        let itemId = $(this).val();
        if (!itemId) { $('#current-stock').val('-'); return; }

        $.get('{{ route($roleName . ".inventory.get-item-stock") }}', { item_id: itemId }, function(res) {
            if (res.success) {
                $('#current-stock').val(res.warehouse_stock);
                // For routers, force qty to 1
                if (res.item_type === 'router') {
                    $('input[name="quantity"]').val(1).prop('max', 1);
                } else {
                    $('input[name="quantity"]').prop('max', '');
                }
            }
        });
    });

    // Form submit
    $('#stock-in-form').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btn-submit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

        $.ajax({
            url: '{{ route($roleName . ".inventory.stock-in.process") }}',
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
                btn.prop('disabled', false).html('<i class="bi bi-box-arrow-in-down me-1"></i> Process Stock In');
                showToast('error', xhr.responseJSON?.message || 'Stock In failed.');
            }
        });
    });
});
</script>
@endpush
