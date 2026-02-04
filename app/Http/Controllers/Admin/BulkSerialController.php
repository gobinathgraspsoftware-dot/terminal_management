<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkSerialImportRequest;
use App\Http\Requests\Admin\BulkSerialUpdateRequest;
use App\Services\BulkSerialService;
use App\Services\SerialLabelService;
use App\Exports\BulkSerialsTemplateExport;
use App\Exports\SerialLabelsExport;
use App\Imports\BulkSerialsImport;
use App\Models\InventorySerial;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class BulkSerialController extends Controller
{
    protected BulkSerialService $bulkSerialService;
    protected SerialLabelService $labelService;

    public function __construct(
        BulkSerialService $bulkSerialService,
        SerialLabelService $labelService
    ) {
        $this->bulkSerialService = $bulkSerialService;
        $this->labelService = $labelService;
    }

    /**
     * Display bulk operations page
     */
    public function index(): View
    {
        $statistics = [
            'total_serials' => InventorySerial::count(),
            'in_stock' => InventorySerial::where('current_status', InventorySerial::STATUS_IN_STOCK)->count(),
            'issued' => InventorySerial::where('current_status', InventorySerial::STATUS_ISSUED_TO_TECH)->count(),
            'installed' => InventorySerial::where('current_status', InventorySerial::STATUS_INSTALLED)->count(),
        ];

        return view('admin.bulk-serials.index', compact('statistics'));
    }

    /**
     * Show import wizard
     */
    public function importForm(): View
    {
        return view('admin.bulk-serials.import');
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $filename = 'bulk_serials_import_template_' . date('Ymd') . '.xlsx';
        
        activity()
            ->causedBy(Auth::user())
            ->log('Downloaded bulk serials import template');

        return Excel::download(new BulkSerialsTemplateExport(), $filename);
    }

    /**
     * Import serials from Excel
     */
    public function import(BulkSerialImportRequest $request): JsonResponse
    {
        try {
            $import = new BulkSerialsImport();
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->withProperties($results)
                ->log('Imported bulk serials from Excel');

            return response()->json([
                'success' => true,
                'message' => "Import completed! {$results['success']} created, {$results['updated']} updated, {$results['failed']} failed.",
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show bulk update form
     */
    public function bulkUpdateForm(Request $request): View
    {
        $serialIds = $request->input('serial_ids', []);
        
        $serials = InventorySerial::with('terminalModel')
            ->whereIn('id', $serialIds)
            ->get();

        $depots = Depot::where('status', 'active')->orderBy('depot_name')->get();
        $technicians = User::role('technician')->orderBy('name')->get();
        
        $statuses = InventorySerial::STATUS_OPTIONS;
        $locationTypes = [
            InventorySerial::LOCATION_TYPE_DEPOT => 'Depot',
            InventorySerial::LOCATION_TYPE_TECHNICIAN => 'Technician',
        ];

        return view('admin.bulk-serials.bulk-update', compact(
            'serials',
            'depots',
            'technicians',
            'statuses',
            'locationTypes'
        ));
    }

    /**
     * Process bulk update
     */
    public function bulkUpdate(BulkSerialUpdateRequest $request): JsonResponse
    {
        try {
            $operation = $request->input('operation');
            $serialIds = $request->input('serial_ids');
            $remarks = $request->input('remarks');

            $result = match ($operation) {
                'update_status' => $this->bulkSerialService->bulkUpdateStatus(
                    $serialIds,
                    $request->input('new_status'),
                    $remarks
                ),
                'transfer' => $this->bulkSerialService->bulkTransfer(
                    $serialIds,
                    $request->input('to_location_type'),
                    $request->input('to_location_id'),
                    $remarks
                ),
                default => ['success' => false, 'message' => 'Invalid operation']
            };

            if ($result['success']) {
                activity()
                    ->causedBy(Auth::user())
                    ->withProperties($result)
                    ->log("Bulk {$operation} completed");
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show bulk transfer form
     */
    public function transferForm(Request $request): View
    {
        $serialIds = $request->input('serial_ids', []);
        
        $serials = InventorySerial::with('terminalModel')
            ->whereIn('id', $serialIds)
            ->get();

        $depots = Depot::where('status', 'active')->orderBy('depot_name')->get();
        $technicians = User::role('technician')->orderBy('name')->get();

        return view('admin.bulk-serials.transfer', compact('serials', 'depots', 'technicians'));
    }

    /**
     * Validate serials
     */
    public function validateSerials(Request $request): JsonResponse
    {
        try {
            $serialNumbers = $request->input('serial_numbers', []);
            $operation = $request->input('operation', 'update');

            $results = $this->bulkSerialService->validateSerials($serialNumbers, $operation);

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get preview of changes
     */
    public function preview(Request $request): JsonResponse
    {
        try {
            $serialIds = $request->input('serial_ids', []);
            $changes = $request->input('changes', []);

            $preview = $this->bulkSerialService->getPreview($serialIds, $changes);

            return response()->json([
                'success' => true,
                'preview' => $preview
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preview generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate and download labels
     */
    public function generateLabels(Request $request)
    {
        try {
            $serialIds = $request->input('serial_ids', []);
            
            if (empty($serialIds)) {
                return back()->with('error', 'Please select serials to print labels');
            }

            $options = [
                'label_size' => $request->input('label_size', 'standard'),
                'labels_per_row' => $request->input('labels_per_row', 3),
                'paper_size' => $request->input('paper_size', 'A4'),
                'orientation' => $request->input('orientation', 'portrait'),
            ];

            activity()
                ->causedBy(Auth::user())
                ->withProperties(['count' => count($serialIds)])
                ->log('Generated serial labels');

            return $this->labelService->downloadLabels($serialIds, $options);

        } catch (\Exception $e) {
            return back()->with('error', 'Label generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Export selected serials to Excel
     */
    public function exportSerials(Request $request)
    {
        try {
            $serialIds = $request->input('serial_ids', []);
            
            if (empty($serialIds)) {
                return back()->with('error', 'Please select serials to export');
            }

            $filename = 'serials_export_' . date('Ymd_His') . '.xlsx';

            activity()
                ->causedBy(Auth::user())
                ->withProperties(['count' => count($serialIds)])
                ->log('Exported serials to Excel');

            return Excel::download(new SerialLabelsExport($serialIds), $filename);

        } catch (\Exception $e) {
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Get locations for AJAX dropdown
     */
    public function getLocations(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $search = $request->input('search', '');

        $locations = [];

        if ($type === 'depot') {
            $locations = Depot::where('status', 'active')
                ->where('depot_name', 'like', "%{$search}%")
                ->orderBy('depot_name')
                ->limit(50)
                ->get()
                ->map(fn($depot) => [
                    'id' => $depot->id,
                    'text' => "[{$depot->depot_code}] {$depot->depot_name}"
                ]);
        } elseif ($type === 'technician') {
            $locations = User::role('technician')
                ->where('name', 'like', "%{$search}%")
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->map(fn($user) => [
                    'id' => $user->id,
                    'text' => "[{$user->employee_id}] {$user->name}"
                ]);
        }

        return response()->json([
            'success' => true,
            'locations' => $locations
        ]);
    }
}
