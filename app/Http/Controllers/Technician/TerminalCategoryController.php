<?php

namespace App\Http\Controllers\Technician;

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
 * Technician Terminal Category Controller
 *
 * View-only access to terminal categories for field reference
 *
 * @package App\Http\Controllers\Technician
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
            new Middleware('role:technician'),
        ];
    }

    /**
     * Display listing of active terminal categories (view-only).
     */
    public function index(): View
    {
        Gate::authorize('view', TerminalCategory::class);

        $categories = TerminalCategory::withCount('terminalModels')
            ->where('status', 'active')
            ->orderBy('category_type')
            ->orderBy('category_name')
            ->get()
            ->groupBy('category_type');

        return view('technician.terminal-categories.index', compact('categories'));
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
                $text = $category->is_serial_tracked ? 'Required' : 'Not Required';
                $color = $category->is_serial_tracked ? 'text-success' : 'text-muted';
                return '<span class="' . $color . '">' . $text . '</span>';
            })
            ->addColumn('model_count', function ($category) {
                return $category->terminal_models_count . ' models';
            })
            ->rawColumns(['type_badge', 'serial_tracking'])
            ->make(true);
    }
}
