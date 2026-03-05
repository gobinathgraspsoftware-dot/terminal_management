@extends('layouts.app')

@section('title', $terminalModel->model_name . ' - Terminal Models - TMS')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-box-seam me-2"></i>{{ $terminalModel->model_name }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.terminal-models.index') }}">Terminal Models</a></li>
                    <li class="breadcrumb-item active">{{ $terminalModel->model_code }}</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('edit_models')
            <a href="{{ route('admin.terminal-models.edit', $terminalModel) }}" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            @endcan
            <a href="{{ route('admin.terminal-models.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Model Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            @if($terminalModel->image_url)
                                <img src="{{ $terminalModel->image_url }}"
                                     alt="{{ $terminalModel->model_name }}"
                                     class="img-fluid img-thumbnail rounded"
                                     style="max-width: 250px; max-height: 250px; object-fit: contain;">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto;">
                                    <div class="text-center text-muted">
                                        <i class="bi bi-image" style="font-size: 3rem;"></i>
                                        <p class="mb-0 small">No Image</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-8">
                            <table class="table table-borderless">
                                <tr><th class="text-muted" style="width:40%;">Model Code</th><td><strong>{{ $terminalModel->model_code }}</strong></td></tr>
                                <tr><th class="text-muted">Model Name</th><td>{{ $terminalModel->model_name }}</td></tr>
                                <tr>
                                    <th class="text-muted">Category</th>
                                    <td>
                                        @if($terminalModel->category)
                                            <span class="badge bg-info">{{ $terminalModel->category->category_name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr><th class="text-muted">Brand</th><td>{{ $terminalModel->brand ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Warranty</th><td>{{ $terminalModel->warranty_months }} months</td></tr>
                                <tr>
                                    <th class="text-muted">Serial Tracked</th>
                                    <td>{!! $terminalModel->is_serial_tracked ? '<span class="badge bg-primary"><i class="bi bi-check-circle"></i> Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Status</th>
                                    <td>{!! $terminalModel->status === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' !!}</td>
                                </tr>
                                <tr><th class="text-muted">Sort Order</th><td>{{ $terminalModel->sort_order }}</td></tr>
                            </table>
                        </div>
                    </div>

                    @if($terminalModel->description)
                    <div class="mt-3">
                        <h6 class="text-muted">Description</h6>
                        <p class="mb-0">{{ $terminalModel->description }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Specifications -->
            @if($terminalModel->specifications && is_array($terminalModel->specifications) && count($terminalModel->specifications) > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Specifications</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-striped">
                        <thead><tr><th style="width:40%;">Specification</th><th>Value</th></tr></thead>
                        <tbody>
                            @foreach($terminalModel->specifications as $spec)
                                @if(!empty($spec['key']) || !empty($spec['value']))
                                <tr><td><strong>{{ $spec['key'] ?? '-' }}</strong></td><td>{{ $spec['value'] ?? '-' }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Stock Summary -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-box me-2"></i>Stock Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <div class="h4 text-primary mb-0">{{ number_format($stockSummary['total_on_hand'], 0) }}</div>
                                <small class="text-muted">On Hand</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <div class="h4 text-warning mb-0">{{ number_format($stockSummary['total_reserved'], 0) }}</div>
                                <small class="text-muted">Reserved</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <div class="h4 text-success mb-0">{{ number_format($stockSummary['total_available'], 0) }}</div>
                                <small class="text-muted">Available</small>
                            </div>
                        </div>
                    </div>

                    @if($stockSummary['by_location']->count() > 0)
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr><th>Location</th><th class="text-end">On Hand</th><th class="text-end">Reserved</th><th class="text-end">Available</th></tr>
                        </thead>
                        <tbody>
                            @foreach($stockSummary['by_location'] as $loc)
                            <tr>
                                <td><i class="bi bi-{{ $loc['location_type'] === 'depot' ? 'building' : 'person' }} me-1"></i>{{ $loc['location_name'] }}</td>
                                <td class="text-end">{{ number_format($loc['quantity_on_hand'], 0) }}</td>
                                <td class="text-end">{{ number_format($loc['quantity_reserved'], 0) }}</td>
                                <td class="text-end">{{ number_format($loc['quantity_available'], 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-muted text-center mb-0">No stock records found.</p>
                    @endif
                </div>
            </div>

            <!-- Recent Movements -->
            @if($recentMovements->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Serial Updates</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr><th>Serial No</th><th>Status</th><th>Updated</th></tr>
                        </thead>
                        <tbody>
                            @foreach($recentMovements as $serial)
                            <tr>
                                <td><code>{{ $serial->serial_no }}</code></td>
                                <td><span class="badge bg-secondary">{{ ucfirst($serial->current_status) }}</span></td>
                                <td>{{ $serial->updated_at->format('d M Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    @can('edit_models')
                    <button class="btn btn-outline-primary w-100 mb-2" id="toggleStatusBtn" data-status="{{ $terminalModel->status }}">
                        <i class="bi bi-toggle-{{ $terminalModel->status === 'active' ? 'on' : 'off' }} me-1"></i>
                        {{ $terminalModel->status === 'active' ? 'Deactivate' : 'Activate' }}
                    </button>
                    @endcan
                    @can('delete_models')
                    <button class="btn btn-outline-danger w-100" id="deleteModelBtn">
                        <i class="bi bi-trash me-1"></i> Delete Model
                    </button>
                    @endcan
                </div>
            </div>

            <!-- Default Accessories -->
            @if($accessories->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-puzzle me-2"></i>Default Accessories</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @foreach($accessories as $accessory)
                        <li class="list-group-item d-flex align-items-center px-0">
                            @if($accessory->image_url)
                                <img src="{{ $accessory->image_url }}" alt="{{ $accessory->model_name }}"
                                     class="img-thumbnail me-2" style="width:40px;height:40px;object-fit:cover;">
                            @else
                                <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                    <i class="bi bi-puzzle text-muted"></i>
                                </div>
                            @endif
                            <div><strong>{{ $accessory->model_name }}</strong><br><small class="text-muted">{{ $accessory->model_code }}</small></div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <!-- Metadata -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-clock me-2"></i>Metadata</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Created</td><td>{{ $terminalModel->created_at->format('d M Y H:i') }}</td></tr>
                        <tr><td class="text-muted">Updated</td><td>{{ $terminalModel->updated_at->format('d M Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#toggleStatusBtn').on('click', function() {
        var currentStatus = $(this).data('status');
        confirmAction(
            currentStatus === 'active' ? 'Deactivate Model?' : 'Activate Model?',
            'Are you sure you want to change the status?',
            function() {
                $.ajax({
                    url: '{{ route("admin.terminal-models.toggle-status", $terminalModel) }}',
                    type: 'POST',
                    success: function(response) {
                        if (response.success) { showToast(response.message); setTimeout(function() { location.reload(); }, 1000); }
                        else { showToast(response.message, 'error'); }
                    },
                    error: function(xhr) { showToast(xhr.responseJSON?.message || 'Failed', 'error'); }
                });
            }
        );
    });

    $('#deleteModelBtn').on('click', function() {
        confirmAction('Delete Terminal Model?', 'This action can be undone.', function() {
            $.ajax({
                url: '{{ route("admin.terminal-models.destroy", $terminalModel) }}',
                type: 'DELETE',
                success: function(response) {
                    if (response.success) { showToast(response.message); setTimeout(function() { window.location.href = '{{ route("admin.terminal-models.index") }}'; }, 1000); }
                    else { showToast(response.message, 'error'); }
                },
                error: function(xhr) { showToast(xhr.responseJSON?.message || 'Delete failed', 'error'); }
            });
        });
    });
});
</script>
@endpush
