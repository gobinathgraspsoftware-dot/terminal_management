{{--
    Serial Timeline Component
    Usage: @include('components.serial-timeline', ['movements' => $movements, 'serial' => $serial, 'canReverse' => false])
--}}
@php
    $canReverse = $canReverse ?? false;
    $rolePrefix = $rolePrefix ?? 'admin';
@endphp

<style>
    /* ===== Serial Timeline Styles ===== */
    .serial-timeline {
        position: relative;
        padding: 20px 0;
    }
    .serial-timeline::before {
        content: '';
        position: absolute;
        top: 0;
        left: 30px;
        width: 3px;
        height: 100%;
        background: linear-gradient(180deg, #667eea 0%, #764ba2 50%, #667eea 100%);
        border-radius: 3px;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 25px;
        padding-left: 70px;
    }
    .timeline-item:last-child {
        margin-bottom: 0;
    }
    .timeline-dot {
        position: absolute;
        left: 18px;
        top: 5px;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        color: #fff;
        z-index: 2;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
    .timeline-dot.dot-success  { background: #198754; }
    .timeline-dot.dot-info     { background: #0dcaf0; }
    .timeline-dot.dot-primary  { background: #0d6efd; }
    .timeline-dot.dot-warning  { background: #ffc107; color: #333; }
    .timeline-dot.dot-danger   { background: #dc3545; }
    .timeline-dot.dot-secondary { background: #6c757d; }
    .timeline-dot.dot-dark     { background: #212529; }

    .timeline-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 15px 18px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        transition: all 0.2s;
    }
    .timeline-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-color: #cbd5e0;
    }
    .timeline-card.reversed {
        opacity: 0.6;
        background: #fef2f2;
        border-color: #fca5a5;
        text-decoration: line-through;
    }
    .timeline-card.reversal-entry {
        background: #fffbeb;
        border-color: #fbbf24;
        border-style: dashed;
    }

    .timeline-date {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 6px;
    }
    .timeline-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 6px;
    }
    .timeline-meta {
        font-size: 0.82rem;
        color: #475569;
    }
    .timeline-meta .meta-label {
        color: #94a3b8;
        font-weight: 500;
    }
    .timeline-reference a {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }
    .timeline-reference a:hover {
        text-decoration: underline;
    }
    .timeline-remarks {
        font-size: 0.8rem;
        color: #64748b;
        font-style: italic;
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px dashed #e2e8f0;
    }
    .timeline-actions {
        margin-top: 8px;
    }

    /* Start and End markers */
    .timeline-marker {
        position: relative;
        margin-bottom: 25px;
        padding-left: 70px;
    }
    .timeline-marker .marker-dot {
        position: absolute;
        left: 16px;
        top: 2px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        color: #fff;
        z-index: 2;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
    .timeline-marker .marker-label {
        font-weight: 700;
        font-size: 0.85rem;
        color: #475569;
        padding: 4px 0;
    }
</style>

<div class="serial-timeline">
    {{-- START MARKER --}}
    <div class="timeline-marker">
        <div class="marker-dot bg-dark">
            <i class="bi bi-play-fill"></i>
        </div>
        <div class="marker-label">
            Serial Journey Start
            @if($serial->grn_date)
                <small class="text-muted ms-2">{{ $serial->grn_date->format('d/m/Y') }}</small>
            @endif
        </div>
    </div>

    {{-- MOVEMENT ITEMS --}}
    @forelse($movements as $movement)
        @php
            $isReversed  = $movement->is_reversed;
            $isReversal  = !is_null($movement->reversal_of_id);
            $dotColor    = $isReversed ? 'secondary' : ($isReversal ? 'warning' : $movement->type_color);
            $cardClass   = $isReversed ? 'reversed' : ($isReversal ? 'reversal-entry' : '');
        @endphp

        <div class="timeline-item" id="movement-{{ $movement->id }}">
            <div class="timeline-dot dot-{{ $dotColor }}">
                <i class="bi {{ $movement->type_icon }}"></i>
            </div>

            <div class="timeline-card {{ $cardClass }}">
                {{-- Date & Transaction No --}}
                <div class="timeline-date d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-calendar3 me-1"></i>
                        {{ $movement->transaction_date?->format('d M Y') }}
                        <span class="text-muted ms-1">{{ $movement->created_at?->format('H:i') }}</span>
                    </span>
                    <span class="text-muted">{{ $movement->transaction_no }}</span>
                </div>

                {{-- Movement Title --}}
                <div class="timeline-title">
                    {!! $movement->type_badge !!}
                    @if($isReversed)
                        <span class="badge bg-danger ms-1"><i class="bi bi-x-circle me-1"></i>Reversed</span>
                    @endif
                    @if($isReversal)
                        <span class="badge bg-warning text-dark ms-1"><i class="bi bi-arrow-counterclockwise me-1"></i>Reversal</span>
                    @endif
                </div>

                {{-- From → To --}}
                <div class="timeline-meta mt-2">
                    <div class="row">
                        <div class="col-md-5">
                            <span class="meta-label">From:</span>
                            <span>{{ $movement->from_location_name }}</span>
                        </div>
                        <div class="col-md-2 text-center">
                            <i class="bi bi-arrow-right text-primary"></i>
                        </div>
                        <div class="col-md-5">
                            <span class="meta-label">To:</span>
                            <span>{{ $movement->to_location_name }}</span>
                        </div>
                    </div>
                </div>

                {{-- Reference --}}
                @if($movement->reference_type)
                    <div class="timeline-meta mt-1 timeline-reference">
                        <span class="meta-label">Reference:</span>
                        @if($movement->reference_url)
                            <a href="{{ $movement->reference_url }}">
                                <i class="bi bi-link-45deg me-1"></i>{{ $movement->reference_label }}
                            </a>
                        @else
                            {{ $movement->reference_label }}
                        @endif
                    </div>
                @endif

                {{-- Performed By --}}
                <div class="timeline-meta mt-1">
                    <span class="meta-label">By:</span>
                    <span>{{ $movement->createdBy?->name ?? 'System' }}</span>
                </div>

                {{-- Reversal Info --}}
                @if($isReversed && $movement->reversedByUser)
                    <div class="timeline-meta mt-1">
                        <span class="meta-label">Reversed by:</span>
                        <span class="text-danger">{{ $movement->reversedByUser->name }} on {{ $movement->reversed_at?->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
                @if($isReversal && $movement->reversalOfEntry)
                    <div class="timeline-meta mt-1">
                        <span class="meta-label">Reversal of:</span>
                        <a href="#movement-{{ $movement->reversal_of_id }}" class="text-warning">
                            TXN# {{ $movement->reversalOfEntry->transaction_no }}
                        </a>
                    </div>
                @endif

                {{-- Remarks --}}
                @if($movement->remarks)
                    <div class="timeline-remarks">
                        <i class="bi bi-chat-left-text me-1"></i>{{ $movement->remarks }}
                    </div>
                @endif

                {{-- Reverse Action (Admin only) --}}
                @if($canReverse && $movement->is_reversible)
                    <div class="timeline-actions">
                        <button type="button"
                                class="btn btn-sm btn-outline-warning btn-reverse"
                                data-id="{{ $movement->id }}"
                                data-txn="{{ $movement->transaction_no }}">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reverse
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="timeline-item">
            <div class="timeline-dot dot-secondary">
                <i class="bi bi-question-lg"></i>
            </div>
            <div class="timeline-card">
                <div class="text-center text-muted py-3">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    No movement history found for this serial.
                </div>
            </div>
        </div>
    @endforelse

    {{-- CURRENT STATUS MARKER --}}
    <div class="timeline-marker">
        <div class="marker-dot bg-primary">
            <i class="bi bi-geo-alt-fill"></i>
        </div>
        <div class="marker-label">
            Current Status: {!! $serial->status_badge !!}
            <span class="ms-2 text-muted">@ {{ $serial->location_name }}</span>
        </div>
    </div>
</div>
