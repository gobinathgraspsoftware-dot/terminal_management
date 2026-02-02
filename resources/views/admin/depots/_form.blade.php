<div class="row">
    <!-- Basic Information -->
    <div class="col-md-6">
        <h5 class="mb-3">Basic Information</h5>
        
        <!-- Depot Code (Auto-generated, Display only for edit) -->
        @isset($depot)
        <div class="mb-3">
            <label class="form-label">Depot Code</label>
            <input type="text" class="form-control" value="{{ $depot->depot_code }}" disabled>
        </div>
        @endisset

        <!-- Depot Name -->
        <div class="mb-3">
            <label for="depot_name" class="form-label">Depot Name <span class="text-danger">*</span></label>
            <input type="text" 
                   class="form-control @error('depot_name') is-invalid @enderror" 
                   id="depot_name" 
                   name="depot_name" 
                   value="{{ old('depot_name', $depot->depot_name ?? '') }}" 
                   required>
            @error('depot_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Depot Type -->
        <div class="mb-3">
            <label for="depot_type" class="form-label">Depot Type <span class="text-danger">*</span></label>
            <select class="form-select @error('depot_type') is-invalid @enderror" 
                    id="depot_type" 
                    name="depot_type" 
                    required>
                <option value="">-- Select Type --</option>
                <option value="main" {{ old('depot_type', $depot->depot_type ?? '') === 'main' ? 'selected' : '' }}>
                    Main Warehouse
                </option>
                <option value="regional" {{ old('depot_type', $depot->depot_type ?? '') === 'regional' ? 'selected' : '' }}>
                    Regional Depot
                </option>
                <option value="technician" {{ old('depot_type', $depot->depot_type ?? '') === 'technician' ? 'selected' : '' }}>
                    Technician Depot
                </option>
            </select>
            @error('depot_type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">
                Main: Central warehouse | Regional: Area depot | Technician: Personal storage
            </small>
        </div>

        <!-- Default Depot -->
        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="is_default" 
                       name="is_default" 
                       value="1"
                       {{ old('is_default', $depot->is_default ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_default">
                    Set as Default Depot
                </label>
            </div>
            <small class="text-muted">
                <i class="bi bi-info-circle"></i> Only one depot can be default. It will be used for new GRNs by default.
            </small>
        </div>

        <!-- Status -->
        <div class="mb-3">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select class="form-select @error('status') is-invalid @enderror" 
                    id="status" 
                    name="status">
                <option value="active" {{ old('status', $depot->status ?? 'active') === 'active' ? 'selected' : '' }}>
                    Active
                </option>
                <option value="inactive" {{ old('status', $depot->status ?? '') === 'inactive' ? 'selected' : '' }}>
                    Inactive
                </option>
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Location & Contact -->
    <div class="col-md-6">
        <h5 class="mb-3">Location & Contact</h5>

        <!-- Address -->
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <textarea class="form-control @error('address') is-invalid @enderror" 
                      id="address" 
                      name="address" 
                      rows="3">{{ old('address', $depot->address ?? '') }}</textarea>
            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <!-- City -->
            <div class="col-md-6 mb-3">
                <label for="city" class="form-label">City</label>
                <input type="text" 
                       class="form-control @error('city') is-invalid @enderror" 
                       id="city" 
                       name="city" 
                       value="{{ old('city', $depot->city ?? '') }}">
                @error('city')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- State -->
            <div class="col-md-6 mb-3">
                <label for="state" class="form-label">State</label>
                <input type="text" 
                       class="form-control @error('state') is-invalid @enderror" 
                       id="state" 
                       name="state" 
                       value="{{ old('state', $depot->state ?? '') }}">
                @error('state')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <!-- Postcode -->
            <div class="col-md-6 mb-3">
                <label for="postcode" class="form-label">Postcode</label>
                <input type="text" 
                       class="form-control @error('postcode') is-invalid @enderror" 
                       id="postcode" 
                       name="postcode" 
                       value="{{ old('postcode', $depot->postcode ?? '') }}">
                @error('postcode')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Country -->
            <div class="col-md-6 mb-3">
                <label for="country" class="form-label">Country</label>
                <input type="text" 
                       class="form-control @error('country') is-invalid @enderror" 
                       id="country" 
                       name="country" 
                       value="{{ old('country', $depot->country ?? 'Malaysia') }}">
                @error('country')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Person In Charge -->
        <div class="mb-3">
            <label for="pic_name" class="form-label">Person In Charge</label>
            <input type="text" 
                   class="form-control @error('pic_name') is-invalid @enderror" 
                   id="pic_name" 
                   name="pic_name" 
                   value="{{ old('pic_name', $depot->pic_name ?? '') }}">
            @error('pic_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <!-- PIC Phone -->
            <div class="col-md-6 mb-3">
                <label for="pic_phone" class="form-label">PIC Phone</label>
                <input type="text" 
                       class="form-control @error('pic_phone') is-invalid @enderror" 
                       id="pic_phone" 
                       name="pic_phone" 
                       value="{{ old('pic_phone', $depot->pic_phone ?? '') }}">
                @error('pic_phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- PIC Email -->
            <div class="col-md-6 mb-3">
                <label for="pic_email" class="form-label">PIC Email</label>
                <input type="email" 
                       class="form-control @error('pic_email') is-invalid @enderror" 
                       id="pic_email" 
                       name="pic_email" 
                       value="{{ old('pic_email', $depot->pic_email ?? '') }}">
                @error('pic_email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Notes -->
    <div class="col-md-12">
        <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" 
                      id="notes" 
                      name="notes" 
                      rows="3">{{ old('notes', $depot->notes ?? '') }}</textarea>
            @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<!-- Form Actions -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between">
            <a href="{{ route('admin.depots.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> {{ $submitText }}
            </button>
        </div>
    </div>
</div>
