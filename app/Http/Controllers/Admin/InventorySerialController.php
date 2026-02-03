<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventorySerial;
use App\Models\TerminalModel;
use App\Models\Depot;
use App\Services\InventorySerialService;
use App\Exports\InventorySerialsExport;
use App\Http\Requests\StoreInventorySerialRequest;
use App\Http\Requests\UpdateInventorySerialRequest;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel;

class InventorySerialController extends Controller
{
    use AuthorizesRequests;

    protected InventorySerialService $service;

    public function __construct(InventorySerialService $service)
    {
        $this->service = $service;
    }

    /**
     * Display listing with DataTable.
     */
    public function index(Request $request)
    {
        $this->authorize('view_inventory');

        if ($request->ajax()) {
            return $this->service->getDataTable($request, 'admin');
        }

        $stats = $this->service->getStatistics();
        $filterOptions = $this->service->getFilterOptions();

        return view('admin.inventory-serials.index', compact('stats', 'filterOptions'));
    }

    /**
     * DataTable AJAX endpoint.
     */
    public function datatable(Request $request)
    {
        $this->authorize('view_inventory');
        return $this->service->getDataTable($request, 'admin');
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $this->authorize('create_inventory');

        $models = TerminalModel::where('status', 'active')->orderBy('model_name')->get();
        $depots = Depot::active()->orderBy('depot_name')->get();

        return view('admin.inventory-serials.create', compact('models', 'depots'));
    }

    /**
     * Store new serial.
     */
    public function store(StoreInventorySerialRequest $request)
    {
        $this->authorize('create_inventory');

        try {
            $serial = $this->service->create($request->validated());

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Serial [{$serial->serial_no}] created successfully.",
                    'data'    => $serial,
                ]);
            }

            return redirect()
                ->route('admin.inventory-serials.show', $serial->id)
                ->with('success', "Serial [{$serial->serial_no}] created successfully.");
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show serial detail with full history.
     */
    public function show(InventorySerial $inventorySerial)
    {
        $this->authorize('view_inventory');

        $detail = $this->service->getSerialDetail($inventorySerial->id);

        return view('admin.inventory-serials.show', $detail);
    }

    /**
     * Show edit form.
     */
    public function edit(InventorySerial $inventorySerial)
    {
        $this->authorize('edit_inventory');

        $models = TerminalModel::where('status', 'active')->orderBy('model_name')->get();
        $depots = Depot::active()->orderBy('depot_name')->get();

        return view('admin.inventory-serials.edit', [
            'serial' => $inventorySerial,
            'models' => $models,
            'depots' => $depots,
        ]);
    }

    /**
     * Update serial.
     */
    public function update(UpdateInventorySerialRequest $request, InventorySerial $inventorySerial)
    {
        $this->authorize('edit_inventory');

        try {
            $this->service->update($inventorySerial, $request->validated());

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Serial [{$inventorySerial->serial_no}] updated successfully.",
                ]);
            }

            return redirect()
                ->route('admin.inventory-serials.show', $inventorySerial->id)
                ->with('success', "Serial [{$inventorySerial->serial_no}] updated successfully.");
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete serial (soft).
     */
    public function destroy(Request $request, InventorySerial $inventorySerial)
    {
        $this->authorize('delete_inventory');

        try {
            $serialNo = $inventorySerial->serial_no;
            $this->service->delete($inventorySerial);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Serial [{$serialNo}] deleted successfully.",
                ]);
            }

            return redirect()
                ->route('admin.inventory-serials.index')
                ->with('success', "Serial [{$serialNo}] deleted successfully.");
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Restore soft-deleted serial.
     */
    public function restore(Request $request, int $id)
    {
        $serial = InventorySerial::withTrashed()->findOrFail($id);
        $this->authorize('delete_inventory');

        $serial->restore();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Serial [{$serial->serial_no}] restored successfully.",
            ]);
        }

        return back()->with('success', "Serial [{$serial->serial_no}] restored successfully.");
    }

    /**
     * Lookup serial by serial_no (AJAX – barcode scanner).
     */
    public function lookup(Request $request)
    {
        $this->authorize('view_inventory');

        $serialNo = $request->input('serial_no');

        if (empty($serialNo)) {
            return response()->json(['exists' => false, 'serial' => null]);
        }

        $serial = InventorySerial::where('serial_no', $serialNo)
            ->with('model.category')
            ->first();

        if ($serial) {
            return response()->json([
                'exists' => true,
                'serial' => [
                    'id'        => $serial->id,
                    'serial_no' => $serial->serial_no,
                    'model'     => $serial->model->model_name ?? '-',
                    'category'  => $serial->model->category->category_name ?? '-',
                    'status'    => $serial->current_status,
                ],
            ]);
        }

        return response()->json(['exists' => false, 'serial' => null]);
    }

    /**
     * Export inventory serials to Excel with current filter state.
     */
    public function export(Request $request)
    {
        $this->authorize('view_inventory');

        $filters = $request->only([
            'status',
            'location_type',
            'depot_id',
            'model_id',
            'category_id',
            'warranty_status',
            'search',
        ]);

        $filename = 'inventory_serials_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new InventorySerialsExport($filters), $filename);
    }
}
