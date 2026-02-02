<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\RateCard;
use App\Models\TerminalModel;
use App\Services\RateCardService;
use Illuminate\Http\Request;
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
     * Display a listing of rate cards (view-only)
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

        return view('supervisor.rate-cards.index', compact(
            'jobTypes',
            'calculationTypes',
            'statuses',
            'states',
            'models'
        ));
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

        return view('supervisor.rate-cards.show', compact('rateCard'));
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
}
