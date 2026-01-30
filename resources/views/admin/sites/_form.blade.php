{{-- Reusable Site Form Partial --}}

<div class="row g-3">
    <!-- Client Selection -->
    <div class="col-md-6">
        <label for="client_id" class="form-label">Client <span class="text-danger">*</span></label>
        <select name="client_id" id="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
            <option value="">Select a client</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ old('client_id', $site->client_id ?? '') == $client->id ? 'selected' : '' }}>
                    {{ $client->client_code }} - {{ $client->client_name }}
                </option>
            @endforeach
        </select>
        @error('client_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Site Name -->
    <div class="col-md-6">
        <label for="site_name" class="form-label">Site Name <span class="text-danger">*</span></label>
        <input type="text" name="site_name" id="site_name" 
               class="form-control @error('site_name') is-invalid @enderror"
               value="{{ old('site_name', $site->site_name ?? '') }}" 
               placeholder="Enter site name" required>
        @error('site_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Address -->
    <div class="col-12">
        <label for="address" class="form-label">Address</label>
        <textarea name="address" id="address" rows="2" 
                  class="form-control @error('address') is-invalid @enderror"
                  placeholder="Enter full address">{{ old('address', $site->address ?? '') }}</textarea>
        @error('address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- City -->
    <div class="col-md-4">
        <label for="city" class="form-label">City</label>
        <input type="text" name="city" id="city" 
               class="form-control @error('city') is-invalid @enderror"
               value="{{ old('city', $site->city ?? '') }}" 
               placeholder="Enter city">
        @error('city')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- State -->
    <div class="col-md-4">
        <label for="state" class="form-label">State</label>
        <select name="state" id="state" class="form-select @error('state') is-invalid @enderror">
            <option value="">Select a state</option>
            @foreach($states as $stateName)
                <option value="{{ $stateName }}" {{ old('state', $site->state ?? '') == $stateName ? 'selected' : '' }}>
                    {{ $stateName }}
                </option>
            @endforeach
        </select>
        @error('state')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Postcode -->
    <div class="col-md-4">
        <label for="postcode" class="form-label">Postcode</label>
        <input type="text" name="postcode" id="postcode" 
               class="form-control @error('postcode') is-invalid @enderror"
               value="{{ old('postcode', $site->postcode ?? '') }}" 
               placeholder="Enter postcode">
        @error('postcode')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Country -->
    <div class="col-md-6">
        <label for="country" class="form-label">Country</label>
        <input type="text" name="country" id="country" 
               class="form-control @error('country') is-invalid @enderror"
               value="{{ old('country', $site->country ?? 'Malaysia') }}" 
               placeholder="Enter country">
        @error('country')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- GPS Coordinates Section -->
    <div class="col-12">
        <hr class="my-3">
        <h6 class="mb-3">
            <i class="bi bi-geo-alt text-primary me-2"></i>GPS Coordinates
        </h6>
    </div>

    <!-- Latitude -->
    <div class="col-md-4">
        <label for="latitude" class="form-label">Latitude</label>
        <input type="number" name="latitude" id="latitude" step="0.00000001"
               class="form-control @error('latitude') is-invalid @enderror"
               value="{{ old('latitude', $site->latitude ?? '') }}" 
               placeholder="e.g. 3.139003"
               min="-90" max="90">
        @error('latitude')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">Range: -90 to 90</small>
    </div>

    <!-- Longitude -->
    <div class="col-md-4">
        <label for="longitude" class="form-label">Longitude</label>
        <input type="number" name="longitude" id="longitude" step="0.00000001"
               class="form-control @error('longitude') is-invalid @enderror"
               value="{{ old('longitude', $site->longitude ?? '') }}" 
               placeholder="e.g. 101.686855"
               min="-180" max="180">
        @error('longitude')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">Range: -180 to 180</small>
    </div>

    <!-- Capture GPS Button -->
    <div class="col-md-4">
        <label class="form-label">&nbsp;</label>
        <button type="button" id="btn-capture-gps" class="btn btn-outline-primary w-100">
            <i class="bi bi-geo-alt me-1"></i> Capture GPS
        </button>
        <small class="text-muted">Use current location</small>
    </div>

    <!-- Person In Charge Section -->
    <div class="col-12">
        <hr class="my-3">
        <h6 class="mb-3">
            <i class="bi bi-person text-primary me-2"></i>Person In Charge
        </h6>
    </div>

    <!-- PIC Name -->
    <div class="col-md-4">
        <label for="pic_name" class="form-label">PIC Name</label>
        <input type="text" name="pic_name" id="pic_name" 
               class="form-control @error('pic_name') is-invalid @enderror"
               value="{{ old('pic_name', $site->pic_name ?? '') }}" 
               placeholder="Enter PIC name">
        @error('pic_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- PIC Phone -->
    <div class="col-md-4">
        <label for="pic_phone" class="form-label">PIC Phone</label>
        <input type="text" name="pic_phone" id="pic_phone" 
               class="form-control @error('pic_phone') is-invalid @enderror"
               value="{{ old('pic_phone', $site->pic_phone ?? '') }}" 
               placeholder="+60123456789">
        @error('pic_phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- PIC Email -->
    <div class="col-md-4">
        <label for="pic_email" class="form-label">PIC Email</label>
        <input type="email" name="pic_email" id="pic_email" 
               class="form-control @error('pic_email') is-invalid @enderror"
               value="{{ old('pic_email', $site->pic_email ?? '') }}" 
               placeholder="pic@example.com">
        @error('pic_email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Additional Information -->
    <div class="col-12">
        <hr class="my-3">
        <h6 class="mb-3">
            <i class="bi bi-info-circle text-primary me-2"></i>Additional Information
        </h6>
    </div>

    <!-- Operating Hours -->
    <div class="col-md-6">
        <label for="operating_hours" class="form-label">Operating Hours</label>
        <input type="text" name="operating_hours" id="operating_hours" 
               class="form-control @error('operating_hours') is-invalid @enderror"
               value="{{ old('operating_hours', $site->operating_hours ?? '') }}" 
               placeholder="e.g. Mon-Fri: 9AM-6PM">
        @error('operating_hours')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Status -->
    <div class="col-md-6">
        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
            <option value="active" {{ old('status', $site->status ?? 'active') == 'active' ? 'selected' : '' }}>
                Active
            </option>
            <option value="inactive" {{ old('status', $site->status ?? '') == 'inactive' ? 'selected' : '' }}>
                Inactive
            </option>
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Notes -->
    <div class="col-12">
        <label for="notes" class="form-label">Notes</label>
        <textarea name="notes" id="notes" rows="3" 
                  class="form-control @error('notes') is-invalid @enderror"
                  placeholder="Enter any additional notes or remarks">{{ old('notes', $site->notes ?? '') }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Form Actions -->
    <div class="col-12">
        <hr class="my-3">
        <div class="d-flex justify-content-between">
            <a href="{{ route('admin.sites.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-circle me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> {{ isset($site) ? 'Update Site' : 'Create Site' }}
            </button>
        </div>
    </div>
</div>
