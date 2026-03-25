@extends('layouts.app')
@section('title', 'Stock Adjustment')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-sliders text-warning me-2"></i>Stock Adjustment</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">Stock Adjustment</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-4">
        {{-- Adjustment Form --}}
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="alert alert-warning d-flex align-items-center mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <small>Use stock adjustment when physical stock count differs from system count. All adjustments are logged for audit purposes.</small>
                    </div>

                    <form action="{{ route('admin.inventory.stock-adjustment.process') }}" method="POST" id="adjustmentForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Inventory Item <span class="text-danger">*</span></label>
                                <select name="inventory_item_id" id="adj_item_select" class="form-select select2" required>
                                    <option value="">-- Select Item --</option>
                                    @foreach($allItems as $item)
                                        @php
                                            $whStock = $item->stockBalances->first()->quantity ?? 0;
                                        @endphp
                                        <option value="{{ $item->id }}" data-stock="{{ $whStock }}" data-type="{{ $item->item_type }}">
                                            {{ $item->item_code }} - {{ $item->item_name }}
                                            @if($item->serial_number) ({{ $item->serial_number }}) @endif
                                            — Current: {{ $whStock }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Current System Qty</label>
                                <input type="text" id="current_qty_display" class="form-control bg-light" readonly value="-">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">New Physical Qty <span class="text-danger">*</span></label>
                                <input type="number" name="new_quantity" id="new_qty" class="form-control" min="0" required placeholder="Enter actual count">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Difference</label>
                                <input type="text" id="difference_display" class="form-control bg-light fw-bold" readonly value="-">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Reason for Adjustment <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control @error('reason') is-invalid @enderror"
                                          rows="3" placeholder="Describe why the adjustment is needed (min 10 chars)..." required minlength="10">{{ old('reason') }}</textarea>
                                @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">This reason will be stored in audit history.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Additional Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2" placeholder="Optional additional notes..."></textarea>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning" id="btn_adjust" disabled>
                                <i class="bi bi-sliders me-1"></i> Process Adjustment
                            </button>
                            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Audit Info Panel --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-shield-check me-1"></i> Adjustment Audit Trail
                </div>
                <div class="card-body">
                    <p class="text-muted small">Every adjustment records the following information:</p>
                    <table class="table table-sm table-borderless">
                        <tr><td><i class="bi bi-check-circle text-success me-1"></i> Item Type</td></tr>
                        <tr><td><i class="bi bi-check-circle text-success me-1"></i> Old Quantity / Old Value</td></tr>
                        <tr><td><i class="bi bi-check-circle text-success me-1"></i> New Quantity / New Value</td></tr>
                        <tr><td><i class="bi bi-check-circle text-success me-1"></i> Reason for Adjustment</td></tr>
                        <tr><td><i class="bi bi-check-circle text-success me-1"></i> Updated By (user)</td></tr>
                        <tr><td><i class="bi bi-check-circle text-success me-1"></i> Updated Date/Time</td></tr>
                    </table>

                    <hr>
                    <h6 class="fw-semibold">Common Scenarios</h6>
                    <ul class="small text-muted ps-3">
                        <li>Physical count is less than system count</li>
                        <li>Physical count is more than system count</li>
                        <li>Wrong stock was keyed in earlier</li>
                        <li>Items found during stock take</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%', allowClear: true, placeholder: '-- Select --' });

    var currentStock = 0;

    $('#adj_item_select').on('change.select2', function() {
        currentStock = parseInt($(this).find(':selected').data('stock')) || 0;
        $('#current_qty_display').val(currentStock);
        $('#new_qty').val('');
        $('#difference_display').val('-').removeClass('text-success text-danger');
        $('#btn_adjust').prop('disabled', true);
    });

    $('#new_qty').on('input', function() {
        var newQty = parseInt($(this).val()) || 0;
        var diff = newQty - currentStock;
        var prefix = diff > 0 ? '+' : '';
        $('#difference_display').val(prefix + diff)
            .removeClass('text-success text-danger')
            .addClass(diff > 0 ? 'text-success' : (diff < 0 ? 'text-danger' : ''));
        // Enable button only if there's an actual change
        $('#btn_adjust').prop('disabled', diff === 0 || !$('#adj_item_select').val());
    });
});
</script>
@endpush
