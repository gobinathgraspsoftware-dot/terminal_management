@extends('layouts.app')
@section('title', 'Rate Card Details')
@section('content')
<div class="container-fluid">
    <div class="row mb-3"><div class="col-md-12"><h4>Rate Card: {{ $rateCard->rate_card_code }}</h4><a href="{{ route('supervisor.rate-cards.index') }}" class="btn btn-secondary float-end">Back</a></div></div>
    <div class="card">
        <div class="card-body">
            <table class="table">
                <tr><th width="200">Code:</th><td>{{ $rateCard->rate_card_code }}</td></tr>
                <tr><th>Name:</th><td>{{ $rateCard->rate_card_name }}</td></tr>
                <tr><th>Job Type:</th><td>{{ $rateCard->job_type_display }}</td></tr>
                <tr><th>Model:</th><td>{{ $rateCard->model ? $rateCard->model->model_name : 'All Models' }}</td></tr>
                <tr><th>State:</th><td>{{ $rateCard->state ?? 'All States' }}</td></tr>
                <tr><th>Calculation:</th><td>{{ $rateCard->calculation_type_display }}</td></tr>
                <tr><th>Rate:</th><td><strong>RM {{ number_format($rateCard->rate_amount, 2) }}</strong></td></tr>
                <tr><th>Effective:</th><td>{{ $rateCard->effective_from->format('d M Y') }} - {{ $rateCard->effective_to ? $rateCard->effective_to->format('d M Y') : 'No end' }}</td></tr>
                <tr><th>Status:</th><td>{!! $rateCard->status_badge !!}</td></tr>
            </table>
        </div>
    </div>
</div>
@endsection
