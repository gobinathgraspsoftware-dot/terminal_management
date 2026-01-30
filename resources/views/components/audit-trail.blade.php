{{-- 
    Audit Trail Component
    Usage: @include('components.audit-trail', ['model' => $vendor])
--}}

@php
    $model = $model ?? null;
@endphp

@if($model)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Audit Trail</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-opacity-10 rounded p-2">
                            <i class="bi bi-plus-circle text-success"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">Created</h6>
                        <p class="mb-0 text-muted small">
                            {{ $model->created_at ? $model->created_at->format('d M Y, H:i') : 'N/A' }}
                        </p>
                        @if($model->createdBy)
                        <p class="mb-0 text-muted small">
                            by <strong>{{ $model->createdBy->name }}</strong>
                        </p>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 rounded p-2">
                            <i class="bi bi-pencil text-primary"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">Last Updated</h6>
                        <p class="mb-0 text-muted small">
                            {{ $model->updated_at ? $model->updated_at->format('d M Y, H:i') : 'N/A' }}
                        </p>
                        @if($model->updatedBy)
                        <p class="mb-0 text-muted small">
                            by <strong>{{ $model->updatedBy->name }}</strong>
                        </p>
                        @endif
                    </div>
                </div>
            </div>

            @if($model->deleted_at)
            <div class="col-12">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0">
                        <div class="bg-danger bg-opacity-10 rounded p-2">
                            <i class="bi bi-trash text-danger"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1 text-danger">Deleted</h6>
                        <p class="mb-0 text-muted small">
                            {{ $model->deleted_at->format('d M Y, H:i') }}
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endif
