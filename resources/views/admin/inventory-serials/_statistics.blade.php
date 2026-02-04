<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Serials</p>
                        <h3 class="mb-0">{{ number_format($stats['total'] ?? 0) }}</h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-upc-scan text-primary fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">In Stock</p>
                        <h3 class="mb-0 text-success">{{ number_format($stats['in_stock'] ?? 0) }}</h3>
                        <small class="text-muted">
                            {{ $stats['total'] > 0 ? round(($stats['in_stock'] / $stats['total']) * 100, 1) : 0 }}%
                        </small>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-box-seam text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Issued to Tech</p>
                        <h3 class="mb-0 text-info">{{ number_format($stats['issued_to_tech'] ?? 0) }}</h3>
                        <small class="text-muted">
                            {{ $stats['total'] > 0 ? round(($stats['issued_to_tech'] / $stats['total']) * 100, 1) : 0 }}%
                        </small>
                    </div>
                    <div class="bg-info bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-person-badge text-info fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Installed</p>
                        <h3 class="mb-0 text-primary">{{ number_format($stats['installed'] ?? 0) }}</h3>
                        <small class="text-muted">
                            {{ $stats['total'] > 0 ? round(($stats['installed'] / $stats['total']) * 100, 1) : 0 }}%
                        </small>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-check-circle text-primary fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Under Service</p>
                        <h3 class="mb-0 text-warning">{{ number_format($stats['under_service'] ?? 0) }}</h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-tools text-warning fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Reserved</p>
                        <h3 class="mb-0 text-dark">{{ number_format($stats['reserved'] ?? 0) }}</h3>
                    </div>
                    <div class="bg-dark bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-bookmark text-dark fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Warranty Active</p>
                        <h3 class="mb-0 text-success">{{ number_format($stats['warranty_active'] ?? 0) }}</h3>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-shield-check text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Warranty Expiring</p>
                        <h3 class="mb-0 text-warning">{{ number_format($stats['warranty_expiring'] ?? 0) }}</h3>
                        <small class="text-muted">&lt;30 days</small>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
