<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTerminalModelRequest;
use App\Http\Requests\Admin\UpdateTerminalModelRequest;
use App\Models\TerminalModel;
use App\Services\TerminalModelService;
use App\Exports\TerminalModelsExport;
use App\Imports\TerminalModelsImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
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

        return view('admin.terminal_models.index', compact('statistics', 'categories'));
    }

    /**
     * DataTables server-side processing
     */
    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TerminalModel::class);

        $query = TerminalModel::with(['category'])
            ->withCount('inventorySerials')
            ->select('terminal_models.*');

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_serial_tracked')) {
            $query->where('is_serial_tracked', $request->is_serial_tracked);
        }

        // Include trashed if requested
        if ($request->get('show_trashed') === 'true') {
            $query->withTrashed();
        }

        return DataTables::of($query)
            ->addColumn('category_name', function ($model) {
                return $model->category ? $model->category->category_name : '-';
            })
            ->addColumn('status_badge', function ($model) {
                if ($model->trashed()) {
                    return '<span class="badge bg-danger">Deleted</span>';
                }
                return $model->status === 'active'
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';
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
                return $this->getActionButtons($model);
            })
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

    /**
     * Show the form for creating a new terminal model
     */
    public function create(): View
    {
        $this->authorize('create', TerminalModel::class);

        $categories = $this->terminalModelService->getActiveCategories();
        $accessoryModels = $this->terminalModelService->getAccessoryModels();
        $modelCode = $this->terminalModelService->generateModelCode();

        return view('admin.terminal_models.create', compact('categories', 'accessoryModels', 'modelCode'));
    }

    /**
     * Store a newly created terminal model
     */
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
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create terminal model: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create terminal model: ' . $e->getMessage());
        }
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

        return view('admin.terminal_models.show', compact('terminalModel', 'stockSummary', 'recentMovements', 'accessories'));
    }

    /**
     * Show the form for editing the specified terminal model
     */
    public function edit(TerminalModel $terminalModel): View
    {
        $this->authorize('update', $terminalModel);

        $terminalModel->load(['category']);

        $categories = $this->terminalModelService->getActiveCategories();
        $accessoryModels = $this->terminalModelService->getAccessoryModels();

        return view('admin.terminal_models.edit', compact('terminalModel', 'categories', 'accessoryModels'));
    }

    /**
     * Update the specified terminal model
     */
    public function update(UpdateTerminalModelRequest $request, TerminalModel $terminalModel)
    {
        try {
            $terminalModel = $this->terminalModelService->update($terminalModel, $request->validated());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Terminal model updated successfully.',
                    'data' => $terminalModel,
                ]);
            }

            return redirect()
                ->route('admin.terminal-models.show', $terminalModel)
                ->with('success', 'Terminal model updated successfully.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update terminal model: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update terminal model: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified terminal model
     */
    public function destroy(TerminalModel $terminalModel)
    {
        $this->authorize('delete', $terminalModel);

        try {
            $this->terminalModelService->delete($terminalModel);

            return response()->json([
                'success' => true,
                'message' => 'Terminal model deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete terminal model: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status
     */
    public function toggleStatus(TerminalModel $terminalModel): JsonResponse
    {
        $this->authorize('update', $terminalModel);

        try {
            $terminalModel = $this->terminalModelService->toggleStatus($terminalModel);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'status' => $terminalModel->status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete model image
     */
    public function deleteImage(TerminalModel $terminalModel): JsonResponse
    {
        $this->authorize('update', $terminalModel);

        try {
            $this->terminalModelService->deleteModelImage($terminalModel);

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export terminal models
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', TerminalModel::class);

        $filters = [
            'category_id' => $request->category_id,
            'status' => $request->status,
            'search' => $request->search,
        ];

        return Excel::download(
            new TerminalModelsExport($filters),
            'terminal_models_' . date('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Import terminal models
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', TerminalModel::class);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $import = new TerminalModelsImport();
            Excel::import($import, $request->file('file'));

            $message = "Import completed. ";
            $message .= "Imported: {$import->getImportedCount()}, ";
            $message .= "Updated: {$import->getUpdatedCount()}";

            $errors = $import->getErrors();
            if (count($errors) > 0) {
                $message .= ". Errors: " . implode('; ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'imported' => $import->getImportedCount(),
                    'updated' => $import->getUpdatedCount(),
                    'errors' => $errors,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get action buttons for DataTables
     */
    protected function getActionButtons(TerminalModel $model): string
    {
        $buttons = '<div class="btn-group btn-group-sm" role="group">';

        // View button
        if (Auth::user()->can('view', $model)) {
            $buttons .= '<a href="' . route('admin.terminal-models.show', $model) . '"
                class="btn btn-outline-primary" title="View">
                <i class="bi bi-eye"></i>
            </a>';
        }

        // Edit button
        if (Auth::user()->can('update', $model)) {
            $buttons .= '<a href="' . route('admin.terminal-models.edit', $model) . '"
                class="btn btn-outline-warning" title="Edit">
                <i class="bi bi-pencil"></i>
            </a>';
        }

        // Delete button
        if (Auth::user()->can('delete', $model) && !$model->trashed()) {
            $buttons .= '<button type="button" class="btn btn-outline-danger delete-btn"
                data-id="' . $model->id . '" title="Delete">
                <i class="bi bi-trash"></i>
            </button>';
        }

        $buttons .= '</div>';

        return $buttons;
    }
}
