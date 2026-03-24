{{--
    Reusable Dashboard Widget Partial
    Path: resources/views/partials/dashboard-widget.blade.php

    Usage:
    @include('partials.dashboard-widget', [
        'icon'        => 'clipboard-check',
        'iconBg'      => 'primary',
        'value'       => 42,
        'label'       => 'Pending Jobs',
        'link'        => route('admin.tickets.index'),   // optional
        'title'       => 'Widget Title',                  // optional
        'refreshable' => true,                             // optional
        'widgetId'    => 'pending_jobs',                   // required if refreshable
    ])
--}}

@php
    $icon        = $icon ?? 'box-seam';
    $iconBg      = $iconBg ?? 'primary';
    $value       = $value ?? 0;
    $label       = $label ?? '';
    $link        = $link ?? '';
    $title       = $title ?? '';
    $refreshable = $refreshable ?? false;
    $widgetId    = $widgetId ?? '';
@endphp

<div class="card h-100" @if($widgetId) id="widget-{{ $widgetId }}" @endif>
    @if($title)
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <small class="fw-semibold text-muted">{{ $title }}</small>
            @if($refreshable && $widgetId)
                <button type="button" class="btn btn-sm btn-link text-muted p-0 refresh-widget" data-widget-id="{{ $widgetId }}" title="Refresh">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            @endif
        </div>
    @endif
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <div class="bg-{{ $iconBg }} bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                    <i class="bi bi-{{ $icon }} text-{{ $iconBg }}" style="font-size: 1.4rem;"></i>
                </div>
            </div>
            <div class="flex-grow-1 ms-3">
                <div class="stats-value fs-3 fw-bold text-dark" @if($widgetId) id="value-{{ $widgetId }}" @endif>
                    {{ $value }}
                </div>
                @if($label)
                    <div class="stats-label text-muted small">{{ $label }}</div>
                @endif
            </div>
            @if($link)
                <div class="ms-2">
                    <a href="{{ $link }}" class="btn btn-sm btn-outline-{{ $iconBg }}" title="View Details">
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            @endif
            @if(!$title && $refreshable && $widgetId)
                <div class="ms-2">
                    <button type="button" class="btn btn-sm btn-link text-muted p-0 refresh-widget" data-widget-id="{{ $widgetId }}" title="Refresh">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
