@extends('layouts.app')
@section('title', 'Edit Rate Card')
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h4><i class="bi bi-pencil"></i> Edit Rate Card: {{ $rateCard->rate_card_code }}</h4>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.rate-cards.index') }}">Rate Cards</a></li><li class="breadcrumb-item active">Edit</li></ol></nav>
        </div>
    </div>
    <form action="{{ route('admin.rate-cards.update', $rateCard) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                <div class="mb-3"><label class="form-label">Rate Card Name *</label><input type="text" class="form-control @error('rate_card_name') is-invalid @enderror" name="rate_card_name" value="{{ old('rate_card_name', $rateCard->rate_card_name) }}" required>@error('rate_card_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3">{{ old('description', $rateCard->description) }}</textarea></div>
                <div class="row">
                    <div class="col-md-4"><div class="mb-3"><label>Job Type *</label><select class="form-select" name="job_type" required>@foreach($jobTypes as $k => $v)<option value="{{ $k }}" {{ old('job_type', $rateCard->job_type) == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div></div>
                    <div class="col-md-4"><div class="mb-3"><label>Terminal Model</label><select class="form-select" name="model_id"><option value="">All Models</option>@foreach($models as $m)<option value="{{ $m->id }}" {{ old('model_id', $rateCard->model_id) == $m->id ? 'selected' : '' }}>{{ $m->model_name }}</option>@endforeach</select></div></div>
                    <div class="col-md-4"><div class="mb-3"><label>State</label><select class="form-select" name="state"><option value="">All States</option>@foreach($states as $k => $v)<option value="{{ $v }}" {{ old('state', $rateCard->state) == $v ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="mb-3"><label>Calculation Type *</label><select class="form-select" name="calculation_type" required>@foreach($calculationTypes as $k => $v)<option value="{{ $k }}" {{ old('calculation_type', $rateCard->calculation_type) == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div></div>
                    <div class="col-md-6"><div class="mb-3"><label>Rate Amount (RM) *</label><input type="number" step="0.01" class="form-control" name="rate_amount" value="{{ old('rate_amount', $rateCard->rate_amount) }}" required></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="mb-3"><label>Min Amount (RM)</label><input type="number" step="0.01" class="form-control" name="min_amount" value="{{ old('min_amount', $rateCard->min_amount) }}"></div></div>
                    <div class="col-md-6"><div class="mb-3"><label>Max Amount (RM)</label><input type="number" step="0.01" class="form-control" name="max_amount" value="{{ old('max_amount', $rateCard->max_amount) }}"></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="mb-3"><label>Effective From *</label><input type="date" class="form-control" name="effective_from" value="{{ old('effective_from', $rateCard->effective_from->format('Y-m-d')) }}" required></div></div>
                    <div class="col-md-6"><div class="mb-3"><label>Effective To</label><input type="date" class="form-control" name="effective_to" value="{{ old('effective_to', $rateCard->effective_to?->format('Y-m-d')) }}"></div></div>
                </div>
                <div class="mb-3"><label>Status *</label><select class="form-select" name="status" required><option value="active" {{ old('status', $rateCard->status) == 'active' ? 'selected' : '' }}>Active</option><option value="inactive" {{ old('status', $rateCard->status) == 'inactive' ? 'selected' : '' }}>Inactive</option></select></div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button><a href="{{ route('admin.rate-cards.index') }}" class="btn btn-secondary">Cancel</a></div>
        </div>
    </form>
</div>
@endsection
