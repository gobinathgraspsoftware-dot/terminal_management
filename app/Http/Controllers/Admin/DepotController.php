<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Depot;
use App\Http\Requests\StoreDepotRequest;
use App\Http\Requests\UpdateDepotRequest;
use App\Services\DepotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DepotController extends Controller
{
    use AuthorizesRequests;

    protected $depotService;

    public function __construct(DepotService $depotService)
    {
        $this->depotService = $depotService;
    }

    /**
     * Display a listing of depots with stock summary.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->depotService->getDataTable($request);
        }

        return view('admin.depots.index');
    }

    /**
     * Show the form for creating a new depot.
     */
    public function create()
    {
        $this->authorize('create_depots');

        return view('admin.depots.create');
    }

    /**
     * Store a newly created depot in storage.
     */
    public function store(StoreDepotRequest $request)
    {
        $this->authorize('create_depots');

        try {
            DB::beginTransaction();

            // Handle default depot logic
            if ($request->is_default) {
                Depot::where('is_default', true)->update(['is_default' => false]);
            }

            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $data['status'] = $request->status ?? 'active';

            $depot = Depot::create($data);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Depot created successfully.',
                    'data' => $depot
                ]);
            }

            return redirect()
                ->route('admin.depots.index')
                ->with('success', 'Depot created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create depot: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create depot: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified depot with stock levels and recent movements.
     */
    public function show(Depot $depot)
    {
        $this->authorize('view_depots');

        // Load relationships
        $depot->load([
            'stockBalances.model.category',
            'stockIssues' => function($query) {
                $query->latest()->take(10);
            }
        ]);

        // Get stock summary
        $stockSummary = $this->depotService->getStockSummary($depot->id);

        // Get recent movements
        $recentMovements = $this->depotService->getRecentMovements($depot->id, 10);

        // Get assigned technicians if regional depot
        $technicians = [];
        if ($depot->depot_type === Depot::TYPE_REGIONAL) {
            $technicians = $this->depotService->getAssignedTechnicians($depot->id);
        }

        return view('admin.depots.show', compact('depot', 'stockSummary', 'recentMovements', 'technicians'));
    }

    /**
     * Show the form for editing the specified depot.
     */
    public function edit(Depot $depot)
    {
        $this->authorize('edit_depots');

        return view('admin.depots.edit', compact('depot'));
    }

    /**
     * Update the specified depot in storage.
     */
    public function update(UpdateDepotRequest $request, Depot $depot)
    {
        $this->authorize('edit_depots');

        try {
            DB::beginTransaction();

            // Handle default depot logic
            if ($request->is_default && !$depot->is_default) {
                Depot::where('is_default', true)
                    ->where('id', '!=', $depot->id)
                    ->update(['is_default' => false]);
            }

            $data = $request->validated();
            $data['updated_by'] = auth()->id();

            $depot->update($data);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Depot updated successfully.',
                    'data' => $depot
                ]);
            }

            return redirect()
                ->route('admin.depots.index')
                ->with('success', 'Depot updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update depot: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update depot: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified depot from storage.
     */
    public function destroy(Depot $depot)
    {
        $this->authorize('delete_depots');

        try {
            // Check if depot has stock
            $hasStock = $depot->stockBalances()->where('quantity_on_hand', '>', 0)->exists();

            if ($hasStock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete depot with existing stock. Please transfer stock first.'
                ], 422);
            }

            // Check if it's the default depot
            if ($depot->is_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the default depot. Please set another depot as default first.'
                ], 422);
            }

            $depot->delete();

            return response()->json([
                'success' => true,
                'message' => 'Depot deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete depot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted depot.
     */
    public function restore($id)
    {
        $this->authorize('delete_depots');

        try {
            $depot = Depot::withTrashed()->findOrFail($id);
            $depot->restore();

            return response()->json([
                'success' => true,
                'message' => 'Depot restored successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore depot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock summary for a specific depot (AJAX).
     */
    public function stockSummary(Depot $depot)
    {
        $this->authorize('view_depots');

        $summary = $this->depotService->getStockSummary($depot->id);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get recent movements for a specific depot (AJAX).
     */
    public function movements(Depot $depot, Request $request)
    {
        $this->authorize('view_depots');

        $limit = $request->get('limit', 20);
        $movements = $this->depotService->getRecentMovements($depot->id, $limit);

        return response()->json([
            'success' => true,
            'data' => $movements
        ]);
    }
}
