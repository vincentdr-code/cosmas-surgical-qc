<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Services\InspectionOrchestratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * InspectionController — thin orchestration layer.
 * All analysis logic lives in InspectionOrchestratorService.
 */
class InspectionController extends Controller
{
    public function __construct(
        private InspectionOrchestratorService $orchestrator
    ) {}

    public function showUploadForm()
    {
        return view('inspections.upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        $image     = $request->file('image');
        $imagePath = $image->store('inspections', 'public');
        $fullPath  = storage_path('app/public/' . $imagePath);

        // ── Agent Orchestrator ────────────────────────────────────────
        $threshold = Auth::user()->confidence_threshold ?? 70;
        $result    = $this->orchestrator->runInspection($fullPath, $threshold);

        // ── Persist ───────────────────────────────────────────────────
        $costMatrix = $result['cost_matrix'] ?? null;
        $agentSteps = $result['agent_steps'] ?? [];

        // Extract YOLO data from agent steps for backward compatibility
        $yoloStep = collect($agentSteps)->firstWhere('tool', 'run_yolo_scan');
        $yoloData = $yoloStep['result'] ?? [];

        $inspection = Inspection::create([
            'user_id'              => Auth::id(),
            'image_path'           => $imagePath,
            'defect_type'          => $result['defect_type']          ?? 'Unknown',
            'instrument_class'     => $result['instrument_class']     ?? null,
            'confidence'           => $result['confidence']           ?? 0,
            'pass_fail'            => $result['verdict']              ?? 'FAIL',
            'claude_reasoning'     => $result['reasoning']            ?? '',
            'regulatory_note'      => $result['regulatory_note']      ?? null,
            'recommended_action'   => $result['recommended_action']   ?? null,
            'composite_risk_score' => $result['composite_risk_score'] ?? null,
            'risk_level'           => $result['risk_level']           ?? null,
            'agent_steps'          => json_encode($agentSteps),
            'cost_matrix'          => $costMatrix ? json_encode($costMatrix) : null,
            'yolo_detections'      => json_encode($yoloData['detections'] ?? []),
            'yolo_count'           => $yoloData['count']              ?? 0,
            'yolo_model'           => $yoloData['model_used']         ?? 'unavailable',
            'inference_ms'         => $yoloData['inference_ms']       ?? null,
        ]);

        return redirect()->route('inspections.results', $inspection);
    }

    public function results(Inspection $inspection)
    {
        // Decode JSON fields for the view
        $inspection->agent_steps_decoded = json_decode($inspection->agent_steps ?? '[]', true);
        $inspection->cost_matrix_decoded = json_decode($inspection->cost_matrix ?? 'null', true);
        $inspection->yolo_detections_decoded = json_decode($inspection->yolo_detections ?? '[]', true);

        return view('inspections.results', compact('inspection'));
    }

    public function auditLog(Request $request)
    {
        $query = Inspection::where('user_id', Auth::id())->latest();

        if ($request->filled('status')) {
            $query->whereRaw('UPPER(pass_fail) = ?', [strtoupper($request->status)]);
        }

        $inspections = $query->paginate(20)->withQueryString();
        return view('inspections.audit-log', compact('inspections'));
    }

    public function exportCsv()
    {
        $inspections = Inspection::latest()->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cosmas_audit_log_' . now()->format('Ymd_His') . '.csv"',
            'Cache-Control'       => 'no-store, no-cache',
        ];

        $callback = function () use ($inspections) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID', 'Pass/Fail', 'Defect Type', 'Instrument Class',
                'Confidence (%)', 'Risk Level', 'Composite Risk Score',
                'Regulatory Note', 'Recommended Action',
                'Inspector (User ID)', 'Created At',
            ]);

            foreach ($inspections as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->pass_fail         ?? '',
                    $row->defect_type       ?? '',
                    $row->instrument_class  ?? '',
                    $row->confidence        ? round($row->confidence, 1) : '',
                    $row->risk_level        ?? '',
                    $row->composite_risk_score ?? '',
                    $row->regulatory_note   ?? '',
                    $row->recommended_action ?? '',
                    $row->user_id           ?? '',
                    $row->created_at        ? $row->created_at->toIso8601String() : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Serve the uploaded inspection image directly through PHP.
     * Bypasses the storage symlink (php artisan storage:link) requirement —
     * the file is read from storage_path() and streamed with correct MIME type.
     */
    public function serveImage(Inspection $inspection)
    {
        $path = storage_path('app/public/' . $inspection->image_path);

        if (!$inspection->image_path || !file_exists($path)) {
            abort(404, 'Image not found');
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';

        return response()->file($path, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
