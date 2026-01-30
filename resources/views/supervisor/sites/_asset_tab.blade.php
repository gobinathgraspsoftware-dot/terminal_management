{{-- Installed Assets Tab Content - Supervisor (Read-Only) --}}

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5>Installed Equipment</h5>
    <span class="badge bg-info">{{ $site->siteAssets->count() }} Assets</span>
</div>

@forelse($site->siteAssets as $asset)
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-9">
                <h6 class="mb-1">
                    {{ $asset->model->model_name ?? 'Unknown Model' }}
                    @if($asset->status === 'active')
                        <span class="badge bg-success ms-2">Active</span>
                    @elseif($asset->status === 'under_service')
                        <span class="badge bg-warning ms-2">Under Service</span>
                    @elseif($asset->status === 'replaced')
                        <span class="badge bg-secondary ms-2">Replaced</span>
                    @elseif($asset->status === 'removed')
                        <span class="badge bg-dark ms-2">Removed</span>
                    @else
                        <span class="badge bg-danger ms-2">{{ ucfirst($asset->status) }}</span>
                    @endif
                </h6>
                
                <p class="text-muted small mb-2">
                    <strong>Serial No:</strong> {{ $asset->serial_no }}
                </p>

                <div class="row small">
                    <div class="col-md-6">
                        <i class="bi bi-calendar-check text-primary me-1"></i>
                        <strong>Installed:</strong> {{ $asset->installed_date ? \Carbon\Carbon::parse($asset->installed_date)->format('d M Y') : '-' }}
                    </div>
                    @if($asset->warranty_end)
                    <div class="col-md-6">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        <strong>Warranty Until:</strong> {{ \Carbon\Carbon::parse($asset->warranty_end)->format('d M Y') }}
                        @if(\Carbon\Carbon::parse($asset->warranty_end)->isPast())
                            <span class="badge bg-danger ms-1">Expired</span>
                        @else
                            <span class="badge bg-success ms-1">Active</span>
                        @endif
                    </div>
                    @endif
                </div>

                @if($asset->notes)
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>{{ $asset->notes }}
                    </small>
                </div>
                @endif
            </div>

            <div class="col-md-3 text-end">
                @if($asset->installation_job_id)
                    <button class="btn btn-sm btn-outline-primary d-block" 
                            onclick="alert('Job details available in full system')">
                        <i class="bi bi-clipboard-check me-1"></i> Installation Job
                    </button>
                @endif
            </div>
        </div>

        @if($asset->removed_date)
        <div class="alert alert-warning mt-3 mb-0">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Removed on {{ \Carbon\Carbon::parse($asset->removed_date)->format('d M Y') }}</strong>
            @if($asset->removal_reason)
                <br><small>Reason: {{ $asset->removal_reason }}</small>
            @endif
        </div>
        @endif
    </div>
</div>
@empty
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No equipment installed at this site yet.
</div>
@endforelse
