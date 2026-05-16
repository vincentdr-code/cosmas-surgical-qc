<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    /**
     * GET /api/v1/stats
     *
     * Public aggregate metrics. No PII — safe for external consumers.
     * Demonstrates scalability: any ERP, MES, or BI tool can pull live QC data.
     */

    /**
     * GET /api/v1/health
     * System health check — for monitoring and uptime verification.
     */
    public function health(): JsonResponse
    {
        $dbOk = true;
        try { \DB::connection()->getPdo(); } catch (\Exception $e) { $dbOk = false; }

        return response()->json([
            'status'     => $dbOk ? 'ok' : 'degraded',
            'version'    => '1.0',
            'timestamp'  => now()->toIso8601String(),
            'services'   => [
                'database'    => $dbOk ? 'up' : 'down',
                'yolo_model'  => 'yolov8s_defect_detector (mAP50=0.764)',
                'claude_model' => 'claude-sonnet-4-6',
            ],
        ], $dbOk ? 200 : 503);
    }

    public function stats(): JsonResponse
    {
        $total   = Inspection::count();
        $passed  = Inspection::whereRaw("UPPER(pass_fail) = 'PASS'")->count();
        $failed  = Inspection::whereRaw("UPPER(pass_fail) = 'FAIL'")->count();
        $flagged = Inspection::whereRaw("UPPER(pass_fail) = 'FLAGGED'")->count();

        $passRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

        $avgConf = Inspection::whereNotNull('confidence')
            ->where('confidence', '>', 0)
            ->avg('confidence');
        $avgConf = $avgConf ? round($avgConf * ($avgConf <= 1 ? 100 : 1), 1) : null;

        $costSaved = round($total * 0.14, 2);

        return response()->json([
            'meta' => [
                'version'     => '1.0',
                'generated_at' => now()->toIso8601String(),
                'description'  => 'Cosmas Sentry — AI Defect Detection QC Metrics',
            ],
            'data' => [
                'total_inspections'    => $total,
                'passed'              => $passed,
                'failed'              => $failed,
                'flagged'             => $flagged,
                'pass_rate_pct'       => $passRate,
                'avg_confidence_pct'  => $avgConf,
                'cost_saved_usd'      => $costSaved,
                'cost_per_inspection' => 0.01,
            ],
        ]);
    }

    /**
     * GET /api/v1/inspections
     *
     * Paginated inspection list (no images, no PII).
     * Useful for downstream reporting or MES integration.
     */
    public function inspections(): JsonResponse
    {
        $rows = Inspection::select([
                'id', 'pass_fail', 'defect_type', 'confidence',
                'instrument_class', 'yolo_confidence', 'created_at'
            ])
            ->latest()
            ->paginate(50);

        return response()->json($rows);
    }
}
