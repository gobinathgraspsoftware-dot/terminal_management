<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockOutRequest;
use App\Http\Requests\StockReturnRequest;
use App\Models\InventoryItem;
use App\Models\JobCategory;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Ticket;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    protected InventoryService $service;

    public function __construct(InventoryService $service)
    {
        $this->service = $service;
    }

    // ══════════════════════════════════════════════════════════
    // INDEX + DATATABLE
    // ══════════════════════════════════════════════════════════

    /**
     * Inventory items list (view-only for supervisors).
     */
    public function index()
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $stats = $this->service->getSummaryStats();
        $jobCategories = JobCategory::active()->orderBy('category_name')->get();

        return view('supervisor.inventory.index', compact('stats', 'jobCategories'));
    }

    /**
     * DataTable AJAX endpoint.
     */
    public function datatable(Request $request)
    {
        Gate::authorize('viewAny', InventoryItem::class);

        $result = $this->service->getDatatable($request->all());
        return response()->json($result);
    }

    /**
     * AJAX - Get stock for a specific item.
     */
    public function getItemStock(Request $request)
    {
        $request->validate(['item_id' => 'required|exists:inventory_items,id']);

        $stock = $this->service->getItemStock($request->item_id);
        return response()->json($stock);
    }

    // ══════════════════════════════════════════════════════════
    // STOCK IN
    // ══════════════════════════════════════════════════════════

    /**
     * Stock In form.
     */
    public function stockInForm()
    {
        Gate::authorize('stockIn', InventoryItem::class);

        $jobCategories = JobCategory::active()->orderBy('category_name')->get();
        $routerItems = InventoryItem::routers()->active()->orderBy('item_name')->get();
        $accessoryItems = InventoryItem::accessories()->active()->orderBy('item_name')->get();

        return view('supervisor.inventory.stock-in', compact('jobCategories', 'routerItems', 'accessoryItems'));
    }

    /**
     * Process Stock In.
     */
    public function stockIn(StockInRequest $request)
    {
        Gate::authorize('stockIn', InventoryItem::class);

        try {
            $data = $request->validated();

            if ($data['stock_type'] === 'router' && empty($data['inventory_item_id'])) {
                $item = $this->service->createItem([
                    'item_name' => $data['item_name'],
                    'job_category_id' => $data['job_category_id'],
                    'item_type' => InventoryItem::TYPE_ROUTER,
                    'serial_number' => $data['serial_number'],
                    'brand' => $data['brand'] ?? null,
                    'model' => $data['model'] ?? null,
                    'reorder_level' => 1,
                    'status' => 'active',
                ]);
                $data['inventory_item_id'] = $item->id;
            }

            $movement = $this->service->stockIn($data);

            return redirect()
                ->route('supervisor.inventory.stock-in')
                ->with('success', "Stock In {$movement->movement_no} processed successfully.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Stock In failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // STOCK OUT (linked to Ticket)
    // ══════════════════════════════════════════════════════════

    /**
     * Stock Out form.
     */
    public function stockOutForm()
    {
        Gate::authorize('stockOut', InventoryItem::class);

        $availableRouters = $this->service->getAvailableRouters();
        $availableAccessories = $this->service->getAvailableAccessories();

        // Tickets visible to this supervisor
        $supervisorId = auth()->id();
        $tickets = Ticket::where('supervisor_id', $supervisorId)
            ->whereIn('status', [
                Ticket::STATUS_OPEN,
                Ticket::STATUS_ASSIGNED,
                Ticket::STATUS_ACCEPTED,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_SCHEDULED,
            ])
            ->select('id', 'ticket_no', 'merchant_name', 'technician_id')
            ->orderBy('created_at', 'desc')
            ->get();

        // Technicians in this supervisor's team (NOT using team_id)
        $technicians = User::where('supervisor_id', $supervisorId)
            ->role('technician')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id']);

        return view('supervisor.inventory.stock-out', compact(
            'availableRouters', 'availableAccessories', 'tickets', 'technicians'
        ));
    }

    /**
     * Process Stock Out.
     */
    public function stockOut(StockOutRequest $request)
    {
        Gate::authorize('stockOut', InventoryItem::class);

        try {
            $movement = $this->service->stockOut($request->validated());

            return redirect()
                ->route('supervisor.inventory.stock-out')
                ->with('success', "Stock Out {$movement->movement_no} processed successfully.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Stock Out failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // STOCK RETURN
    // ══════════════════════════════════════════════════════════

    /**
     * Stock Return form.
     */
    public function stockReturnForm()
    {
        Gate::authorize('stockReturn', InventoryItem::class);

        $allItems = InventoryItem::active()->orderBy('item_name')->get();
        $supervisorId = auth()->id();
        $technicians = User::where('supervisor_id', $supervisorId)
            ->role('technician')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id']);

        return view('supervisor.inventory.stock-return', compact('allItems', 'technicians'));
    }

    /**
     * Process Stock Return.
     */
    public function stockReturn(StockReturnRequest $request)
    {
        Gate::authorize('stockReturn', InventoryItem::class);

        try {
            $movement = $this->service->stockReturn($request->validated());

            return redirect()
                ->route('supervisor.inventory.stock-return')
                ->with('success', "Stock Return {$movement->movement_no} processed successfully.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Stock Return failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // MOVEMENTS
    // ══════════════════════════════════════════════════════════

    /**
     * Movements list page.
     */
    public function movementsIndex()
    {
        Gate::authorize('viewMovements', InventoryItem::class);

        $items = InventoryItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name']);
        $movementTypes = StockMovement::getMovementTypes();

        return view('supervisor.inventory.movements', compact('items', 'movementTypes'));
    }

    /**
     * Movements DataTable AJAX.
     */
    public function movementsDatatable(Request $request)
    {
        Gate::authorize('viewMovements', InventoryItem::class);

        $result = $this->service->getMovementsDatatable($request->all());
        return response()->json($result);
    }
}
