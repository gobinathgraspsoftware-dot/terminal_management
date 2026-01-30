{{-- 
    Reusable Address Component
    Usage: @include('components.address-form', ['data' => $model])
--}}

@php
    $data = $data ?? null;
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Address Information</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Address</label>
                <textarea class="form-control" name="address" rows="2">{{ old('address', $data->address ?? '') }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">City</label>
                <input type="text" class="form-control" name="city" value="{{ old('city', $data->city ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">State</label>
                <select class="form-select" name="state">
                    <option value="">Select State</option>
                    @foreach(['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'] as $state)
                        <option value="{{ $state }}" {{ old('state', $data->state ?? '') == $state ? 'selected' : '' }}>{{ $state }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Postcode</label>
                <input type="text" class="form-control" name="postcode" value="{{ old('postcode', $data->postcode ?? '') }}">
            </div>
            <div class="col-md-12">
                <label class="form-label">Country</label>
                <input type="text" class="form-control" name="country" value="{{ old('country', $data->country ?? 'Malaysia') }}">
            </div>
        </div>
    </div>
</div>
