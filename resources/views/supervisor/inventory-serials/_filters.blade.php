<div class="card mb-4" id="filterCard" style="position: relative;">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-funnel"></i> Filters
            </h6>
            <button class="btn btn-sm btn-link text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-md-3">
                    <label for="searchInput" class="form-label small">Search Serial</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search serial number...">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-md-3">
                    <label for="filterStatus" class="form-label small">Status</label>
                    <select class="form-select filter-select" id="filterStatus">
                        <option value="">All Statuses</option>
                        @foreach($filterOptions['statuses'] as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Technician Filter -->
                <div class="col-md-3">
                    <label for="filterTechnician" class="form-label small">Team Member</label>
                    <select class="form-select filter-select" id="filterTechnician">
                        <option value="">All Team Members</option>
                        @foreach($filterOptions['technicians'] ?? [] as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Model Filter -->
                <div class="col-md-3">
                    <label for="filterModel" class="form-label small">Model</label>
                    <select class="form-select filter-select" id="filterModel">
                        <option value="">All Models</option>
                        @foreach(\App\Models\TerminalModel::where('status', 'active')->orderBy('model_name')->get() as $model)
                            <option value="{{ $model->id }}">{{ $model->model_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="col-md-3">
                    <label for="filterCategory" class="form-label small">Category</label>
                    <select class="form-select filter-select" id="filterCategory">
                        <option value="">All Categories</option>
                        @foreach(\App\Models\TerminalCategory::where('status', 'active')->orderBy('category_name')->get() as $category)
                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Warranty Status Filter -->
                <div class="col-md-3">
                    <label for="filterWarranty" class="form-label small">Warranty Status</label>
                    <select class="form-select filter-select" id="filterWarranty">
                        <option value="">All</option>
                        <option value="active">Active Warranty</option>
                        <option value="expired">Expired/No Warranty</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
