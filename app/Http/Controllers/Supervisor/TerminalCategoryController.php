<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\TerminalCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Supervisor Terminal Category Controller
 *
 * View-only access to terminal categories for reference
 *
 * @package App\Http\Controllers\Supervisor
 */
class TerminalCategoryController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:supervisor'),
        ];
    }

    /**
     * Display listing of terminal categories (view-only).
     */
    public function index(): View
    {
        Gate::authorize('view', TerminalCategory::class);

        $categories = TerminalCategory::withCount('terminalModels')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('category_name')
            ->get();

        $stats = [
            'total_categories' => TerminalCategory::where('status', 'active')->count(),
            'serial_tracked' => TerminalCategory::where('status', 'active')
                ->where('is_serial_tracked', true)->count(),
            'total_models' => \App\Models\TerminalModel::where('status', 'active')->count(),
        ];

        return view('supervisor.terminal-categories.index', compact('categories', 'stats'));
    }

    /**
     * Get DataTable data for categories.
     */
    public function datatable(Request $request): JsonResponse
    {
        Gate::authorize('view', TerminalCategory::class);

        $query = TerminalCategory::withCount('terminalModels')
            ->where('status', 'active')
            ->select('terminal_categories.*');

        return DataTables::of($query)
            ->addColumn('type_badge', function ($category) {
                $colors = [
                    'terminal' => 'primary',
                    'router' => 'info',
                    'sim' => 'warning',
                    'accessory' => 'secondary',
                    'other' => 'dark',
                ];
                $color = $colors[$category->category_type] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst($category->category_type) . '</span>';
            })
            ->addColumn('serial_tracking', function ($category) {
                $icon = $category->is_serial_tracked 
                    ? '<i class="bi bi-check-circle-fill text-success"></i>' 
                    : '<i class="bi bi-x-circle-fill text-danger"></i>';
                return $icon;
            })
            ->addColumn('model_count', function ($category) {
                $count = $category->terminal_models_count;
                $badge = $count > 0 ? 'bg-success' : 'bg-secondary';
                return '<span class="badge ' . $badge . '">' . $count . ' models</span>';
            })
            ->rawColumns(['type_badge', 'serial_tracking', 'model_count'])
            ->make(true);
    }
}
