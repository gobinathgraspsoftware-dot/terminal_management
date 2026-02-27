<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Services\StockReportService;
use App\Models\InventorySerial;
use App\Models\StockLedger;
use App\Exports\StockCardExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StockReportController extends Controller
{
    protected StockReportService $stockReportService;

    public function __construct(StockReportService $stockReportService)
    {
        $this->stockReportService = $stockReportService;
    }

    /**
     * My Inventory — serials currently assigned to this technician.
     */
    public function myInventory(Request $request)
    {
        $user = auth()->user();

        $serials = InventorySerial::with('model.category')
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $user->id)
            ->orderBy('serial_no')
            ->paginate(25);

        // Summary counts
        $summary = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $user->id)
            ->select(
                DB::raw('count(*) as total'),
                DB::raw('sum(case when current_status = "issued_to_tech" then 1 else 0 end) as issued'),
                DB::raw('sum(case when current_status = "installed" then 1 else 0 end) as installed'),
                DB::raw('sum(case when current_status = "under_service" then 1 else 0 end) as under_service')
            )
            ->first();

        // Recent movements for this technician
        $recentMovements = StockLedger::with(['serial.model', 'createdBy'])
            ->forTechnician($user->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return view('technician.stock-reports.my-inventory', compact(
            'serials', 'summary', 'recentMovements'
        ));
    }

    /**
     * Stock Card — limited to serials assigned to this technician.
     */
    public function stockCard(Request $request, $serialId = null)
    {
        $user = auth()->user();
        $stockCardData = null;

        if ($serialId) {
            // Verify the technician has/had this serial
            $serial = InventorySerial::findOrFail($serialId);
            $stockCardData = $this->stockReportService->getStockCard($serialId);
        }

        // Only show serials currently assigned to this technician
        $serials = InventorySerial::with('model')
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $user->id)
            ->orderBy('serial_no')
            ->get();

        return view('technician.stock-reports.stock-card', compact('stockCardData', 'serials'));
    }

    /**
     * Export Stock Card
     */
    public function exportStockCard(Request $request, int $serialId)
    {
        $filename = 'stock-card-' . $serialId . '-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new StockCardExport($serialId), $filename);
    }

    /**
     * Print Stock Card
     */
    public function printStockCard(Request $request, int $serialId)
    {
        $stockCardData = $this->stockReportService->getStockCard($serialId);

        return view('technician.stock-reports.print-stock-card', compact('stockCardData'));
    }

    /**
     * Search serials (AJAX) — limited to technician's own serials.
     */
    public function searchSerials(Request $request)
    {
        $user = auth()->user();
        $term = $request->input('term', '');

        $serials = InventorySerial::with('model')
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $user->id)
            ->where('serial_no', 'like', '%' . $term . '%')
            ->orderBy('serial_no')
            ->limit(20)
            ->get()
            ->map(function ($serial) {
                return [
                    'id' => $serial->id,
                    'text' => $serial->serial_no . ' - ' . ($serial->model ? $serial->model->model_name : 'Unknown Model'),
                ];
            });

        return response()->json($serials);
    }
}
