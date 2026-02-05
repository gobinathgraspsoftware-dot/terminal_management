<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Services\StockReportService;
use App\Models\InventorySerial;
use App\Exports\StockCardExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StockReportController extends Controller
{
    protected StockReportService $stockReportService;

    public function __construct(StockReportService $stockReportService)
    {
        $this->stockReportService = $stockReportService;
    }

    /**
     * My inventory - shows technician's current stock
     */
    public function myInventory(Request $request)
    {
        $filters = $request->only(['from_date', 'to_date', 'model_id']);

        // Get technician's movements
        $movements = $this->stockReportService
            ->getTechnicianMovements(auth()->id(), $filters)
            ->paginate(30);

        // Get current inventory
        $currentInventory = InventorySerial::with('model.category')
            ->where('current_location_type', 'technician')
            ->where('current_location_id', auth()->id())
            ->whereIn('current_status', ['in_stock', 'reserved', 'in_service'])
            ->orderBy('serial_no')
            ->get();

        return view('technician.stock-reports.my-inventory', compact(
            'movements',
            'currentInventory',
            'filters'
        ));
    }

    /**
     * Stock card view (only for technician's own serials)
     */
    public function stockCard(Request $request, $serialId = null)
    {
        $stockCardData = null;

        if ($serialId) {
            $serial = InventorySerial::findOrFail($serialId);

            // Verify this serial belongs to technician
            if ($serial->current_location_type !== 'technician' ||
                $serial->current_location_id !== auth()->id()) {
                abort(403, 'Unauthorized access to this serial number.');
            }

            $stockCardData = $this->stockReportService->getStockCard($serialId);
        }

        // Get technician's serials for dropdown
        $serials = InventorySerial::with('model')
            ->where('current_location_type', 'technician')
            ->where('current_location_id', auth()->id())
            ->orderBy('serial_no')
            ->get();

        return view('technician.stock-reports.stock-card', compact('stockCardData', 'serials'));
    }

    /**
     * Export stock card to Excel
     */
    public function exportStockCard(Request $request, int $serialId)
    {
        $serial = InventorySerial::findOrFail($serialId);

        // Verify this serial belongs to technician
        if ($serial->current_location_type !== 'technician' ||
            $serial->current_location_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this serial number.');
        }

        $filename = 'stock-card-' . $serialId . '-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new StockCardExport($serialId), $filename);
    }

    /**
     * Print stock card
     */
    public function printStockCard(Request $request, int $serialId)
    {
        $serial = InventorySerial::findOrFail($serialId);

        // Verify this serial belongs to technician
        if ($serial->current_location_type !== 'technician' ||
            $serial->current_location_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this serial number.');
        }

        $stockCardData = $this->stockReportService->getStockCard($serialId);

        return view('technician.stock-reports.print-stock-card', compact('stockCardData'));
    }

    /**
     * Search serials (AJAX) - only technician's own serials
     */
    public function searchSerials(Request $request)
    {
        $term = $request->input('term', '');

        $serials = InventorySerial::with('model')
            ->where('current_location_type', 'technician')
            ->where('current_location_id', auth()->id())
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
