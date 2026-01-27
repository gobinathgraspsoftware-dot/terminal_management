@props([
    'title' => '',
    'icon' => '',
    'iconBg' => 'primary',
    'value' => '',
    'label' => '',
    'trend' => null, // 'up', 'down', or null
    'trendValue' => null,
    'link' => null,
    'loading' => false,
    'refreshable' => false,
    'widgetId' => null
])

<div class="stats-card h-100" @if($refreshable && $widgetId) id="widget-{{ $widgetId }}" @endif>
    @if($title)
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-muted">{{ $title }}</h6>
            @if($refreshable && $widgetId)
                <button class="btn btn-sm btn-link text-muted p-0 refresh-widget" data-widget-id="{{ $widgetId }}">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            @endif
        </div>
    @endif

    <div class="d-flex align-items-center">
        @if($icon)
            <div class="stats-icon bg-{{ $iconBg }} bg-opacity-10 text-{{ $iconBg }} me-3">
                <i class="bi bi-{{ $icon }}"></i>
            </div>
        @endif

        <div class="flex-grow-1">
            @if($loading)
                <div class="spinner-border spinner-border-sm text-{{ $iconBg }}" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            @else
                <div class="stats-value" id="value-{{ $widgetId ?? uniqid() }}">
                    {{ $value }}
                </div>
                
                @if($label)
                    <div class="stats-label">{{ $label }}</div>
                @endif

                @if($trend)
                    <div class="mt-2">
                        <span class="badge bg-{{ $trend === 'up' ? 'success' : 'danger' }} bg-opacity-10 
                                     text-{{ $trend === 'up' ? 'success' : 'danger' }}">
                            <i class="bi bi-arrow-{{ $trend }}"></i>
                            {{ $trendValue }}
                        </span>
                    </div>
                @endif
            @endif
        </div>

        @if($link)
            <div class="ms-2">
                <a href="{{ $link }}" class="btn btn-sm btn-outline-{{ $iconBg }}">
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        @endif
    </div>

    @if(isset($slot) && $slot->isNotEmpty())
        <div class="mt-3 pt-3 border-top">
            {{ $slot }}
        </div>
    @endif
</div>

@if($refreshable && $widgetId)
@push('scripts')
<script>
    $(document).ready(function() {
        // Widget refresh functionality
        $('.refresh-widget[data-widget-id="{{ $widgetId }}"]').on('click', function() {
            const widgetId = $(this).data('widget-id');
            const widget = $('#widget-' + widgetId);
            const valueElement = widget.find('.stats-value');
            
            // Show loading
            valueElement.html('<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>');
            
            // Fetch updated data
            $.ajax({
                url: '{{ route("api.widget.data") }}',
                method: 'GET',
                data: { widget: widgetId },
                success: function(response) {
                    if (response.count !== undefined) {
                        valueElement.text(response.count);
                    } else if (response.value !== undefined) {
                        valueElement.text(response.value);
                    }
                },
                error: function() {
                    valueElement.html('<span class="text-danger">Error</span>');
                }
            });
        });
    });
</script>
@endpush
@endif