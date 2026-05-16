<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\InspectionPromptService;

class InspectionController extends Controller
{
    private string $yoloServiceUrl = 'http://127.0.0.1:8001';
    private string $anthropicApiKey;

    public function __construct()
    {
        $this->anthropicApiKey = config('services.anthropic.key');
    }

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

        // ── Step 1: YOLOv8 pre-screening ──────────────────────────────────
        $yoloResult = $this->runYoloDetection($fullPath);

        // ── Step 2: Claude reasoning (with YOLO context) ──────────────────
        $claudeResult = $this->runClaudeAnalysis($fullPath, $yoloResult);


        // ── Step 2b: Apply user confidence threshold ───────────────────────
        // If Claude says PASS but isn't confident enough, downgrade to FLAGGED.
        // This keeps AI in an augmentation role — human QC has the final word.
        $threshold = Auth::user()->confidence_threshold ?? 70;
        if (strtoupper($claudeResult['pass_fail'] ?? '') === 'PASS'
            && ($claudeResult['confidence'] ?? 0) < $threshold) {
            $claudeResult['pass_fail'] = 'FLAGGED';
            $claudeResult['reasoning'] = ($claudeResult['reasoning'] ?? '')
                . " [Auto-flagged: AI confidence ({$claudeResult['confidence']}%) is below your QC threshold ({$threshold}%). Human review required per FDA 21 CFR Part 11.]";
            $claudeResult['recommended_action'] = 'Flag for supervisor review';
        }

        // ── Step 3: Persist to DB ──────────────────────────────────────────
        $inspection = Inspection::create([
            'user_id'            => Auth::id(),
            'image_path'         => $imagePath,
            'defect_type'        => $claudeResult['defect_type']        ?? 'Unknown',
            'confidence'         => $claudeResult['confidence']          ?? 0,
            'pass_fail'          => $claudeResult['pass_fail']           ?? 'FAIL',
            'claude_reasoning'   => $claudeResult['reasoning']           ?? '',
            'regulatory_note'    => $claudeResult['regulatory_note']     ?? null,
            'recommended_action' => $claudeResult['recommended_action']  ?? null,
            'yolo_detections'    => json_encode($yoloResult['detections'] ?? []),
            'yolo_count'         => $yoloResult['count']                 ?? 0,
            'yolo_model'         => $yoloResult['model_used']            ?? 'unavailable',
            'inference_ms'       => $yoloResult['inference_ms']          ?? null,
        ]);

        return redirect()->route('inspections.results', $inspection);
    }

    // ── Private: YOLOv8 detection ──────────────────────────────────────────
    private function runYoloDetection(string $imagePath): array
    {
        try {
            $health = Http::timeout(3)->get("{$this->yoloServiceUrl}/health");
            if (!$health->successful()) {
                Log::warning('YOLOv8 service unhealthy — skipping pre-screen');
                return $this->yoloUnavailable();
            }
        } catch (\Exception $e) {
            Log::warning('YOLOv8 service unreachable: ' . $e->getMessage());
            return $this->yoloUnavailable();
        }

        try {
            $response = Http::timeout(30)
                ->attach('file', file_get_contents($imagePath), basename($imagePath))
                ->post("{$this->yoloServiceUrl}/detect");

            if ($response->successful()) {
                return $response->json();
            }
            Log::error('YOLOv8 detect error: ' . $response->body());
            return $this->yoloUnavailable();
        } catch (\Exception $e) {
            Log::error('YOLOv8 exception: ' . $e->getMessage());
            return $this->yoloUnavailable();
        }
    }

    private function yoloUnavailable(): array
    {
        return [
            'success'      => false,
            'detections'   => [],
            'count'        => 0,
            'model_used'   => 'unavailable',
            'inference_ms' => null,
            'finetuned'    => false,
        ];
    }

    // ── Private: Claude Vision analysis ───────────────────────────────────
    private function runClaudeAnalysis(string $imagePath, array $yoloResult): array
    {
        $imageData   = base64_encode(file_get_contents($imagePath));
        $mimeType    = mime_content_type($imagePath);
        // Prompt is now managed by InspectionPromptService (versioned for audit trail)

        $prompt = <<<PROMPT
You are Cosmas Sentry, an AI quality-control assistant for surgical instrument manufacturing under ISO 7153-1 and ASTM F899 standards.

{$yoloContext}

Analyze this surgical instrument image and respond ONLY with valid JSON in this exact format:
{
  "defect_type": "string (e.g. Surface Corrosion, Dimensional Non-conformance, No Defect Detected, etc.)",
  "confidence": integer 0-100,
  "pass_fail": "PASS" or "FAIL",
  "reasoning": "2-3 sentences explaining your assessment, referencing relevant standards and the computer vision findings above",
  "recommended_action": "string (e.g. Release for use, Flag for supervisor review, Remove from production)",
  "regulatory_note": "Brief note on FDA/ISO compliance relevance"
}

Be calibrated: high confidence only when evidence is clear. If uncertain, lower confidence and recommend human review.
PROMPT;

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
                    [
                        'type'   => 'image',
                        'source' => [
                            'type'       => 'base64',
                            'media_type' => $mimeType,
                            'data'       => $imageData,
                        ],
                    ],
                    ['type' => 'text', 'text' => $prompt],
                ],
            ]],
        ]);

        if (!$response->successful()) {
            Log::error('Claude API error: ' . $response->body());
            return [
                'defect_type'        => 'Analysis Failed',
                'confidence'         => 0,
                'pass_fail'          => 'FAIL',
                'reasoning'          => 'Claude API error.',
                'regulatory_note'    => null,
                'recommended_action' => null,
            ];
        }

        $content = $response->json()['content'][0]['text'] ?? '';
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $parsed  = json_decode($content, true);

        if (!$parsed) {
            Log::error('Claude JSON parse failed: ' . $content);
            return [
                'defect_type'        => 'Parse Error',
                'confidence'         => 0,
                'pass_fail'          => 'FAIL',
                'reasoning'          => $content,
                'regulatory_note'    => null,
                'recommended_action' => null,
            ];
        }

        return $parsed;
    }

    private function buildYoloContext(array $yoloResult): string
    {
        if (!$yoloResult['success'] || empty($yoloResult['detections'])) {
            if ($yoloResult['model_used'] === 'unavailable') {
                return "Computer Vision Pre-screen: Service unavailable — relying on your direct image analysis.";
            }
            return "Computer Vision Pre-screen: YOLOv8 detected NO objects of concern in this image (0 detections above 25% confidence threshold).";
        }

        $model     = $yoloResult['model_used'];
        $finetuned = $yoloResult['finetuned'] ? 'fine-tuned on surgical instruments' : 'base COCO model';
        $count     = $yoloResult['count'];
        $ms        = $yoloResult['inference_ms'];

        $lines = ["Computer Vision Pre-screen: YOLOv8 ({$finetuned}) found {$count} detection(s) in {$ms}ms:"];
        foreach ($yoloResult['detections'] as $i => $det) {
            $n    = $i + 1;
            $name = $det['class_name'];
            $conf = round($det['confidence'] * 100);
            $sev  = $det['severity'];
            $lines[] = "  {$n}. {$name} — {$conf}% confidence — Severity: {$sev}";
        }
        $lines[] = "\nUse these findings to inform your analysis. High-severity detections should increase FAIL likelihood.";

        return implode("\n", $lines);
    }

    // ── Public: Results & Audit log ────────────────────────────────────────
    public function results(Inspection $inspection)
    {
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

    /**
     * Export the full audit log as a CSV file.
     * Supports FDA 21 CFR Part 11 audit trail requirements.
     */
    public function exportCsv()
    {
        $inspections = \App\Models\Inspection::latest()->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cosmas_audit_log_' . now()->format('Ymd_His') . '.csv"',
            'Cache-Control'       => 'no-store, no-cache',
        ];

        $callback = function () use ($inspections) {
            $handle = fopen('php://output', 'w');

            // CSV header row
            fputcsv($handle, [
                'ID', 'Pass/Fail', 'Defect Type', 'Confidence (%)',
                'Instrument Class', 'YOLOv8 Confidence (%)',
                'Regulatory Note', 'Inspector (User ID)', 'Created At',
            ]);

            foreach ($inspections as $row) {
                $conf     = $row->confidence ? round($row->confidence, 1) : '';
                $yoloConf = '';  // yolo_confidence not in current schema

                fputcsv($handle, [
                    $row->id,
                    $row->pass_fail ?? '',
                    $row->defect_type ?? '',
                    $conf,
                    $row->instrument_class ?? '',
                    $yoloConf,
                    $row->regulatory_note ?? '',
                    $row->user_id ?? '',
                    $row->created_at ? $row->created_at->toIso8601String() : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

}
