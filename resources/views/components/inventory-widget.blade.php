{{--
    Reusable Inventory Dashboard Widget Component
    Usage:
    <x-widgets.inventory-widget
        id="widgetId"
        title="Widget Title"
        icon="bi-box-seam"
        :ajaxUrl="route('admin.inventory-dashboard.stock-by-category')"
        :refreshInterval="60"
    >
        <x-slot name="actions">Optional action buttons</x-slot>
        Widget content (can be overridden)
    </x-widgets.inventory-widget>
--}}

@props([
    'id' => 'widget-' . uniqid(),
    'title' => 'Widget',
    'icon' => 'bi-box-seam',
    'ajaxUrl' => null,
    'refreshInterval' => 0,
    'height' => 'auto',
    'color' => 'primary',
    'collapsible' => false,
])

<div class="card inventory-widget" id="{{ $id }}" data-ajax-url="{{ $ajaxUrl }}" data-refresh-interval="{{ $refreshInterval }}">
    {{-- Widget Header --}}
    <div class="card-header d-flex align-items-center justify-content-between py-2">
        <div class="d-flex align-items-center">
            <i class="bi {{ $icon }} me-2 text-{{ $color }}"></i>
            <h6 class="mb-0 fw-semibold">{{ $title }}</h6>
        </div>
        <div class="d-flex align-items-center gap-2">
            {{-- Optional action slot --}}
            @isset($actions)
                {{ $actions }}
            @endisset

            {{-- Refresh button --}}
            @if($ajaxUrl)
                <button type="button" class="btn btn-sm btn-outline-secondary widget-refresh-btn" data-widget-id="{{ $id }}" title="Refresh">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            @endif

            {{-- Collapse toggle --}}
            @if($collapsible)
                <button type="button" class="btn btn-sm btn-outline-secondary widget-collapse-btn" data-bs-toggle="collapse" data-bs-target="#{{ $id }}-body" title="Collapse">
                    <i class="bi bi-chevron-up"></i>
                </button>
            @endif
        </div>
    </div>

    {{-- Widget Body --}}
    <div class="card-body position-relative {{ $collapsible ? 'collapse show' : '' }}"
         id="{{ $id }}-body"
         style="{{ $height !== 'auto' ? 'height: ' . $height . '; overflow-y: auto;' : '' }}">

        {{-- Loading overlay (shown during AJAX) --}}
        <div class="widget-loading d-none" id="{{ $id }}-loading">
            <div class="d-flex flex-column align-items-center justify-content-center py-4">
                <div class="spinner-border spinner-border-sm text-primary mb-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <small class="text-muted">Loading data...</small>
            </div>
        </div>

        {{-- Error message (shown on AJAX failure) --}}
        <div class="widget-error d-none" id="{{ $id }}-error">
            <div class="text-center py-4">
                <i class="bi bi-exclamation-triangle text-warning fs-4 d-block mb-2"></i>
                <small class="text-muted">Failed to load data.</small>
                <br>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2 widget-refresh-btn" data-widget-id="{{ $id }}">
                    <i class="bi bi-arrow-clockwise me-1"></i> Retry
                </button>
            </div>
        </div>

        {{-- Widget content area --}}
        <div class="widget-content" id="{{ $id }}-content">
            {{ $slot }}
        </div>
    </div>

    {{-- Widget Footer (optional) --}}
    @isset($footer)
        <div class="card-footer py-2 bg-transparent">
            {{ $footer }}
        </div>
    @endisset
</div>

<style>
    .inventory-widget {
        border: none;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: box-shadow 0.2s;
    }
    .inventory-widget:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .inventory-widget .card-header {
        background: white;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 8px 8px 0 0;
    }
    .inventory-widget .card-header h6 {
        font-size: 0.875rem;
    }
    .inventory-widget .widget-loading {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.85);
        z-index: 5;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .inventory-widget .widget-refresh-btn {
        padding: 0.15rem 0.4rem;
        font-size: 0.75rem;
        line-height: 1;
    }
    .inventory-widget .widget-collapse-btn .bi {
        transition: transform 0.2s;
    }
    .inventory-widget .widget-collapse-btn.collapsed .bi {
        transform: rotate(180deg);
    }
</style>
