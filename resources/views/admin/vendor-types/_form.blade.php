<div class="row g-3">
    {{-- Title --}}
    <div class="col-md-6">
        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="title" name="title"
               value="{{ old('title', $vendorType->title ?? '') }}"
               placeholder="Enter vendor type title" required maxlength="100">
        <div class="invalid-feedback" id="error-title"></div>
    </div>

    {{-- Status --}}
    <div class="col-md-6">
        <label for="is_active" class="form-label">Status</label>
        <select class="form-select" id="is_active" name="is_active">
            <option value="1" {{ old('is_active', $vendorType->is_active ?? true) == 1 ? 'selected' : '' }}>Active</option>
            <option value="0" {{ old('is_active', $vendorType->is_active ?? true) == 0 ? 'selected' : '' }}>Inactive</option>
        </select>
        <div class="invalid-feedback" id="error-is_active"></div>
    </div>

    {{-- Description --}}
    <div class="col-12">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3"
                  placeholder="Enter description (optional)" maxlength="500">{{ old('description', $vendorType->description ?? '') }}</textarea>
        <div class="invalid-feedback" id="error-description"></div>
    </div>
</div>
