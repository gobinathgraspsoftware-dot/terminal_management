{{-- Accessories Inventory Table (Quantity Based) --}}
<div class="mb-3">
    <p class="text-muted small mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Accessories (SIM Card, Antenna, etc.) are quantity-based stock items.
    </p>
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <select id="filterAccDepot" class="form-select form-select-sm">
                <option value="">All Locations</option>
                @foreach($depots as $depot)
                <option value="{{ $depot->id }}">{{ $depot->depot_name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
<div class="table-responsive">
    <table id="accessoryTable" class="table table-sm table-hover align-middle" style="width:100%">
        <thead class="table-light">
            <tr>
                <th>Model</th>
                <th>Category</th>
                <th>Location</th>
                <th class="text-end">On Hand</th>
                <th class="text-end">Reserved</th>
                <th class="text-end">Available</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
