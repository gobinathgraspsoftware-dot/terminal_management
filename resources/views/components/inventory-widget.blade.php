{{--
    Reusable Inventory Dashboard Widget Partial
    Path: resources/views/partials/inventory-widget.blade.php

    Usage:
    @include('partials.inventory-widget', [
        'widgetId'        => 'stockByCategory',
        'widgetTitle'     => 'Stock Summary by Category',
        'widgetIcon'      => 'bi-grid-3x3-gap',
        'widgetColor'     => 'primary',
        'widgetHeight'    => '320px',
        'widgetContent'   => '<div>Loading...</div>',
    ])
--}}

@php
    $widgetId     = $widgetId ?? 'widget-' . uniqid();
    $widgetTitle  = $widgetTitle ?? 'Widget';
    $widgetIcon   = $widgetIcon ?? 'bi-box-seam';
    $widgetColor  = $widgetColor ?? 'primary';
    $widgetHeight = $widgetHeight ?? 'auto';
    $widgetContent = $widgetContent ?? '';
@endphp

<div class="card inventory-widget" id="{{ $widgetId }}">
    {{-- Widget Header --}}
    <div class="card-header d-flex align-items-center justify-content-between py-2">
        <div class="d-flex align-items-center">
            <i class="bi {{ $widgetIcon }} me-2 text-{{ $widgetColor }}"></i>
            <h6 class="mb-0 fw-semibold">{{ $widgetTitle }}</h6>
        </div>
        <div class="d-flex align-items-center gap-2">
            {{-- Refresh button --}}
            <button type="button" class="btn btn-sm btn-outline-secondary widget-refresh-btn" data-widget-id="{{ $widgetId }}" title="Refresh">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    {{-- Widget Body --}}
    <div class="card-body position-relative"
         id="{{ $widgetId }}-body"
         style="{{ $widgetHeight !== 'auto' ? 'height: ' . $widgetHeight . '; overflow-y: auto;' : '' }}">

        {{-- Loading overlay (shown during AJAX) --}}
        <div class="widget-loading d-none" id="{{ $widgetId }}-loading">
            <div class="d-flex flex-column align-items-center justify-content-center py-4">
                <div class="spinner-border spinner-border-sm text-primary mb-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <small class="text-muted">Loading data...</small>
            </div>
        </div>

        {{-- Error message (shown on AJAX failure) --}}
        <div class="widget-error d-none" id="{{ $widgetId }}-error">
            <div class="text-center py-4">
                <i class="bi bi-exclamation-triangle text-warning fs-4 d-block mb-2"></i>
                <small class="text-muted">Failed to load data.</small>
                <br>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2 widget-refresh-btn" data-widget-id="{{ $widgetId }}">
                    <i class="bi bi-arrow-clockwise me-1"></i> Retry
                </button>
            </div>
        </div>

        {{-- Widget content area --}}
        <div class="widget-content" id="{{ $widgetId }}-content">
            {!! $widgetContent !!}
        </div>
    </div>
</div>
