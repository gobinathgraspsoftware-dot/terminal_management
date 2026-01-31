{{-- Category Code --}}
<div class="mb-3">
    <label for="category_code" class="form-label">
        Category Code
        <span class="text-muted">(Optional - Auto-generated if blank)</span>
    </label>
    <input type="text" 
           class="form-control @error('category_code') is-invalid @enderror" 
           id="category_code" 
           name="category_code" 
           value="{{ old('category_code', $category->category_code ?? '') }}"
           placeholder="e.g., TRM0001, RTR0001"
           pattern="[A-Z0-9]+"
           maxlength="50">
    @error('category_code')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">Format: Uppercase letters and numbers only (e.g., TRM0001)</div>
</div>

{{-- Category Name --}}
<div class="mb-3">
    <label for="category_name" class="form-label">
        Category Name <span class="text-danger">*</span>
    </label>
    <input type="text" 
           class="form-control @error('category_name') is-invalid @enderror" 
           id="category_name" 
           name="category_name" 
           value="{{ old('category_name', $category->category_name ?? '') }}"
           required
           maxlength="100"
           placeholder="e.g., POS Terminals, Mobile Routers">
    @error('category_name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Category Type --}}
<div class="mb-3">
    <label for="category_type" class="form-label">
        Category Type <span class="text-danger">*</span>
    </label>
    <select class="form-select @error('category_type') is-invalid @enderror" 
            id="category_type" 
            name="category_type" 
            required>
        <option value="">-- Select Type --</option>
        <option value="terminal" {{ old('category_type', $category->category_type ?? '') === 'terminal' ? 'selected' : '' }}>
            Terminal (POS Terminals)
        </option>
        <option value="router" {{ old('category_type', $category->category_type ?? '') === 'router' ? 'selected' : '' }}>
            Router (Network Routers)
        </option>
        <option value="sim" {{ old('category_type', $category->category_type ?? '') === 'sim' ? 'selected' : '' }}>
            SIM (SIM Cards)
        </option>
        <option value="accessory" {{ old('category_type', $category->category_type ?? '') === 'accessory' ? 'selected' : '' }}>
            Accessory (Cables, Adapters, etc.)
        </option>
        <option value="other" {{ old('category_type', $category->category_type ?? '') === 'other' ? 'selected' : '' }}>
            Other (Miscellaneous)
        </option>
    </select>
    @error('category_type')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Description --}}
<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea class="form-control @error('description') is-invalid @enderror" 
              id="description" 
              name="description" 
              rows="3"
              maxlength="1000"
              placeholder="Enter category description...">{{ old('description', $category->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">Maximum 1000 characters</div>
</div>

{{-- Serial Tracking --}}
<div class="mb-3">
    <div class="card bg-light border">
        <div class="card-body">
            <div class="form-check form-switch">
                <input class="form-check-input" 
                       type="checkbox" 
                       role="switch" 
                       id="is_serial_tracked" 
                       name="is_serial_tracked"
                       value="1"
                       {{ old('is_serial_tracked', $category->is_serial_tracked ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_serial_tracked">
                    <strong>Require Serial Number Tracking</strong>
                </label>
            </div>
            <small class="text-muted d-block mt-2">
                <i class="bi bi-info-circle me-1"></i>
                Enable this if items in this category require unique serial number tracking (e.g., terminals, routers).
                Disable for items that don't need individual tracking (e.g., cables, SIM cards).
            </small>
        </div>
    </div>
</div>

{{-- Sort Order --}}
<div class="mb-3">
    <label for="sort_order" class="form-label">
        Sort Order
        <span class="text-muted">(Optional - Used for display ordering)</span>
    </label>
    <input type="number" 
           class="form-control @error('sort_order') is-invalid @enderror" 
           id="sort_order" 
           name="sort_order" 
           value="{{ old('sort_order', $category->sort_order ?? 0) }}"
           min="0"
           step="1"
           placeholder="0">
    @error('sort_order')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">Lower numbers appear first. You can also use drag-drop on the list page.</div>
</div>

{{-- Status --}}
<div class="mb-3">
    <label for="status" class="form-label">
        Status <span class="text-danger">*</span>
    </label>
    <select class="form-select @error('status') is-invalid @enderror" 
            id="status" 
            name="status" 
            required>
        <option value="active" {{ old('status', $category->status ?? 'active') === 'active' ? 'selected' : '' }}>
            Active
        </option>
        <option value="inactive" {{ old('status', $category->status ?? '') === 'inactive' ? 'selected' : '' }}>
            Inactive
        </option>
    </select>
    @error('status')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">Inactive categories won't appear in dropdowns for new entries</div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-uppercase category code
    $('#category_code').on('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });

    // Category type change handler - optional logic
    $('#category_type').on('change', function() {
        const type = $(this).val();
        
        // Auto-enable serial tracking for terminals and routers
        if (type === 'terminal' || type === 'router') {
            $('#is_serial_tracked').prop('checked', true);
        }
        // Auto-disable for accessories (can be overridden)
        else if (type === 'accessory' || type === 'sim') {
            $('#is_serial_tracked').prop('checked', false);
        }
    });
});
</script>
@endpush
