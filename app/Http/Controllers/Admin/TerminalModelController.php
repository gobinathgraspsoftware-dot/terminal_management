<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTerminalModelRequest;
use App\Http\Requests\Admin\UpdateTerminalModelRequest;
use App\Models\TerminalModel;
use App\Services\TerminalModelService;
use App\Exports\TerminalModelsExport;
use App\Imports\TerminalModelsImport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
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

        return view('admin.terminal_models.index', compact('statistics', 'categories'));
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TerminalModel::class);

        $query = TerminalModel::with(['category'])
            ->withCount('inventorySerials')
            ->select('terminal_models.*');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_serial_tracked')) {
            $query->where('is_serial_tracked', $request->is_serial_tracked);
        }

        if ($request->get('show_trashed') === 'true') {
            $query->withTrashed();
        }

        return DataTables::of($query)
            ->addColumn('category_name', fn($model) => $model->category ? $model->category->category_name : '-')
            ->addColumn('status_badge', function ($model) {
                if ($model->trashed()) {
                    return '<span class="badge bg-danger">Deleted</span>';
                }
                return $model->status === 'active'
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';
            })
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
                // Use Storage::url() - same pattern as profile avatar
                if ($model->image_path && Storage::disk('public')->exists($model->image_path)) {
                    $url = Storage::url($model->image_path);
                    return '<img src="' . $url . '" alt="' . e($model->model_name) . '" class="img-thumbnail" style="max-width: 50px; max-height: 50px; object-fit: cover;">';
                }
                return '<i class="bi bi-image text-muted" style="font-size: 1.5rem;"></i>';
            })
            ->addColumn('actions', fn($model) => $this->getActionButtons($model))
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $request->search['value']) {
                    $searchValue = $request->search['value'];
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('model_code', 'like', "%{$searchValue}%")
                            ->orWhere('model_name', 'like', "%{$searchValue}%")
                            ->orWhere('brand', 'like', "%{$searchValue}%")
                            ->orWhere('description', 'like', "%{$searchValue}%");
                    });
                }
            })
            ->rawColumns(['status_badge', 'serial_tracking_badge', 'stock_level', 'image_preview', 'actions'])
            ->make(true);
    }

    public function create(): View
    {
        $this->authorize('create', TerminalModel::class);

        $categories = $this->terminalModelService->getActiveCategories();
        $accessoryModels = $this->terminalModelService->getAccessoryModels();
        $modelCode = $this->terminalModelService->generateModelCode();

        return view('admin.terminal_models.create', compact('categories', 'accessoryModels', 'modelCode'));
    }

    public function store(StoreTerminalModelRequest $request)
    {
        try {
            $terminalModel = $this->terminalModelService->create($request->validated());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Terminal model created successfully.',
                    'data' => $terminalModel,
                ]);
            }

            return redirect()
                ->route('admin.terminal-models.show', $terminalModel)
                ->with('success', 'Terminal model created successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to create terminal model', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to create terminal model: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Failed to create terminal model: ' . $e->getMessage());
        }
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

        return view('admin.terminal_models.show', compact('terminalModel', 'stockSummary', 'recentMovements', 'accessories'));
    }

    public function edit(TerminalModel $terminalModel): View
    {
        $this->authorize('update', $terminalModel);

        $terminalModel->load(['category']);

        $categories = $this->terminalModelService->getActiveCategories();
        $accessoryModels = $this->terminalModelService->getAccessoryModels();

        return view('admin.terminal_models.edit', compact('terminalModel', 'categories', 'accessoryModels'));
    }

    public function update(UpdateTerminalModelRequest $request, TerminalModel $terminalModel)
    {
        try {
            $terminalModel = $this->terminalModelService->update($terminalModel, $request->validated());

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Terminal model updated successfully.', 'data' => $terminalModel]);
            }

            return redirect()->route('admin.terminal-models.show', $terminalModel)->with('success', 'Terminal model updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update terminal model', ['id' => $terminalModel->id, 'error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update terminal model: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Failed to update terminal model: ' . $e->getMessage());
        }
    }

    public function destroy(TerminalModel $terminalModel): JsonResponse
    {
        $this->authorize('delete', $terminalModel);

        try {
            $this->terminalModelService->delete($terminalModel);
            return response()->json(['success' => true, 'message' => 'Terminal model deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete terminal model: ' . $e->getMessage()], 500);
        }
    }

    public function toggleStatus(TerminalModel $terminalModel): JsonResponse
    {
        $this->authorize('update', $terminalModel);

        try {
            $terminalModel = $this->terminalModelService->toggleStatus($terminalModel);
            return response()->json(['success' => true, 'message' => 'Status updated successfully.', 'status' => $terminalModel->status]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update status: ' . $e->getMessage()], 500);
        }
    }

    public function deleteImage(TerminalModel $terminalModel): JsonResponse
    {
        $this->authorize('update', $terminalModel);

        try {
            $this->terminalModelService->deleteModelImage($terminalModel);
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete image: ' . $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', TerminalModel::class);

        $filters = [
            'category_id' => $request->category_id,
            'status' => $request->status,
            'search' => $request->search,
        ];

        return Excel::download(new TerminalModelsExport($filters), 'terminal_models_' . date('Y-m-d_His') . '.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', TerminalModel::class);

        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:5120']);

        try {
            $import = new TerminalModelsImport();
            Excel::import($import, $request->file('file'));

            $message = "Import completed. Imported: {$import->getImportedCount()}, Updated: {$import->getUpdatedCount()}";
            $errors = $import->getErrors();
            if (count($errors) > 0) {
                $message .= ". Errors: " . implode('; ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => ['imported' => $import->getImportedCount(), 'updated' => $import->getUpdatedCount(), 'errors' => $errors],
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    protected function getActionButtons(TerminalModel $model): string
    {
        $buttons = '<div class="btn-group btn-group-sm" role="group">';
        $buttons .= '<a href="' . route('admin.terminal-models.show', $model) . '" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>';
        $buttons .= '<a href="' . route('admin.terminal-models.edit', $model) . '" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>';
        if (!$model->trashed()) {
            $buttons .= '<button type="button" class="btn btn-outline-danger delete-btn" data-id="' . $model->id . '" title="Delete"><i class="bi bi-trash"></i></button>';
        }
        $buttons .= '</div>';
        return $buttons;
    }
}
