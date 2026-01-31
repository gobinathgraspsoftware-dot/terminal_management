<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\TerminalModel;
use App\Services\TerminalModelService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class TerminalModelController extends Controller
{
    protected TerminalModelService $terminalModelService;

    public function __construct(TerminalModelService $terminalModelService)
    {
        $this->terminalModelService = $terminalModelService;
    }

    /**
     * Display a listing of terminal models
     */
    public function index(): View
    {
        $this->authorize('viewAny', TerminalModel::class);

        $statistics = $this->terminalModelService->getStatistics();
        $categories = $this->terminalModelService->getActiveCategories();

        return view('supervisor.terminal_models.index', compact('statistics', 'categories'));
    }

    /**
     * DataTables server-side processing
     */
    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TerminalModel::class);

        $query = TerminalModel::with(['category'])
            ->withCount('inventorySerials')
            ->where('status', TerminalModel::STATUS_ACTIVE)
            ->select('terminal_models.*');

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return DataTables::of($query)
            ->addColumn('category_name', function ($model) {
                return $model->category ? $model->category->category_name : '-';
            })
            ->addColumn('status_badge', function ($model) {
                return '<span class="badge bg-success">Active</span>';
            })
            ->addColumn('serial_tracking_badge', function ($model) {
                return $model->is_serial_tracked
                    ? '<span class="badge bg-primary"><i class="bi bi-check-circle"></i> Yes</span>'
                    : '<span class="badge bg-secondary">No</span>';
            })
            ->addColumn('stock_level', function ($model) {
                $stockSummary = $this->terminalModelService->getStockSummary($model);
                $total = $stockSummary['total_overall'];

                if ($total == 0) {
                    return '<span class="badge bg-danger">Out of Stock</span>';
                } elseif ($total < 10) {
                    return '<span class="badge bg-warning text-dark">' . $total . ' units</span>';
                } else {
                    return '<span class="badge bg-success">' . $total . ' units</span>';
                }
            })
            ->addColumn('image_preview', function ($model) {
                if ($model->image_path) {
                    return '<img src="' . asset('storage/' . $model->image_path) . '" alt="' . e($model->model_name) . '" class="img-thumbnail" style="max-width: 50px; max-height: 50px;">';
                }
                return '<span class="text-muted">No image</span>';
            })
            ->addColumn('actions', function ($model) {
                $buttons = '<div class="btn-group btn-group-sm" role="group">';
                $buttons .= '<a href="' . route('supervisor.terminal-models.show', $model) . '"
                    class="btn btn-outline-primary" title="View">
                    <i class="bi bi-eye"></i>
                </a>';
                $buttons .= '</div>';
                return $buttons;
            })
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $request->search['value']) {
                    $searchValue = $request->search['value'];
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('model_code', 'like', "%{$searchValue}%")
                            ->orWhere('model_name', 'like', "%{$searchValue}%")
                            ->orWhere('brand', 'like', "%{$searchValue}%");
                    });
                }
            })
            ->rawColumns(['status_badge', 'serial_tracking_badge', 'stock_level', 'image_preview', 'actions'])
            ->make(true);
    }

    /**
     * Display the specified terminal model
     */
    public function show(TerminalModel $terminalModel): View
    {
        $this->authorize('view', $terminalModel);

        $terminalModel->load(['category']);

        $stockSummary = $this->terminalModelService->getStockSummary($terminalModel);
        $recentMovements = $this->terminalModelService->getRecentMovements($terminalModel);

        // Get accessory models if default_accessories is set
        $accessories = [];
        if ($terminalModel->default_accessories && is_array($terminalModel->default_accessories)) {
            $accessories = TerminalModel::whereIn('id', $terminalModel->default_accessories)->get();
        }

        return view('supervisor.terminal_models.show', compact('terminalModel', 'stockSummary', 'recentMovements', 'accessories'));
    }
}
