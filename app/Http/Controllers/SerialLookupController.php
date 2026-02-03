<?php

namespace App\Http\Controllers;

use App\Services\InventorySerialService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SerialLookupController extends Controller
{
    use AuthorizesRequests;

    protected InventorySerialService $service;

    public function __construct(InventorySerialService $service)
    {
        $this->service = $service;
    }

    /**
     * Serial autocomplete for search inputs.
     * GET /api/serials/autocomplete?term=ABC
     */
    public function autocomplete(Request $request)
    {
        $this->authorize('view_inventory');

        $request->validate(['term' => 'required|string|min:2|max:100']);

        $results = $this->service->autocomplete($request->term, $request->input('limit', 10));

        return response()->json([
            'success' => true,
            'data'    => $results,
        ]);
    }

    /**
     * Validate a single serial number.
     * GET /api/serials/validate?serial_no=ABC123
     */
    public function validateSerial(Request $request)
    {
        $this->authorize('view_inventory');

        $request->validate(['serial_no' => 'required|string|max:100']);

        $result = $this->service->validateSerial($request->serial_no);

        return response()->json($result);
    }

    /**
     * Batch serial lookup.
     * POST /api/serials/batch-lookup
     */
    public function batchLookup(Request $request)
    {
        $this->authorize('view_inventory');

        $request->validate([
            'serial_numbers'   => 'required|array|min:1|max:100',
            'serial_numbers.*' => 'required|string|max:100',
        ]);

        $results = $this->service->batchLookup($request->serial_numbers);

        $found = collect($results)->where('found', true)->count();
        $total = count($results);

        return response()->json([
            'success' => true,
            'data'    => $results,
            'summary' => [
                'total'     => $total,
                'found'     => $found,
                'not_found' => $total - $found,
            ],
        ]);
    }

    /**
     * Quick search (returns full serial details for the lookup modal).
     * GET /api/serials/search?q=ABC
     */
    public function search(Request $request)
    {
        $this->authorize('view_inventory');

        $request->validate(['q' => 'required|string|min:1|max:100']);

        $serials = \App\Models\InventorySerial::search($request->q)
            ->with(['terminalModel.category', 'grn'])
            ->limit(20)
            ->get()
            ->map(function ($serial) {
                return [
                    'id'             => $serial->id,
                    'serial_no'      => $serial->serial_no,
                    'model_name'     => $serial->terminalModel?->model_name ?? '-',
                    'category'       => $serial->terminalModel?->category?->category_name ?? '-',
                    'status'         => $serial->current_status,
                    'status_label'   => \App\Models\InventorySerial::STATUS_OPTIONS[$serial->current_status] ?? $serial->current_status,
                    'status_badge'   => $serial->status_badge,
                    'location_type'  => $serial->current_location_type,
                    'location_name'  => $serial->location_name,
                    'warranty'       => $serial->warranty_status,
                    'grn_no'         => $serial->grn?->grn_no ?? '-',
                    'grn_date'       => $serial->grn_date?->format('d/m/Y') ?? '-',
                    'available'      => $serial->is_available,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $serials,
            'count'   => $serials->count(),
        ]);
    }

    /**
     * Get complete serial history via AJAX (for modal detail view).
     * GET /api/serials/{id}/history
     */
    public function history(int $id)
    {
        $this->authorize('view_inventory');

        $detail = $this->service->getSerialDetail($id);
        $serial = $detail['serial'];

        return response()->json([
            'success' => true,
            'serial'  => [
                'id'             => $serial->id,
                'serial_no'      => $serial->serial_no,
                'model_name'     => $serial->terminalModel?->model_name ?? '-',
                'status'         => $serial->current_status,
                'status_badge'   => $serial->status_badge,
                'location'       => $serial->location_name,
                'location_type'  => $serial->current_location_type,
                'warranty'       => $serial->warranty_status,
            ],
            'movement_history'     => $detail['movement_history']->map(function ($m) {
                return [
                    'date'             => $m->transaction_date?->format('d/m/Y'),
                    'transaction_no'   => $m->transaction_no,
                    'type'             => ucfirst(str_replace('_', ' ', $m->transaction_type)),
                    'from'             => ($m->from_location_type ? ucfirst($m->from_location_type) . ' #' . $m->from_location_id : '-'),
                    'to'               => ($m->to_location_type ? ucfirst($m->to_location_type) . ' #' . $m->to_location_id : '-'),
                    'quantity'         => $m->quantity,
                    'remarks'          => $m->remarks,
                    'created_by'       => $m->createdBy?->name ?? '-',
                ];
            }),
            'installation_history' => $detail['installation_history']->map(function ($i) {
                return [
                    'site_name'      => $i->site?->site_name ?? '-',
                    'installed_date' => $i->installed_date?->format('d/m/Y'),
                    'installed_by'   => $i->installedBy?->name ?? '-',
                    'status'         => $i->status,
                    'removed_date'   => $i->removed_date?->format('d/m/Y'),
                ];
            }),
            'service_history' => $detail['service_history']->map(function ($s) {
                return [
                    'date'         => $s->event_date?->format('d/m/Y'),
                    'type'         => ucfirst(str_replace('_', ' ', $s->event_type)),
                    'description'  => $s->description,
                    'performed_by' => $s->performedBy?->name ?? '-',
                    'site'         => $s->siteAsset?->site?->site_name ?? '-',
                ];
            }),
        ]);
    }
}
