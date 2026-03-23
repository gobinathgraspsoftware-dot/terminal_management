@extends('layouts.app')

@section('title', 'Accessories Usage - Inventory Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-sim text-secondary me-2"></i>Accessories Usage</h4>
            <p class="text-muted mb-0">Track accessory usage per ticket — one ticket = one item / one quantity</p>
        </div>
        <div class="d-flex gap-2">
            @can('export_accessory_usage')
            <a href="{{ route('admin.inventory-management.accessories.export', request()->query()) }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Export
            </a>
            @endcan
            <a href="{{ route('admin.inventory-management.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Hub
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.inventory-management.accessories') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Accessory Type</label>
                    <select name="accessory_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach(\App\Models\AccessoryUsage::ACCESSORY_TYPE_OPTIONS as $val => $label)
                        <option value="{{ $val }}" {{ request('accessory_type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All Actions</option>
                        @foreach(\App\Models\AccessoryUsage::ACTION_OPTIONS as $val => $label)
                        <option value="{{ $val }}" {{ request('action') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.inventory-management.accessories') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Record New Usage Button + Modal Trigger --}}
    @can('create_accessory_usage')
    <div class="mb-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordUsageModal">
            <i class="bi bi-plus-circle me-1"></i> Record Accessory Usage
        </button>
    </div>
    @endcan

    {{-- Usage Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Ticket</th>
                            <th>Accessory Type</th>
                            <th>Model</th>
                            <th>Serial No</th>
                            <th class="text-center">Qty</th>
                            <th>Action</th>
                            <th>Condition</th>
                            <th>Return Info</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th style="width:100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usages as $i => $usage)
                        <tr>
                            <td>{{ $usages->firstItem() + $i }}</td>
                            <td>
                                <a href="{{ route('admin.tickets.show', $usage->ticket_id) }}" class="text-decoration-none">
                                    {{ $usage->ticket->ticket_no ?? 'N/A' }}
                                </a>
                            </td>
                            <td>{{ $usage->accessory_type_label }}</td>
                            <td>{{ $usage->model->model_name ?? 'N/A' }}</td>
                            <td>{{ $usage->serial_no ?? '-' }}</td>
                            <td class="text-center">{{ number_format($usage->quantity) }}</td>
                            <td>{!! $usage->action_badge !!}</td>
                            <td>{{ ucfirst($usage->condition) }}</td>
                            <td>
                                @if($usage->return_date)
                                    <small>{{ $usage->return_date->format('d M Y') }}</small><br>
                                    <small class="text-muted">{{ ucfirst($usage->return_condition) }} → {{ $usage->returnDepot->depot_name ?? '' }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $usage->createdBy->name ?? 'System' }}</td>
                            <td><small>{{ $usage->created_at?->format('d M Y H:i') }}</small></td>
                            <td>
                                @if($usage->canReturn())
                                @can('return_accessory')
                                <button type="button" class="btn btn-sm btn-outline-success return-btn"
                                        data-id="{{ $usage->id }}"
                                        data-model="{{ $usage->model->model_name ?? '' }}"
                                        data-serial="{{ $usage->serial_no ?? 'N/A' }}">
                                    <i class="bi bi-arrow-return-left"></i> Return
                                </button>
                                @endcan
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">No accessory usage records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($usages->hasPages())
        <div class="card-footer bg-white">
            {{ $usages->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Record Usage Modal --}}
<div class="modal fade" id="recordUsageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i> Record Accessory Usage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="usageForm">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Ticket <span class="text-danger">*</span></label>
                            <select name="ticket_id" class="form-select select2-modal-ticket" required>
                                <option value="">Search Ticket...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Accessory Type <span class="text-danger">*</span></label>
                            <select name="accessory_type" class="form-select" required>
                                @foreach(\App\Models\AccessoryUsage::ACCESSORY_TYPE_OPTIONS as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Accessory Model <span class="text-danger">*</span></label>
                            <select name="model_id" class="form-select select2-modal-model" required>
                                <option value="">Select Model</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" max="99" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Condition</label>
                            <select name="condition" class="form-select">
                                <option value="new">New</option>
                                <option value="good">Good</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Serial No (if tracked)</label>
                            <input type="text" name="serial_no" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Optional notes">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveUsageBtn">
                        <i class="bi bi-check-circle me-1"></i> Record Usage
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Return Modal --}}
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-return-left me-1"></i> Return Accessory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="returnForm">
                @csrf
                <input type="hidden" name="usage_id" id="returnUsageId">
                <div class="modal-body">
                    <p>Returning: <strong id="returnInfo"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Return to Depot <span class="text-danger">*</span></label>
                        <select name="return_depot_id" class="form-select" required>
                            <option value="">Select Depot</option>
                            @foreach(\App\Models\Depot::where('status', 'active')->orderBy('depot_name')->get() as $depot)
                            <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Return Condition <span class="text-danger">*</span></label>
                        <select name="return_condition" class="form-select" required>
                            <option value="good">Good</option>
                            <option value="damaged">Damaged</option>
                            <option value="defective">Defective</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="confirmReturnBtn">
                        <i class="bi bi-check-circle me-1"></i> Confirm Return
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
    // Select2 in modal for ticket search
    $('.select2-modal-ticket').select2({
        theme: 'bootstrap-5', width: '100%', dropdownParent: $('#recordUsageModal'),
        ajax: {
            url: '{{ route("admin.tickets.index") }}',
            dataType: 'json', delay: 300, minimumInputLength: 2,
            data: function(params) { return { search: params.term }; },
            processResults: function(data) {
                // Fallback: if tickets API returns paginated data
                return { results: [] };
            }
        },
        placeholder: 'Type ticket number...'
    });

    // Load accessory models
    @php
        $accessoryModels = \App\Models\TerminalModel::with('category')
            ->whereHas('category', function($q) { $q->whereIn('category_type', ['sim', 'accessory']); })
            ->where('status', 'active')
            ->orderBy('model_name')
            ->get(['id', 'model_name', 'category_id']);
    @endphp
    let accModels = @json($accessoryModels);

    let modelOpts = '<option value="">Select Model</option>';
    accModels.forEach(m => modelOpts += `<option value="${m.id}">${m.model_name}</option>`);
    $('.select2-modal-model').html(modelOpts);

    // Record usage submit
    $('#usageForm').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        $('#saveUsageBtn').prop('disabled', true);

        $.ajax({
            url: '{{ route("admin.inventory-management.accessories.store") }}',
            method: 'POST', data: formData, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    $('#recordUsageModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                }
            },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error', 'error'); },
            complete: function() { $('#saveUsageBtn').prop('disabled', false); }
        });
    });

    // Return button
    $(document).on('click', '.return-btn', function() {
        $('#returnUsageId').val($(this).data('id'));
        $('#returnInfo').text($(this).data('model') + ' / ' + $(this).data('serial'));
        $('#returnModal').modal('show');
    });

    // Return submit
    $('#returnForm').on('submit', function(e) {
        e.preventDefault();
        let usageId = $('#returnUsageId').val();
        let formData = new FormData(this);
        $('#confirmReturnBtn').prop('disabled', true);

        $.ajax({
            url: '{{ url("admin/inventory-management/accessories") }}/' + usageId + '/return',
            method: 'POST', data: formData, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    $('#returnModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                }
            },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error', 'error'); },
            complete: function() { $('#confirmReturnBtn').prop('disabled', false); }
        });
    });
});
</script>
@endpush
