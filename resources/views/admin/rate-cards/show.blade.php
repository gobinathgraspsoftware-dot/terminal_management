@extends('layouts.app')
@section('title', 'Rate Card Details')
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h4><i class="bi bi-eye"></i> Rate Card: {{ $rateCard->rate_card_code }}</h4>
            <div class="float-end">@can('edit_rate_cards')<a href="{{ route('admin.rate-cards.edit', $rateCard) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>@endcan<a href="{{ route('admin.rate-cards.index') }}" class="btn btn-secondary ms-2">Back</a></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h5>Rate Card Information</h5></div>
                <div class="card-body">
                    <table class="table">
                        <tr><th width="200">Code:</th><td><strong>{{ $rateCard->rate_card_code }}</strong></td></tr>
                        <tr><th>Name:</th><td>{{ $rateCard->rate_card_name }}</td></tr>
                        <tr><th>Description:</th><td>{{ $rateCard->description ?? 'N/A' }}</td></tr>
                        <tr><th>Job Type:</th><td><span class="badge bg-info">{{ $rateCard->job_type_display }}</span></td></tr>
                        <tr><th>Terminal Model:</th><td>{{ $rateCard->model ? $rateCard->model->model_name : 'All Models' }}</td></tr>
                        <tr><th>State:</th><td>{{ $rateCard->state ?? 'All States' }}</td></tr>
                        <tr><th>Calculation Type:</th><td><span class="badge bg-primary">{{ $rateCard->calculation_type_display }}</span></td></tr>
                        <tr><th>Rate Amount:</th><td><strong>RM {{ number_format($rateCard->rate_amount, 2) }}</strong></td></tr>
                        <tr><th>Min Amount:</th><td>{{ $rateCard->min_amount ? 'RM ' . number_format($rateCard->min_amount, 2) : 'No limit' }}</td></tr>
                        <tr><th>Max Amount:</th><td>{{ $rateCard->max_amount ? 'RM ' . number_format($rateCard->max_amount, 2) : 'No limit' }}</td></tr>
                        <tr><th>Effective From:</th><td>{{ $rateCard->effective_from->format('d M Y') }}</td></tr>
                        <tr><th>Effective To:</th><td>{{ $rateCard->effective_to ? $rateCard->effective_to->format('d M Y') : 'No end date' }}</td></tr>
                        <tr><th>Status:</th><td>{!! $rateCard->status_badge !!} @if($rateCard->is_effective)<span class="badge bg-success ms-1">Currently Effective</span>@elseif($rateCard->is_expired)<span class="badge bg-danger ms-1">Expired</span>@endif</td></tr>
                        <tr><th>Created:</th><td>{{ $rateCard->created_at->format('d M Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-header bg-info text-white"><h5>Examples</h5></div><div class="card-body">
                <h6>Flat Rate Example:</h6><p>Commission: RM {{ number_format($rateCard->calculateCommission(1000, 1), 2) }}</p>
                @if($rateCard->calculation_type == 'per_terminal')<h6>5 Terminals:</h6><p>Commission: RM {{ number_format($rateCard->calculateCommission(0, 5), 2) }}</p>@endif
                @if($rateCard->calculation_type == 'percentage')<h6>Job Value RM 2,000:</h6><p>Commission: RM {{ number_format($rateCard->calculateCommission(2000, 1), 2) }}</p>@endif
            </div></div>
        </div>
    </div>
</div>
@endsection
