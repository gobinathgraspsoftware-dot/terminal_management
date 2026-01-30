<?php

namespace App\Helpers;

class DataTableHelper
{
    /**
     * Get default DataTable configuration
     */
    public static function getDefaultConfig(): array
    {
        return [
            'processing' => true,
            'serverSide' => true,
            'responsive' => true,
            'pageLength' => 25,
            'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            'dom' => '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            'language' => [
                'processing' => '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                'emptyTable' => 'No data available',
                'zeroRecords' => 'No matching records found',
                'lengthMenu' => 'Show _MENU_ entries',
                'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                'infoEmpty' => 'Showing 0 to 0 of 0 entries',
                'infoFiltered' => '(filtered from _MAX_ total entries)',
                'search' => 'Search:',
                'paginate' => [
                    'first' => '«',
                    'last' => '»',
                    'next' => '›',
                    'previous' => '‹'
                ]
            ],
        ];
    }

    /**
     * Get configuration for export buttons
     */
    public static function getExportConfig(): array
    {
        return [
            'buttons' => [
                'copy',
                'csv',
                'excel',
                'pdf',
                'print'
            ],
            'dom' => 'Bfrtip'
        ];
    }

    /**
     * Get configuration for column visibility
     */
    public static function getColumnVisibilityConfig(): array
    {
        return [
            'buttons' => [
                'colvis'
            ]
        ];
    }

    /**
     * Format date for DataTable display
     */
    public static function formatDate($date, string $format = 'Y-m-d H:i'): string
    {
        if (!$date) {
            return '-';
        }

        if ($date instanceof \Carbon\Carbon) {
            return $date->format($format);
        }

        return \Carbon\Carbon::parse($date)->format($format);
    }

    /**
     * Format currency for DataTable display
     */
    public static function formatCurrency(float $amount, string $currency = 'MYR'): string
    {
        $symbol = match($currency) {
            'MYR' => 'RM',
            'USD' => '$',
            'SGD' => 'S$',
            'EUR' => '€',
            default => $currency
        };

        return $symbol . ' ' . number_format($amount, 2);
    }

    /**
     * Format boolean for DataTable display
     */
    public static function formatBoolean(bool $value, string $trueText = 'Yes', string $falseText = 'No'): string
    {
        return $value ? 
            '<span class="badge bg-success">' . $trueText . '</span>' : 
            '<span class="badge bg-secondary">' . $falseText . '</span>';
    }

    /**
     * Get action buttons HTML
     */
    public static function getActionButtons(array $buttons): string
    {
        $html = '<div class="btn-group btn-group-sm" role="group">';
        
        foreach ($buttons as $button) {
            if ($button['permission'] ?? true) {
                $class = $button['class'] ?? 'btn-primary';
                $icon = $button['icon'] ?? '';
                $title = $button['title'] ?? '';
                $action = $button['action'] ?? '#';
                $dataAttributes = '';
                
                if (isset($button['data'])) {
                    foreach ($button['data'] as $key => $value) {
                        $dataAttributes .= " data-{$key}=\"{$value}\"";
                    }
                }
                
                $html .= "<a href=\"{$action}\" class=\"btn {$class}\" title=\"{$title}\"{$dataAttributes}>";
                $html .= "<i class=\"bi bi-{$icon}\"></i>";
                $html .= "</a>";
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Apply standard filters to query
     */
    public static function applyFilters($query, array $filters): void
    {
        foreach ($filters as $column => $value) {
            if (empty($value)) {
                continue;
            }

            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        }
    }

    /**
     * Apply date range filter to query
     */
    public static function applyDateRangeFilter($query, string $column, ?string $startDate, ?string $endDate): void
    {
        if ($startDate) {
            $query->whereDate($column, '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate($column, '<=', $endDate);
        }
    }

    /**
     * Apply search filter to query
     */
    public static function applySearchFilter($query, array $searchableColumns, string $searchValue): void
    {
        $query->where(function ($q) use ($searchableColumns, $searchValue) {
            foreach ($searchableColumns as $column) {
                $q->orWhere($column, 'like', "%{$searchValue}%");
            }
        });
    }
}
