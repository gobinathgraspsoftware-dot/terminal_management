@extends('layouts.app')

@section('title', 'Accessories Usage')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-sim text-secondary me-2"></i>Accessories Usage</h4>
            <p class="text-muted mb-0">Team accessory usage tracking</p>
        </div>
        <a href="{{ route('supervisor.inventory-management.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    @can('create_accessory_usage')
    <div class="mb-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordUsageModal">
            <i class="bi bi-plus-circle me-1"></i> Record Usage
        </button>
    </div>
    @endcan

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th><th>Ticket</th><th>Type</th><th>Model</th><th>Serial</th>
                            <th class="text-center">Qty</th><th>Action</th><th>Date</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usages as $i => $usage)
                        <tr>
                            <td>{{ $usages->firstItem() + $i }}</td>
                            <td>{{ $usage->ticket->ticket_no ?? 'N/A' }}</td>
                            <td>{{ $usage->accessory_type_label }}</td>
                            <td>{{ $usage->model->model_name ?? 'N/A' }}</td>
                            <td>{{ $usage->serial_no ?? '-' }}</td>
                            <td class="text-center">{{ number_format($usage->quantity) }}</td>
                            <td>{!! $usage->action_badge !!}</td>
                            <td><small>{{ $usage->created_at?->format('d M Y') }}</small></td>
                            <td>
                                @if($usage->canReturn())
                                @can('return_accessory')
                                <button type="button" class="btn btn-sm btn-outline-success return-btn"
                                        data-id="{{ $usage->id }}" data-model="{{ $usage->model->model_name ?? '' }}">
                                    <i class="bi bi-arrow-return-left"></i>
                                </button>
                                @endcan
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($usages->hasPages())
        <div class="card-footer bg-white">{{ $usages->links() }}</div>
        @endif
    </div>
</div>

{{-- Record Usage Modal --}}
<div class="modal fade" id="recordUsageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Accessory Usage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="usageForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ticket <span class="text-danger">*</span></label>
                        <input type="number" name="ticket_id" class="form-control" required placeholder="Ticket ID">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                        <select name="accessory_type" class="form-select" required>
                            @foreach(\App\Models\AccessoryUsage::ACCESSORY_TYPE_OPTIONS as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Model <span class="text-danger">*</span></label>
                        <select name="model_id" class="form-select" required>
                            <option value="">Select</option>
                            @php
                                $accessoryModels = \App\Models\TerminalModel::whereHas('category', function($q) { $q->whereIn('category_type', ['sim','accessory']); })->where('status','active')->orderBy('model_name')->get();
                            @endphp
                            @foreach($accessoryModels as $m)
                            <option value="{{ $m->id }}">{{ $m->model_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Qty <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveUsageBtn">Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Return Modal --}}
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Return Accessory</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="returnForm">
                @csrf
                <input type="hidden" name="usage_id" id="returnUsageId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Return Depot <span class="text-danger">*</span></label>
                        <select name="return_depot_id" class="form-select" required>
                            <option value="">Select</option>
                            @foreach(\App\Models\Depot::where('status','active')->orderBy('depot_name')->get() as $d)
                            <option value="{{ $d->id }}">{{ $d->depot_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Condition <span class="text-danger">*</span></label>
                        <select name="return_condition" class="form-select" required>
                            <option value="good">Good</option><option value="damaged">Damaged</option><option value="defective">Defective</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#usageForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("supervisor.inventory-management.accessories.store") }}',
            method: 'POST', data: new FormData(this), processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) { if (res.success) { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1000); } },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error', 'error'); }
        });
    });

    $(document).on('click', '.return-btn', function() {
        $('#returnUsageId').val($(this).data('id'));
        $('#returnModal').modal('show');
    });

    $('#returnForm').on('submit', function(e) {
        e.preventDefault();
        let usageId = $('#returnUsageId').val();
        $.ajax({
            url: '{{ url("supervisor/inventory-management/accessories") }}/' + usageId + '/return',
            method: 'POST', data: new FormData(this), processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) { if (res.success) { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1000); } },
            error: function(xhr) { showToast(xhr.responseJSON?.message || 'Error', 'error'); }
        });
    });
});
</script>
@endpush
