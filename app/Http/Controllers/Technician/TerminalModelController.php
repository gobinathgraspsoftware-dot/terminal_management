<?php

namespace App\Http\Controllers\Technician;

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
     * Display a listing of terminal models (mobile-friendly)
     */
    public function index(): View
    {
        $this->authorize('viewAny', TerminalModel::class);

        $statistics = $this->terminalModelService->getStatistics();
        $categories = $this->terminalModelService->getActiveCategories();

        return view('technician.terminal_models.index', compact('statistics', 'categories'));
    }

    /**
     * DataTables server-side processing
     */
    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TerminalModel::class);

        $query = TerminalModel::with(['category'])
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
            ->addColumn('serial_tracking_badge', function ($model) {
                return $model->is_serial_tracked
                    ? '<span class="badge bg-primary"><i class="bi bi-check-circle"></i> Serial Tracked</span>'
                    : '<span class="badge bg-secondary">No Tracking</span>';
            })
            ->addColumn('image_preview', function ($model) {
                if ($model->image_path) {
                    return '<img src="' . asset('storage/' . $model->image_path) . '" alt="' . e($model->model_name) . '" class="img-thumbnail" style="max-width: 60px; max-height: 60px;">';
                }
                return '<i class="bi bi-image text-muted" style="font-size: 2rem;"></i>';
            })
            ->addColumn('actions', function ($model) {
                return '<a href="' . route('technician.terminal-models.show', $model) . '"
                    class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> View
                </a>';
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
            ->rawColumns(['serial_tracking_badge', 'image_preview', 'actions'])
            ->make(true);
    }

    /**
     * Display the specified terminal model (mobile-friendly)
     */
    public function show(TerminalModel $terminalModel): View
    {
        $this->authorize('view', $terminalModel);

        $terminalModel->load(['category']);

        // Get accessory models if default_accessories is set
        $accessories = [];
        if ($terminalModel->default_accessories && is_array($terminalModel->default_accessories)) {
            $accessories = TerminalModel::whereIn('id', $terminalModel->default_accessories)->get();
        }

        return view('technician.terminal_models.show', compact('terminalModel', 'accessories'));
    }
}
