<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRateCardRequest;
use App\Http\Requests\UpdateRateCardRequest;
use App\Models\RateCard;
use App\Models\TerminalModel;
use App\Services\RateCardService;
use App\Exports\RateCardsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RateCardController extends Controller
{
    use AuthorizesRequests; // Add this trait

    protected $rateCardService;

    public function __construct(RateCardService $rateCardService)
    {
        $this->rateCardService = $rateCardService;
    }

    /**
     * Display a listing of rate cards
     */
    public function index(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('view_rate_cards')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->ajax()) {
            return $this->rateCardService->getDatatableData($request);
        }

        $jobTypes = RateCard::getJobTypeOptions();
        $calculationTypes = RateCard::getCalculationTypeOptions();
        $statuses = RateCard::getStatusOptions();
        $states = RateCard::getMalaysianStates();
        $models = TerminalModel::active()->orderBy('model_name')->get();

        return view('admin.rate-cards.index', compact(
            'jobTypes',
            'calculationTypes',
            'statuses',
            'states',
            'models'
        ));
    }

    /**
     * Show the form for creating a new rate card
     */
    public function create()
    {
        // Check permission
        if (!auth()->user()->can('create_rate_cards')) {
            abort(403, 'Unauthorized action.');
        }

        $jobTypes = RateCard::getJobTypeOptions();
        $calculationTypes = RateCard::getCalculationTypeOptions();
        $states = RateCard::getMalaysianStates();
        $models = TerminalModel::active()->orderBy('model_name')->get();

        return view('admin.rate-cards.create', compact(
            'jobTypes',
            'calculationTypes',
            'states',
            'models'
        ));
    }

    /**
     * Store a newly created rate card
     */
    public function store(StoreRateCardRequest $request)
    {
        // Check permission
        if (!auth()->user()->can('create_rate_cards')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $rateCard = $this->rateCardService->create($request->validated());

            DB::commit();

            return redirect()
                ->route('admin.rate-cards.index')
                ->with('success', 'Rate card created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to create rate card: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified rate card
     */
    public function show(RateCard $rateCard)
    {
        // Check permission
        if (!auth()->user()->can('view_rate_cards')) {
            abort(403, 'Unauthorized action.');
        }

        $rateCard->load('model');

        return view('admin.rate-cards.show', compact('rateCard'));
    }

    /**
     * Show the form for editing the specified rate card
     */
    public function edit(RateCard $rateCard)
    {
        // Check permission
        if (!auth()->user()->can('edit_rate_cards')) {
            abort(403, 'Unauthorized action.');
        }

        $jobTypes = RateCard::getJobTypeOptions();
        $calculationTypes = RateCard::getCalculationTypeOptions();
        $states = RateCard::getMalaysianStates();
        $models = TerminalModel::active()->orderBy('model_name')->get();

        return view('admin.rate-cards.edit', compact(
            'rateCard',
            'jobTypes',
            'calculationTypes',
            'states',
            'models'
        ));
    }

    /**
     * Update the specified rate card
     */
    public function update(UpdateRateCardRequest $request, RateCard $rateCard)
    {
        // Check permission
        if (!auth()->user()->can('edit_rate_cards')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $this->rateCardService->update($rateCard, $request->validated());

            DB::commit();

            return redirect()
                ->route('admin.rate-cards.index')
                ->with('success', 'Rate card updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to update rate card: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified rate card
     */
    public function destroy(RateCard $rateCard)
    {
        // Check permission
        if (!auth()->user()->can('delete_rate_cards')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $this->rateCardService->delete($rateCard);

            return response()->json([
                'success' => true,
                'message' => 'Rate card deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete rate card: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate commission preview
     */
    public function calculatePreview(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('view_rate_cards')) {
            abort(403, 'Unauthorized action.');
        }

        $calculationType = $request->input('calculation_type');
        $rateAmount = $request->input('rate_amount', 0);
        $minAmount = $request->input('min_amount');
        $maxAmount = $request->input('max_amount');
        $jobValue = $request->input('job_value', 1000);
        $terminalCount = $request->input('terminal_count', 1);

        $amount = 0;

        switch ($calculationType) {
            case RateCard::CALC_TYPE_FLAT:
                $amount = $rateAmount;
                $calculation = "Flat Rate: RM " . number_format($rateAmount, 2);
                break;

            case RateCard::CALC_TYPE_PER_TERMINAL:
                $amount = $rateAmount * $terminalCount;
                $calculation = "RM " . number_format($rateAmount, 2) . " × " . $terminalCount . " terminal(s)";
                break;

            case RateCard::CALC_TYPE_PERCENTAGE:
                $amount = ($jobValue * $rateAmount) / 100;
                $calculation = number_format($rateAmount, 2) . "% of RM " . number_format($jobValue, 2);
                break;

            default:
                $calculation = "Invalid calculation type";
        }

        // Apply limits
        $beforeLimits = $amount;
        if ($minAmount && $amount < $minAmount) {
            $amount = $minAmount;
        }
        if ($maxAmount && $amount > $maxAmount) {
            $amount = $maxAmount;
        }

        return response()->json([
            'success' => true,
            'calculation' => $calculation,
            'amount' => $amount,
            'formatted_amount' => 'RM ' . number_format($amount, 2),
            'before_limits' => $beforeLimits,
            'limits_applied' => $beforeLimits != $amount,
            'min_amount' => $minAmount,
            'max_amount' => $maxAmount,
        ]);
    }

    /**
     * Find applicable rate card
     */
    public function findApplicableRate(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('view_rate_cards')) {
            abort(403, 'Unauthorized action.');
        }

        $jobType = $request->input('job_type');
        $modelId = $request->input('model_id');
        $state = $request->input('state');
        $date = $request->input('date', now()->format('Y-m-d'));

        $rateCard = $this->rateCardService->findApplicableRate(
            $jobType,
            $modelId,
            $state,
            $date
        );

        if (!$rateCard) {
            return response()->json([
                'success' => false,
                'message' => 'No applicable rate card found for the given criteria.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'rate_card' => [
                'id' => $rateCard->id,
                'code' => $rateCard->rate_card_code,
                'name' => $rateCard->rate_card_name,
                'calculation_type' => $rateCard->calculation_type,
                'rate_amount' => $rateCard->rate_amount,
                'min_amount' => $rateCard->min_amount,
                'max_amount' => $rateCard->max_amount,
            ]
        ]);
    }

    /**
     * Export rate cards
     */
    public function export(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('view_rate_cards')) {
            abort(403, 'Unauthorized action.');
        }

        $filters = [
            'job_type' => $request->input('job_type'),
            'model_id' => $request->input('model_id'),
            'state' => $request->input('state'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        return Excel::download(
            new RateCardsExport($filters),
            'rate-cards-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Toggle rate card status
     */
    public function toggleStatus(RateCard $rateCard)
    {
        // Check permission
        if (!auth()->user()->can('edit_rate_cards')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $newStatus = $rateCard->status === RateCard::STATUS_ACTIVE
                ? RateCard::STATUS_INACTIVE
                : RateCard::STATUS_ACTIVE;

            $rateCard->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => 'Rate card status updated successfully.',
                'status' => $newStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }
}
