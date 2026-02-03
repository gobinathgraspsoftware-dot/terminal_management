{{-- Serial Movement Timeline Component --}}
{{-- Usage: @include('components.serial-timeline', ['movements' => $movement_history]) --}}

@if($movements->isEmpty())
    <div class="text-center text-muted py-4">
        <i class="bi bi-arrow-left-right" style="font-size: 2.5rem;"></i>
        <p class="mt-2 mb-0">No movement history recorded.</p>
    </div>
@else
    <div class="serial-timeline">
        @foreach($movements as $index => $movement)
            @php
                $typeColors = [
                    'grn_in'           => 'success',
                    'issue_to_tech'    => 'info',
                    'return_from_tech' => 'warning',
                    'transfer'         => 'primary',
                    'install'          => 'primary',
                    'replacement_out'  => 'danger',
                    'replacement_in'   => 'success',
                    'wastage'          => 'danger',
                    'return_to_vendor' => 'secondary',
                    'adjustment'       => 'dark',
                ];
                $typeIcons = [
                    'grn_in'           => 'bi-box-arrow-in-down',
                    'issue_to_tech'    => 'bi-person-plus',
                    'return_from_tech' => 'bi-person-dash',
                    'transfer'         => 'bi-arrow-left-right',
                    'install'          => 'bi-geo-alt-fill',
                    'replacement_out'  => 'bi-arrow-up-circle',
                    'replacement_in'   => 'bi-arrow-down-circle',
                    'wastage'          => 'bi-trash',
                    'return_to_vendor' => 'bi-arrow-return-left',
                    'adjustment'       => 'bi-sliders',
                ];
                $color = $typeColors[$movement->transaction_type] ?? 'secondary';
                $icon = $typeIcons[$movement->transaction_type] ?? 'bi-circle';
                $typeLabel = ucfirst(str_replace('_', ' ', $movement->transaction_type));
            @endphp

            <div class="timeline-item d-flex mb-3 {{ $index === 0 ? 'timeline-latest' : '' }}">
                <!-- Timeline Dot -->
                <div class="timeline-dot me-3 text-center" style="min-width: 40px;">
                    <div class="rounded-circle bg-{{ $color }} bg-opacity-10 d-inline-flex align-items-center justify-content-center"
                         style="width: 36px; height: 36px;">
                        <i class="bi {{ $icon }} text-{{ $color }}"></i>
                    </div>
                    @if(!$loop->last)
                        <div class="timeline-line bg-{{ $color }}" style="width: 2px; height: 100%; margin: 4px auto 0; min-height: 20px; opacity: 0.3;"></div>
                    @endif
                </div>

                <!-- Timeline Content -->
                <div class="timeline-content flex-grow-1 pb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-{{ $color }} mb-1">{{ $typeLabel }}</span>
                            @if($index === 0)
                                <span class="badge bg-dark ms-1">Latest</span>
                            @endif
                        </div>
                        <small class="text-muted">
                            {{ $movement->transaction_date?->format('d/m/Y') ?? '-' }}
                        </small>
                    </div>

                    <!-- Transaction Number -->
                    <div class="mt-1">
                        <small class="text-muted">
                            <strong>{{ $movement->transaction_no }}</strong>
                        </small>
                    </div>

                    <!-- From / To -->
                    <div class="mt-1">
                        @if($movement->from_location_type)
                            <small class="text-muted">
                                <i class="bi bi-box-arrow-right text-danger"></i>
                                From: {{ ucfirst($movement->from_location_type) }} #{{ $movement->from_location_id }}
                            </small>
                        @endif
                        @if($movement->to_location_type)
                            <br>
                            <small class="text-muted">
                                <i class="bi bi-box-arrow-in-right text-success"></i>
                                To: {{ ucfirst($movement->to_location_type) }} #{{ $movement->to_location_id }}
                            </small>
                        @endif
                    </div>

                    <!-- Quantity -->
                    @if($movement->quantity)
                        <div class="mt-1">
                            <small>
                                Qty: <strong class="text-{{ $movement->quantity > 0 ? 'success' : 'danger' }}">
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity, 0) }}
                                </strong>
                            </small>
                        </div>
                    @endif

                    <!-- Remarks -->
                    @if($movement->remarks)
                        <div class="mt-1">
                            <small class="text-muted fst-italic">{{ Str::limit($movement->remarks, 80) }}</small>
                        </div>
                    @endif

                    <!-- Created By -->
                    <div class="mt-1">
                        <small class="text-muted">
                            <i class="bi bi-person"></i> {{ $movement->createdBy?->name ?? 'System' }}
                        </small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<style>
    .serial-timeline .timeline-latest .timeline-content {
        background-color: rgba(13, 110, 253, 0.04);
        border-radius: 8px;
        padding: 10px;
        border-left: 3px solid #0d6efd;
    }

    .serial-timeline .timeline-item:not(.timeline-latest) .timeline-content {
        padding: 5px 10px;
    }
</style>
