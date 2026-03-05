<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\TerminalModel;
use App\Services\TerminalModelService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class TerminalModelController extends Controller
{
    use AuthorizesRequests;

    protected TerminalModelService $terminalModelService;

    public function __construct(TerminalModelService $terminalModelService)
    {
        $this->terminalModelService = $terminalModelService;
    }

    public function index(): View
    {
        $this->authorize('viewAny', TerminalModel::class);

        $statistics = $this->terminalModelService->getStatistics();
        $categories = $this->terminalModelService->getActiveCategories();

        return view('supervisor.terminal_models.index', compact('statistics', 'categories'));
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TerminalModel::class);

        $query = TerminalModel::with(['category'])
            ->where('status', 'active')
            ->select('terminal_models.*');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return DataTables::of($query)
            ->addColumn('category_name', fn($model) => $model->category ? $model->category->category_name : '-')
            ->addColumn('status_badge', fn($model) => $model->status === 'active'
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>')
            ->addColumn('serial_tracking_badge', fn($model) => $model->is_serial_tracked
                ? '<span class="badge bg-primary"><i class="bi bi-check-circle"></i> Yes</span>'
                : '<span class="badge bg-secondary">No</span>')
            ->addColumn('stock_level', function ($model) {
                $stockSummary = $this->terminalModelService->getStockSummary($model);
                $total = $stockSummary['total_overall'];
                if ($total == 0) return '<span class="badge bg-danger">Out of Stock</span>';
                if ($total < 10) return '<span class="badge bg-warning text-dark">' . number_format($total, 0) . ' units</span>';
                return '<span class="badge bg-success">' . number_format($total, 0) . ' units</span>';
            })
            ->addColumn('image_preview', function ($model) {
                if ($model->image_path && Storage::disk('public')->exists($model->image_path)) {
                    $url = Storage::url($model->image_path);
                    return '<img src="' . $url . '" alt="' . e($model->model_name) . '" class="img-thumbnail" style="max-width: 50px; max-height: 50px; object-fit: cover;">';
                }
                return '<i class="bi bi-image text-muted" style="font-size: 1.5rem;"></i>';
            })
            ->addColumn('actions', fn($model) => '<a href="' . route('supervisor.terminal-models.show', $model) . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>')
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

    public function show(TerminalModel $terminalModel): View
    {
        $this->authorize('view', $terminalModel);

        $terminalModel->load(['category']);

        $stockSummary = $this->terminalModelService->getStockSummary($terminalModel);
        $recentMovements = $this->terminalModelService->getRecentMovements($terminalModel);

        $accessories = collect();
        if ($terminalModel->default_accessories && is_array($terminalModel->default_accessories)) {
            $accessoryIds = array_filter($terminalModel->default_accessories);
            if (!empty($accessoryIds)) {
                $accessories = TerminalModel::whereIn('id', $accessoryIds)->get();
            }
        }

        return view('supervisor.terminal_models.show', compact('terminalModel', 'stockSummary', 'recentMovements', 'accessories'));
    }
}
