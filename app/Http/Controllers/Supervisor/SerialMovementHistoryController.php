<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Services\SerialHistoryService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SerialMovementHistoryController extends Controller
{
    use AuthorizesRequests;

    protected SerialHistoryService $service;

    public function __construct(SerialHistoryService $service)
    {
        $this->service = $service;
    }

    /**
     * Display team serial movements with DataTable and filters.
     */
    public function index(Request $request)
    {
        $this->authorize('view_inventory');

        if ($request->ajax()) {
            return $this->service->getDataTable($request, 'supervisor', auth()->id());
        }

        $stats         = $this->service->getStatistics();
        $filterOptions = $this->service->getFilterOptions();

        return view('supervisor.serial-movement-history.index', compact('stats', 'filterOptions'));
    }

    /**
     * DataTable AJAX endpoint.
     */
    public function datatable(Request $request)
    {
        $this->authorize('view_inventory');
        return $this->service->getDataTable($request, 'supervisor', auth()->id());
    }

    /**
     * Show serial movement timeline for a specific serial.
     */
    public function show(int $serialId)
    {
        $this->authorize('view_inventory');

        $data = $this->service->getSerialTimeline($serialId);

        return view('supervisor.serial-movement-history.show', $data);
    }

    /**
     * Get timeline data via AJAX.
     */
    public function timeline(Request $request, int $serialId)
    {
        $this->authorize('view_inventory');

        $data      = $this->service->getSerialTimeline($serialId);
        $serial    = $data['serial'];
        $movements = $data['movements'];

        return response()->json([
            'success' => true,
            'serial'  => [
                'id'             => $serial->id,
                'serial_no'      => $serial->serial_no,
                'model_name'     => $serial->terminalModel?->model_name ?? '-',
                'status'         => $serial->current_status,
                'status_badge'   => $serial->status_badge,
                'location'       => $serial->location_name,
            ],
            'movements' => $movements->map(function ($m) {
                return [
                    'id'              => $m->id,
                    'date'            => $m->transaction_date?->format('d/m/Y'),
                    'time'            => $m->created_at?->format('H:i'),
                    'transaction_no'  => $m->transaction_no,
                    'type'            => $m->transaction_type,
                    'type_label'      => $m->type_label,
                    'type_icon'       => $m->type_icon,
                    'type_color'      => $m->type_color,
                    'from'            => $m->from_location_name,
                    'to'              => $m->to_location_name,
                    'quantity'        => $m->quantity,
                    'reference'       => $m->reference_label,
                    'reference_url'   => $m->reference_url,
                    'remarks'         => $m->remarks,
                    'performed_by'    => $m->createdBy?->name ?? '-',
                    'is_reversed'     => $m->is_reversed,
                    'is_reversal'     => !is_null($m->reversal_of_id),
                    'is_reversible'   => false, // Supervisors cannot reverse
                ];
            }),
            'summary' => $data['summary'],
        ]);
    }
}
