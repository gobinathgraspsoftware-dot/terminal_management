@extends('layouts.app')

@section('title', 'New Stock Return — Inventory')

@section('content')
<div class="container-fluid py-4">

    {{-- ── Page Header ─────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="bi bi-box-arrow-in-left text-info me-2"></i>New Stock Return
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.stock-return') }}">Stock Return</a></li>
                    <li class="breadcrumb-item active">New Return</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.inventory.stock-return') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>

    {{-- ── Validation Errors ────────────────────────────────────────────── --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Form Card ───────────────────────────────────────────────────── --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <span class="fw-semibold">
                <i class="bi bi-clipboard-check me-2 text-info"></i>Stock Return Details
            </span>
        </div>
        <div class="card-body">

            <form action="{{ route('admin.inventory.stock-return.process') }}" method="POST" id="stockReturnForm">
                @csrf

                <div class="row g-4">

                    {{-- ── Item Selection ────────────────────────────────── --}}
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">
                            Inventory Item <span class="text-danger">*</span>
                        </label>
                        <select name="inventory_item_id"
                                id="itemSelect"
                                class="form-select @error('inventory_item_id') is-invalid @enderror"
                                required>
                            <option value="">— Select an item —</option>
                            @foreach($allItems as $item)
                                <option value="{{ $item->id }}"
                                        data-type="{{ $item->item_type }}"
                                        data-name="{{ $item->item_name }}"
                                        {{ old('inventory_item_id') == $item->id ? 'selected' : '' }}>
                                    {{ $item->item_name }}
                                    @if($item->brand)({{ $item->brand }})@endif
                                    — [{{ strtoupper($item->item_type) }}]
                                    @if($item->item_code)  #{{ $item->item_code }}@endif
                                </option>
                            @endforeach
                        </select>
                        @error('inventory_item_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        {{-- Item type badge shown after selection --}}
                        <div id="itemTypeBadge" class="mt-2" style="display:none;"></div>
                    </div>

                    {{-- ── Return Date ───────────────────────────────────── --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            Return Date <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               name="stockreturn_date"
                               class="form-control @error('stockreturn_date') is-invalid @enderror"
                               value="{{ old('stockreturn_date', date('Y-m-d')) }}"
                               max="{{ date('Y-m-d') }}"
                               required>
                        @error('stockreturn_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ── Condition ─────────────────────────────────────── --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">
                            Condition <span class="text-danger">*</span>
                        </label>
                        <select name="item_condition"
                                class="form-select @error('item_condition') is-invalid @enderror"
                                required>
                            <option value="">— Select —</option>
                            <option value="good"    {{ old('item_condition', 'good') === 'good'    ? 'selected' : '' }}>Good</option>
                            <option value="faulty"  {{ old('item_condition')         === 'faulty'  ? 'selected' : '' }}>Faulty</option>
                            <option value="damaged" {{ old('item_condition')         === 'damaged' ? 'selected' : '' }}>Damaged</option>
                        </select>
                        @error('item_condition')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ── Quantity (Accessory only — hidden for Router) ─── --}}
                    <div class="col-md-2" id="qtyWrapper" style="display:none;">
                        <label class="form-label fw-semibold">
                            Quantity <span class="text-danger">*</span>
                        </label>
                        <input type="number"
                               name="quantity"
                               id="quantityInput"
                               class="form-control @error('quantity') is-invalid @enderror"
                               value="{{ old('quantity', 1) }}"
                               min="1">
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ── Reason ─────────────────────────────────────────── --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Reason <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="reason"
                               class="form-control @error('reason') is-invalid @enderror"
                               value="{{ old('reason') }}"
                               placeholder="e.g. Contract ended, Defective unit, Replacement completed"
                               maxlength="500"
                               required>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ── Remarks ────────────────────────────────────────── --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Remarks
                            <span class="text-muted small fw-normal">(optional)</span>
                        </label>
                        <input type="text"
                               name="remarks"
                               class="form-control @error('remarks') is-invalid @enderror"
                               value="{{ old('remarks') }}"
                               placeholder="Additional notes..."
                               maxlength="1000">
                        @error('remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ════════════════════════════════════════════════════
                         ROUTER IDs SECTION — shown only when item_type = router
                         Mirrors the stock-in pattern exactly.
                         Quantity is auto-derived from number of rows entered.
                         ════════════════════════════════════════════════════ --}}
                    <div class="col-12" id="routerSection" style="display:none;">
                        <div class="card border-info">
                            <div class="card-header bg-info bg-opacity-10 py-2">
                                <span class="fw-semibold text-info">
                                    <i class="bi bi-router me-2"></i>Router IDs
                                    <small class="text-muted fw-normal ms-2">
                                        — Enter one Router ID per row. Quantity is auto-calculated from the number of IDs entered.
                                    </small>
                                </span>
                            </div>
                            <div class="card-body">

                                {{-- Running count badge --}}
                                <div class="mb-3">
                                    <span class="badge bg-secondary" id="routerCountBadge">0 router(s) entered</span>
                                </div>

                                {{-- Router ID rows --}}
                                <div id="routerIdsContainer">
                                    @if(old('router_ids'))
                                        @foreach(old('router_ids') as $oldId)
                                            <div class="input-group mb-2 router-id-row">
                                                <span class="input-group-text bg-light">
                                                    <i class="bi bi-router text-info"></i>
                                                </span>
                                                <input type="text"
                                                       name="router_ids[]"
                                                       class="form-control router-id-input"
                                                       placeholder="e.g. RTR-00001"
                                                       value="{{ $oldId }}"
                                                       maxlength="100">
                                                <button type="button"
                                                        class="btn btn-outline-danger btn-remove-router"
                                                        title="Remove this row">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="input-group mb-2 router-id-row">
                                            <span class="input-group-text bg-light">
                                                <i class="bi bi-router text-info"></i>
                                            </span>
                                            <input type="text"
                                                   name="router_ids[]"
                                                   class="form-control router-id-input"
                                                   placeholder="e.g. RTR-00001"
                                                   maxlength="100">
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-remove-router"
                                                    title="Remove this row">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <button type="button" id="btnAddRouter"
                                        class="btn btn-outline-info btn-sm mt-1">
                                    <i class="bi bi-plus-circle me-1"></i>Add Another Router ID
                                </button>

                            </div>
                        </div>
                    </div>
                    {{-- end routerSection --}}

                </div>{{-- /.row --}}

                {{-- ── Submit ─────────────────────────────────────────────── --}}
                <div class="mt-4 pt-3 border-top d-flex gap-2">
                    <button type="submit" class="btn btn-info text-white px-4" id="btnSubmit">
                        <i class="bi bi-box-arrow-in-left me-1"></i>Process Stock Return
                    </button>
                    <a href="{{ route('admin.inventory.stock-return') }}"
                       class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </a>
                </div>

            </form>

        </div>{{-- /.card-body --}}
    </div>{{-- /.card --}}

</div>
@endsection

@push('scripts')
<script>
$(function () {

    // ── Item type toggle ──────────────────────────────────────────────────
    function applyItemType(type) {
        if (type === 'router') {
            $('#routerSection').show();
            $('#qtyWrapper').hide();
            $('#quantityInput').removeAttr('required');
            $('#itemTypeBadge').html('<span class="badge bg-primary">Router — quantity derived from Router IDs</span>').show();
        } else if (type === 'accessory') {
            $('#routerSection').hide();
            $('#qtyWrapper').show();
            $('#quantityInput').attr('required', true);
            $('#itemTypeBadge').html('<span class="badge bg-info">Accessory</span>').show();
        } else {
            $('#routerSection').hide();
            $('#qtyWrapper').hide();
            $('#quantityInput').removeAttr('required');
            $('#itemTypeBadge').hide();
        }
        updateRouterCount();
    }

    // On page load — restore old() state
    @if(old('inventory_item_id'))
        applyItemType('{{ old('inventory_item_id') ? optional(\App\Models\InventoryItem::find(old('inventory_item_id')))->item_type : '' }}');
    @endif

    $('#itemSelect').on('change', function () {
        var type = $(this).find(':selected').data('type') || '';
        applyItemType(type);
    });

    // ── Router count badge ────────────────────────────────────────────────
    function updateRouterCount() {
        var filled = 0;
        $('.router-id-input').each(function () {
            if ($(this).val().trim() !== '') filled++;
        });
        var total = $('.router-id-row').length;
        $('#routerCountBadge')
            .text(filled + ' router(s) entered (' + total + ' row' + (total === 1 ? '' : 's') + ')')
            .removeClass('bg-secondary bg-success bg-warning')
            .addClass(filled === 0 ? 'bg-secondary' : (filled === total ? 'bg-success' : 'bg-warning'));
    }

    $(document).on('input', '.router-id-input', updateRouterCount);

    // ── Add row ───────────────────────────────────────────────────────────
    $('#btnAddRouter').on('click', function () {
        var row = $('<div class="input-group mb-2 router-id-row">' +
            '<span class="input-group-text bg-light"><i class="bi bi-router text-info"></i></span>' +
            '<input type="text" name="router_ids[]" class="form-control router-id-input" placeholder="e.g. RTR-00001" maxlength="100">' +
            '<button type="button" class="btn btn-outline-danger btn-remove-router" title="Remove this row"><i class="bi bi-trash"></i></button>' +
            '</div>');
        $('#routerIdsContainer').append(row);
        row.find('input').focus();
        updateRouterCount();
    });

    // ── Remove row (keep at least one) ───────────────────────────────────
    $(document).on('click', '.btn-remove-router', function () {
        if ($('.router-id-row').length > 1) {
            $(this).closest('.router-id-row').remove();
            updateRouterCount();
        } else {
            $(this).closest('.router-id-row').find('input').val('');
            updateRouterCount();
        }
    });

    // ── Form submit guard ─────────────────────────────────────────────────
    $('#stockReturnForm').on('submit', function (e) {
        var type = $('#itemSelect').find(':selected').data('type');
        if (type === 'router') {
            // Check at least one non-empty router ID
            var hasIds = false;
            $('.router-id-input').each(function () {
                if ($(this).val().trim() !== '') { hasIds = true; return false; }
            });
            if (!hasIds) {
                e.preventDefault();
                showToast('Please enter at least one Router ID.', 'warning');
                $('#routerSection').find('input:first').focus();
                return false;
            }
        }
        $('#btnSubmit').prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-1"></span>Processing…'
        );
    });

    // Initial count
    updateRouterCount();

});
</script>
@endpush
