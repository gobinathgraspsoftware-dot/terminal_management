{{-- Router Inventory Table (Serial Tracked, Individual) --}}
<div class="mb-3">
    <p class="text-muted small mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Routers require Terminal ID and are individually tracked by serial number.
    </p>
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <select id="filterRouterStatus" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                @foreach(\App\Models\InventorySerial::STATUS_OPTIONS as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select id="filterRouterDepot" class="form-select form-select-sm">
                <option value="">All Depots</option>
                @foreach($depots as $depot)
                <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
<div class="table-responsive">
    <table id="routerTable" class="table table-sm table-hover align-middle" style="width:100%">
        <thead class="table-light">
            <tr>
                <th>Serial No</th>
                <th>Model</th>
                <th>Category</th>
                <th>Status</th>
                <th>Location</th>
                <th style="width:80px">Action</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
