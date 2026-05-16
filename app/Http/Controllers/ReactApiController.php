<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Services\InspectionPromptService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * React SPA adapter — speaks the protocol the React frontend expects.
 * All endpoints are public (no session auth) for demo purposes.
 * Confidence values sent as 0–1 decimals to match React's display logic.
 */
class ReactApiController extends Controller
{
    private string $yoloServiceUrl = 'http://127.0.0.1:8001';
    private string $anthropicApiKey;
    private InspectionPromptService $promptService;

    public function __construct(InspectionPromptService $promptService)
    {
        $this->anthropicApiKey = config('services.anthropic.key');
        $this->promptService   = $promptService;
    }

    private function cors(JsonResponse $response): JsonResponse
    {
        return $response->header('Access-Control-Allow-Origin', '*')
                        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
    }

    // ── GET /api/react/health ────────────────────────────────────────────────
    public function health(): JsonResponse
    {
        $dbOk = true;
        try { DB::connection()->getPdo(); } catch (\Exception $e) { $dbOk = false; }

        $yoloOk = false;
        try {
            $r = Http::timeout(2)->get("{$this->yoloServiceUrl}/health");
            $yoloOk = $r->successful();
        } catch (\Exception $e) {}

        static $startTime = null;
        if (!$startTime) $startTime = now()->timestamp;

        return $this->cors(response()->json([
            'model_loaded'         => $yoloOk,
            'plc_connected'        => true,
            'plc_simulator_mode'   => true,
            'log_file_exists'      => $dbOk,
            'uptime_seconds'       => now()->timestamp - (int) config('app.start_time', now()->timestamp),
            'yolo_ok'              => $yoloOk,
            'db_ok'                => $dbOk,
        ]));
    }

    // ── GET /api/react/stats ─────────────────────────────────────────────────
    public function stats(): JsonResponse
    {
        $total   = Inspection::count();
        $passed  = Inspection::whereRaw("UPPER(pass_fail) = 'PASS'")->count();
        $failed  = Inspection::whereRaw("UPPER(pass_fail) = 'FAIL'")->count();

        // Build defect_counts from defect_type column
        $rawCounts = Inspection::select('defect_type', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('defect_type')
            ->whereRaw("LOWER(defect_type) NOT IN ('no defect detected','none','unknown','')")
            ->groupBy('defect_type')
            ->get();

        $defectCounts = [];
        foreach ($rawCounts as $row) {
            $key = $this->normaliseDefectName($row->defect_type);
            $defectCounts[$key] = ($defectCounts[$key] ?? 0) + $row->cnt;
        }
        arsort($defectCounts);

        return $this->cors(response()->json([
            'pass_count'        => $passed,
            'fail_count'        => $failed,
            'total_inspections' => $total,
            'defect_counts'     => $defectCounts,
        ]));
    }

    // ── GET /api/react/audit-log ─────────────────────────────────────────────
    public function auditLog(Request $request): JsonResponse
    {
        // CSV download mode
        if ($request->boolean('download')) {
            return $this->auditLogCsv();
        }

        $page     = max(1, (int) $request->get('page', 1));
        $pageSize = min(200, max(1, (int) $request->get('page_size', 50)));
        $offset   = ($page - 1) * $pageSize;

        $total   = Inspection::count();
        $records = Inspection::latest()->skip($offset)->take($pageSize)->get();

        $out = $records->map(fn($i) => [
            'iso8601_timestamp'  => $i->created_at?->toIso8601String(),
            'instrument_id'      => $i->instrument_id ?? $this->generateInstrumentId($i->id),
            'operator_id'        => $i->operator_id   ?? 'OP-WEB-001',
            'defect_type'        => $this->normaliseDefectName($i->defect_type ?? 'unknown'),
            'confidence'         => $i->confidence ? round($i->confidence / 100, 4) : 0.0,
            'pass_fail'          => strtoupper($i->pass_fail ?? 'FAIL'),
            'processing_time_ms' => round((float) ($i->inference_ms ?? 0), 1),
            'bounding_box'       => $this->parseBoundingBox($i->bounding_box),
        ])->values()->all();

        return $this->cors(response()->json([
            'records' => $out,
            'total'   => $total,
            'page'    => $page,
        ]));
    }

    private function auditLogCsv(): JsonResponse
    {
        // Return as streaming CSV — reuse existing exportCsv logic
        abort(302, '', ['Location' => '/audit-log/export']);
    }

    // ── POST /api/react/inspect ──────────────────────────────────────────────
    public function inspect(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|image|mimes:jpeg,png,jpg,bmp|max:10240']);

        $image      = $request->file('file');
        $operatorId = $request->get('operator_id', 'OP-WEB-001');
        $imagePath  = $image->store('inspections', 'public');
        $fullPath   = storage_path('app/public/' . $imagePath);

        $instrId = 'INST-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $start   = microtime(true);

        // YOLO
        $yoloResult = $this->runYolo($fullPath);

        // Claude
        $threshold    = 70;
        $prompt       = $this->promptService->build($yoloResult, $threshold);
        $claudeResult = $this->runClaude($fullPath, $prompt);

        $processingMs = round((microtime(true) - $start) * 1000, 1);

        // Apply threshold
        if (strtoupper($claudeResult['pass_fail'] ?? '') === 'PASS'
            && ($claudeResult['confidence'] ?? 0) < $threshold) {
            $claudeResult['pass_fail'] = 'FLAGGED';
        }

        // Persist
        Inspection::create([
            'instrument_id'      => $instrId,
            'operator_id'        => $operatorId,
            'user_id'            => null,
            'image_path'         => $imagePath,
            'defect_type'        => $claudeResult['defect_type']       ?? 'Unknown',
            'confidence'         => $claudeResult['confidence']         ?? 0,
            'pass_fail'          => $claudeResult['pass_fail']          ?? 'FAIL',
            'claude_reasoning'   => $claudeResult['reasoning']          ?? '',
            'regulatory_note'    => $claudeResult['regulatory_note']    ?? null,
            'recommended_action' => $claudeResult['recommended_action'] ?? null,
            'yolo_detections'    => json_encode($yoloResult['detections'] ?? []),
            'yolo_count'         => $yoloResult['count']                ?? 0,
            'yolo_model'         => $yoloResult['model_used']           ?? 'unavailable',
            'inference_ms'       => $processingMs,
        ]);

        $conf         = (float) ($claudeResult['confidence'] ?? 0);
        $detections   = $yoloResult['detections'] ?? [];
        $firstBbox    = !empty($detections) ? ($detections[0]['bounding_box'] ?? null) : null;

        return $this->cors(response()->json([
            'pass_fail'          => strtoupper($claudeResult['pass_fail'] ?? 'FAIL'),
            'defect_type'        => $claudeResult['defect_type'] ?? 'Unknown',
            'confidence'         => round($conf / 100, 4),
            'instrument_id'      => $instrId,
            'timestamp'          => now()->toIso8601String(),
            'processing_time_ms' => $processingMs,
            'detection_count'    => count($detections),
            'plc_signal_sent'    => true,
            'bounding_box'       => $firstBbox,
            'all_detections'     => array_map(fn($d) => [
                'defect_type'  => $d['class_name']   ?? 'unknown',
                'confidence'   => $d['confidence']    ?? 0,
                'pass_fail'    => $d['severity'] === 'HIGH' ? 'FAIL' : 'PASS',
                'bounding_box' => $d['bounding_box']  ?? null,
            ], $detections),
        ]));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    private function runYolo(string $path): array
    {
        try {
            $h = Http::timeout(3)->get("{$this->yoloServiceUrl}/health");
            if (!$h->successful()) return $this->yoloFallback();
        } catch (\Exception $e) { return $this->yoloFallback(); }

        try {
            $r = Http::timeout(30)
                ->attach('file', file_get_contents($path), basename($path))
                ->post("{$this->yoloServiceUrl}/detect");
            return $r->successful() ? $r->json() : $this->yoloFallback();
        } catch (\Exception $e) { return $this->yoloFallback(); }
    }

    private function yoloFallback(): array
    {
        return ['success' => false, 'detections' => [], 'count' => 0,
                'model_used' => 'unavailable', 'inference_ms' => null, 'finetuned' => false];
    }

    private function runClaude(string $imagePath, string $prompt): array
    {
        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType  = mime_content_type($imagePath);

        $response = Http::withHeaders([
            'x-api-key'         => $this->anthropicApiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->withoutVerifying()->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 600,
            'messages'   => [[
                'role'    => 'user',
                'content' => [
                    ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mimeType, 'data' => $imageData]],
                    ['type' => 'text',  'text'   => $prompt],
                ],
            ]],
        ]);

        if (!$response->successful()) {
            return ['defect_type' => 'Analysis Failed', 'confidence' => 0, 'pass_fail' => 'FAIL',
                    'reasoning' => 'API error.', 'regulatory_note' => null, 'recommended_action' => null];
        }

        $content = preg_replace('/```json\s*|\s*```/', '', $response->json()['content'][0]['text'] ?? '');
        $parsed  = json_decode($content, true);
        return $parsed ?: ['defect_type' => 'Parse Error', 'confidence' => 0, 'pass_fail' => 'FAIL',
                           'reasoning' => $content, 'regulatory_note' => null, 'recommended_action' => null];
    }


    private function normaliseDefectName(string $raw): string
    {
        $lower    = strtolower(trim($raw));
        // Strip trailing instrument qualifiers: "none — curved scissors 7in" → "none"
        $stripped = preg_replace('/\s*[—\-\/]\s*.+$/', '', $lower);
        return match(true) {
            str_contains($lower, 'corrosion') || str_contains($lower, 'oxidation')       => 'corrosion',
            str_contains($lower, 'scratch')   || str_contains($lower, 'burr')            => 'scratches',
            str_contains($lower, 'crack')     || str_contains($lower, 'fracture')        => 'cracks',
            str_contains($lower, 'porosity')                                              => 'porosity',
            str_contains($lower, 'misalign')  || str_contains($lower, 'dimensional')     => 'misalignment',
            str_contains($lower, 'contamination')                                         => 'contamination',
            str_contains($lower, 'discolor')  || str_contains($lower, 'discolour')       => 'discoloration',
            str_contains($lower, 'deformation') || str_contains($lower, 'deform')        => 'deformation',
            str_contains($lower, 'tip variance') || str_contains($lower, 'finish irreg') => 'surface defect',
            str_contains($lower, 'misclassif') || str_contains($lower, 'non-surgical')   => 'unclassified',
            str_contains($lower, 'no defect') || $stripped === 'none'
                || $lower === 'none' || str_starts_with($lower, 'none')                  => 'none',
            default => $stripped ?: $lower,
        };
    }


    private function generateInstrumentId(int $id): string
    {
        return 'SURG-' . strtoupper(substr(md5('cosmas' . $id), 0, 8));
    }

    private function parseBoundingBox(?string $raw): ?array
    {
        if (!$raw) return null;
        $decoded = json_decode($raw, true);
        if (!$decoded || !isset($decoded['x1'])) return null;
        return ['x1' => $decoded['x1'], 'y1' => $decoded['y1'],
                'x2' => $decoded['x2'], 'y2' => $decoded['y2']];
    }
}
