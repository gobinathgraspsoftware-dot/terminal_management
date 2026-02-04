<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Services\SerialLabelService;
use App\Exports\SerialLabelsExport;
use App\Models\InventorySerial;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class BulkSerialController extends Controller
{
    protected SerialLabelService $labelService;

    public function __construct(SerialLabelService $labelService)
    {
        $this->labelService = $labelService;
    }

    /**
     * Display bulk operations page
     * Technicians can only view their own serials
     */
    public function index(): View
    {
        $user = Auth::user();

        // Statistics scoped to technician's own stock
        $statistics = [
            'total_serials' => InventorySerial::where('current_location_type', InventorySerial::LOCATION_TYPE_TECHNICIAN)
                ->where('current_location_id', $user->id)
                ->count(),
            'issued' => InventorySerial::where('current_status', InventorySerial::STATUS_ISSUED_TO_TECH)
                ->where('current_location_type', InventorySerial::LOCATION_TYPE_TECHNICIAN)
                ->where('current_location_id', $user->id)
                ->count(),
            'reserved' => InventorySerial::where('current_status', InventorySerial::STATUS_RESERVED)
                ->where('current_location_type', InventorySerial::LOCATION_TYPE_TECHNICIAN)
                ->where('current_location_id', $user->id)
                ->count(),
        ];

        return view('technician.bulk-serials.index', compact('statistics'));
    }

    /**
     * Generate and download labels for technician's own serials
     */
    public function generateLabels(Request $request)
    {
        try {
            $user = Auth::user();
            $serialIds = $request->input('serial_ids', []);
            
            if (empty($serialIds)) {
                return back()->with('error', 'Please select serials to print labels');
            }

            // Verify technician can only print their own serials
            $validSerialIds = InventorySerial::whereIn('id', $serialIds)
                ->where('current_location_type', InventorySerial::LOCATION_TYPE_TECHNICIAN)
                ->where('current_location_id', $user->id)
                ->pluck('id')
                ->toArray();

            if (empty($validSerialIds)) {
                return back()->with('error', 'You can only print labels for your own serials');
            }

            $options = [
                'label_size' => $request->input('label_size', 'standard'),
                'labels_per_row' => $request->input('labels_per_row', 3),
                'paper_size' => $request->input('paper_size', 'A4'),
                'orientation' => $request->input('orientation', 'portrait'),
            ];

            activity()
                ->causedBy(Auth::user())
                ->withProperties(['count' => count($validSerialIds)])
                ->log('Generated serial labels (Technician)');

            return $this->labelService->downloadLabels($validSerialIds, $options);

        } catch (\Exception $e) {
            return back()->with('error', 'Label generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Export technician's own serials to Excel
     */
    public function exportSerials(Request $request)
    {
        try {
            $user = Auth::user();
            $serialIds = $request->input('serial_ids', []);
            
            if (empty($serialIds)) {
                return back()->with('error', 'Please select serials to export');
            }

            // Verify technician can only export their own serials
            $validSerialIds = InventorySerial::whereIn('id', $serialIds)
                ->where('current_location_type', InventorySerial::LOCATION_TYPE_TECHNICIAN)
                ->where('current_location_id', $user->id)
                ->pluck('id')
                ->toArray();

            if (empty($validSerialIds)) {
                return back()->with('error', 'You can only export your own serials');
            }

            $filename = 'my_serials_' . date('Ymd_His') . '.xlsx';

            activity()
                ->causedBy(Auth::user())
                ->withProperties(['count' => count($validSerialIds)])
                ->log('Exported own serials to Excel (Technician)');

            return Excel::download(new SerialLabelsExport($validSerialIds), $filename);

        } catch (\Exception $e) {
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
}
