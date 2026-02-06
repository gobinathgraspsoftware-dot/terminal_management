<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\StockIssue;
use App\Models\Depot;
use App\Models\User;
use App\Models\TerminalModel;
use App\Http\Requests\StoreStockIssueRequest;
use App\Http\Requests\UpdateStockIssueRequest;
use App\Services\StockIssueService;
use App\Exports\StockIssuesExport;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class StockIssueController extends Controller
{
    use AuthorizesRequests;

    protected $stockIssueService;

    public function __construct(StockIssueService $stockIssueService)
    {
        $this->stockIssueService = $stockIssueService;
    }

    /**
     * Display a listing of stock issues (team scope)
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', StockIssue::class);

        if ($request->ajax()) {
            $query = $this->stockIssueService->getFilteredStockIssues(
                $request,
                'supervisor',
                auth()->id()
            );

            return datatables()->eloquent($query)
                ->addColumn('issue_type_badge', function ($issue) {
                    $type = $issue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH ? 'Issue to Tech' : 'Return from Tech';
                    $class = $issue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH ? 'primary' : 'success';
                    return "<span class='badge bg-{$class}'>{$type}</span>";
                })
                ->addColumn('from_location', function ($issue) {
                    if ($issue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH) {
                        return $issue->fromDepot ? '<i class="bi bi-building"></i> ' . $issue->fromDepot->depot_name : '-';
                    } else {
                        return $issue->fromTechnician ? '<i class="bi bi-person"></i> ' . $issue->fromTechnician->name : '-';
                    }
                })
                ->addColumn('to_location', function ($issue) {
                    if ($issue->issue_type === StockIssue::TYPE_ISSUE_TO_TECH) {
                        return $issue->toTechnician ? '<i class="bi bi-person"></i> ' . $issue->toTechnician->name : '-';
                    } else {
                        return $issue->toDepot ? '<i class="bi bi-building"></i> ' . $issue->toDepot->depot_name : '-';
                    }
                })
                ->addColumn('status_badge', function ($issue) {
                    $badges = [
                        'draft' => '<span class="badge bg-secondary">Draft</span>',
                        'posted' => '<span class="badge bg-success">Posted</span>',
                        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
                    ];
                    return $badges[$issue->status] ?? $issue->status;
                })
                ->addColumn('action', function ($issue) {
                    $actions = '<div class="btn-group" role="group">';

                    // View
                    $actions .= '<a href="' . route('supervisor.stock-issues.show', $issue->id) . '" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>';

                    // Edit (only for draft)
                    if ($issue->status === StockIssue::STATUS_DRAFT && auth()->user()->can('update', $issue)) {
                        $actions .= '<a href="' . route('supervisor.stock-issues.edit', $issue->id) . '" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>';
                    }

                    // Post (only for draft)
                    if ($issue->status === StockIssue::STATUS_DRAFT && auth()->user()->can('post', $issue)) {
                        $actions .= '<button type="button" class="btn btn-sm btn-success" onclick="postIssue(' . $issue->id . ')" title="Post">
                                        <i class="bi bi-check-circle"></i>
                                    </button>';
                    }

                    // Print
                    $actions .= '<a href="' . route('supervisor.stock-issues.print', $issue->id) . '" target="_blank" class="btn btn-sm btn-secondary" title="Print">
                                    <i class="bi bi-printer"></i>
                                </a>';

                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['issue_type_badge', 'from_location', 'to_location', 'status_badge', 'action'])
                ->make(true);
        }

        // Get team technicians only
        $teamTechnicians = auth()->user()->teamMembers;
        $depots = Depot::orderBy('depot_name')->get();

        return view('supervisor.stock-issues.index', compact('depots', 'teamTechnicians'));
    }

    /**
     * Show the form for creating a new stock issue
     */
    public function create()
    {
        $this->authorize('create', StockIssue::class);

        $depots = Depot::orderBy('depot_name')->get();
        $teamTechnicians = auth()->user()->teamMembers;
        $models = TerminalModel::where('is_active', true)->orderBy('model_name')->get();

        return view('supervisor.stock-issues.create', compact('depots', 'teamTechnicians', 'models'));
    }

    /**
     * Store a newly created stock issue
     */
    public function store(StoreStockIssueRequest $request)
    {
        try {
            $stockIssue = $this->stockIssueService->createStockIssue($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Stock issue created successfully',
                'redirect' => route('supervisor.stock-issues.show', $stockIssue->id)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating stock issue: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified stock issue
     */
    public function show(StockIssue $stockIssue)
    {
        $this->authorize('view', $stockIssue);

        $stockIssue->load([
            'fromDepot',
            'toDepot',
            'toTechnician',
            'fromTechnician',
            'lines.model',
            'lines.serial'
        ]);

        return view('supervisor.stock-issues.show', compact('stockIssue'));
    }

    /**
     * Show the form for editing the specified stock issue
     */
    public function edit(StockIssue $stockIssue)
    {
        $this->authorize('update', $stockIssue);

        if ($stockIssue->status !== StockIssue::STATUS_DRAFT) {
            return redirect()->route('supervisor.stock-issues.show', $stockIssue->id)
                ->with('error', 'Only draft stock issues can be edited');
        }

        $stockIssue->load('lines.model', 'lines.serial');
        $depots = Depot::orderBy('depot_name')->get();
        $teamTechnicians = auth()->user()->teamMembers;
        $models = TerminalModel::where('is_active', true)->orderBy('model_name')->get();

        return view('supervisor.stock-issues.edit', compact('stockIssue', 'depots', 'teamTechnicians', 'models'));
    }

    /**
     * Update the specified stock issue
     */
    public function update(UpdateStockIssueRequest $request, StockIssue $stockIssue)
    {
        try {
            $stockIssue = $this->stockIssueService->updateStockIssue($stockIssue, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Stock issue updated successfully',
                'redirect' => route('supervisor.stock-issues.show', $stockIssue->id)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating stock issue: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Post stock issue
     */
    public function post(StockIssue $stockIssue)
    {
        $this->authorize('post', $stockIssue);

        try {
            $stockIssue = $this->stockIssueService->postStockIssue($stockIssue);

            return response()->json([
                'success' => true,
                'message' => 'Stock issue posted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error posting stock issue: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Print stock issue
     */
    public function print(StockIssue $stockIssue)
    {
        $this->authorize('view', $stockIssue);

        $stockIssue->load([
            'fromDepot',
            'toDepot',
            'toTechnician',
            'fromTechnician',
            'lines.model',
            'lines.serial'
        ]);

        return view('supervisor.stock-issues.print', compact('stockIssue'));
    }

    /**
     * Get available serials for depot
     */
    public function getAvailableSerials(Request $request)
    {
        $depotId = $request->depot_id;
        $modelId = $request->model_id;

        $serials = $this->stockIssueService->getAvailableSerials($depotId, $modelId);

        return response()->json([
            'success' => true,
            'serials' => $serials
        ]);
    }

    /**
     * Get technician serials
     */
    public function getTechnicianSerials(Request $request)
    {
        $technicianId = $request->technician_id;
        $modelId = $request->model_id;

        $serials = $this->stockIssueService->getTechnicianSerials($technicianId, $modelId);

        return response()->json([
            'success' => true,
            'serials' => $serials
        ]);
    }

    /**
     * Export stock issues
     */
    public function export(Request $request)
    {
        $query = $this->stockIssueService->getFilteredStockIssues(
            $request,
            'supervisor',
            auth()->id()
        );

        return Excel::download(
            new StockIssuesExport($query),
            'stock-issues-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
