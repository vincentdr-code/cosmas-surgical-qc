<?php

namespace App\Services;

use App\Models\Inspection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * COSMAS SENTRY — Inspection Agent Orchestrator
 *
 * Claude operates in an autonomous tool-use loop, deciding at each step
 * which tool to call based on what it has learned so far. The six tools
 * represent the phases of a compliant, adaptive QC inspection:
 *
 *   1. check_image_quality        — reject bad images before wasting analysis
 *   2. run_yolo_scan              — two-stage computer vision (YOLOv8n + YOLOv8s)
 *   2b. request_focused_rescan    — Claude calls this autonomously when instrument
 *                                   confidence is low — true agentic re-inspection
 *   3. lookup_regulatory_context  — FDA/ISO standards for the detected defect
 *   4. calculate_risk_score       — FMEA RPN × Bayesian recall prior × instrument
 *                                   profile → CRS. Stage 1 output NOW drives Stage 2
 *                                   interpretation via INSTRUMENT_PROFILES.
 *   5. finalize_inspection_report — structured verdict + EV cost matrix
 *
 * Formula: CRS = (S × instrument_multiplier × O × D) × P(recall | defect_type) × trend_weight
 * Decision rule: argmin[E(action)] subject to: E(Pass) = ∞ when CRS ≥ 300
 *
 * ADR-007: Instrument-Adaptive Inspection Profiles
 * The same surface defect has different clinical consequences depending on
 * the instrument type. A crack on a scalpel blade risks intraoperative
 * fragmentation; the same crack on a retractor is serious but not immediately
 * life-threatening. INSTRUMENT_PROFILES encodes this clinical reality into the
 * risk calculation so Stage 1 classification meaningfully influences the verdict.
 */
class InspectionOrchestratorService
{
    private string $yoloServiceUrl = 'http://127.0.0.1:8001';
    private string $anthropicApiKey;
    private const  MAX_LOOP_ITERATIONS = 12;

    /**
     * Instrument-Adaptive Clinical Profiles (ADR-007)
     *
     * Each profile encodes:
     *   confidence_threshold  — minimum YOLO defect confidence before auto-escalating
     *                           to FLAGGED. Scalpels are stricter; retractors more lenient.
     *   severity_multipliers  — per-defect-type multiplier applied to S in the CRS formula.
     *                           Grounds the math in clinical consequence, not just surface detection.
     *   critical_defects      — defects that always override verdict to FAIL regardless of CRS.
     *   iso_standard          — applicable ISO 7153-x standard for this instrument class.
     *   clinical_role         — one-line description used to enrich Claude's reasoning chain.
     *
     * Sources: ISO 7153 series, FDA MAUDE recall database (SIC 3841),
     *          ECRI Institute surgical instrument failure mode library.
     */
    private const INSTRUMENT_PROFILES = [
        'scalpel' => [
            'confidence_threshold' => 0.45,
            'severity_multipliers' => [
                'crack'      => 1.5,   // fragment retention in surgical site
                'corrosion'  => 1.4,   // metal ion leaching during incision
                'porosity'   => 1.4,   // stress fracture precursor on blade
                'scratch'    => 1.3,   // edge integrity affects incision precision
                'default'    => 1.3,
            ],
            'critical_defects' => ['crack', 'corrosion', 'porosity'],
            'iso_standard'     => 'ISO 7153-1, ASTM F899',
            'clinical_role'    => 'Direct tissue incision — zero structural defect tolerance.',
        ],
        'forceps' => [
            'confidence_threshold' => 0.55,
            'severity_multipliers' => [
                'crack'      => 1.3,
                'corrosion'  => 1.2,
                'porosity'   => 1.1,
                'scratch'    => 0.9,   // handle scratches cosmetic only
                'default'    => 1.1,
            ],
            'critical_defects' => ['crack', 'corrosion'],
            'iso_standard'     => 'ISO 7153-2',
            'clinical_role'    => 'Tissue grasping — jaw mechanism integrity critical, handle wear acceptable.',
        ],
        'scissors' => [
            'confidence_threshold' => 0.50,
            'severity_multipliers' => [
                'crack'      => 1.4,
                'corrosion'  => 1.2,
                'porosity'   => 1.2,
                'scratch'    => 1.1,
                'default'    => 1.2,
            ],
            'critical_defects' => ['crack', 'corrosion'],
            'iso_standard'     => 'ISO 7153-4',
            'clinical_role'    => 'Tissue dissection — blade alignment and edge integrity determine cutting precision.',
        ],
        'needle_holder' => [
            'confidence_threshold' => 0.55,
            'severity_multipliers' => [
                'crack'      => 1.3,
                'corrosion'  => 1.1,
                'porosity'   => 1.0,
                'scratch'    => 0.8,
                'default'    => 1.1,
            ],
            'critical_defects' => ['crack'],
            'iso_standard'     => 'ISO 7153-5',
            'clinical_role'    => 'Suture needle control — jaw grip mechanism integrity critical, handle cosmetics secondary.',
        ],
        'clamp' => [
            'confidence_threshold' => 0.60,
            'severity_multipliers' => [
                'crack'      => 1.2,
                'corrosion'  => 1.1,
                'porosity'   => 0.9,
                'scratch'    => 0.7,
                'default'    => 1.0,
            ],
            'critical_defects' => ['crack'],
            'iso_standard'     => 'ISO 7153-3',
            'clinical_role'    => 'Vessel and tissue occlusion — ratchet mechanism integrity critical, surface wear acceptable.',
        ],
        'retractor' => [
            'confidence_threshold' => 0.65,
            'severity_multipliers' => [
                'crack'      => 1.2,
                'corrosion'  => 1.0,
                'porosity'   => 0.8,
                'scratch'    => 0.6,   // surface wear cosmetically acceptable
                'default'    => 0.9,
            ],
            'critical_defects' => ['crack'],
            'iso_standard'     => 'ISO 7153-6',
            'clinical_role'    => 'Tissue retraction — structural integrity matters, surface cosmetics acceptable.',
        ],
    ];

    public function __construct()
    {
        $this->anthropicApiKey = config('services.anthropic.key');
    }

    // ══════════════════════════════════════════════════════════════════
    // PUBLIC ENTRY POINT
    // ══════════════════════════════════════════════════════════════════

    public function runInspection(string $imagePath, int $confidenceThreshold = 70): array
    {
        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType  = mime_content_type($imagePath);

        $messages = [[
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
                ['type' => 'text', 'text' => $this->getSystemPrompt()],
            ],
        ]];

        $agentSteps  = [];
        $finalReport = null;
        $iteration   = 0;

        while ($iteration < self::MAX_LOOP_ITERATIONS) {
            $iteration++;

            $response = $this->callClaude($messages, $this->getToolDefinitions());

            if (!$response) {
                break;
            }

            $stopReason = $response['stop_reason'] ?? 'end_turn';
            $content    = $response['content'] ?? [];

            $messages[] = ['role' => 'assistant', 'content' => $content];

            if ($stopReason === 'end_turn') {
                break;
            }

            if ($stopReason === 'tool_use') {
                $toolResultBlocks = [];

                foreach ($content as $block) {
                    if (($block['type'] ?? '') !== 'tool_use') {
                        continue;
                    }

                    $toolName  = $block['name'];
                    $toolInput = $block['input'] ?? [];
                    $toolId    = $block['id'];

                    if ($toolName === 'finalize_inspection_report') {
                        $finalReport = $toolInput;
                        $agentSteps[] = [
                            'tool'   => $toolName,
                            'input'  => $toolInput,
                            'result' => ['status' => 'report_finalized'],
                        ];
                        break 2;
                    }

                    $result = $this->executeTool($toolName, $toolInput, $imagePath);

                    $agentSteps[] = [
                        'tool'   => $toolName,
                        'input'  => $toolInput,
                        'result' => $result,
                    ];

                    $toolResultBlocks[] = [
                        'type'        => 'tool_result',
                        'tool_use_id' => $toolId,
                        'content'     => json_encode($result),
                    ];
                }

                if (!empty($toolResultBlocks)) {
                    $messages[] = ['role' => 'user', 'content' => $toolResultBlocks];
                }
            }
        }

        if ($finalReport) {
            $finalReport = $this->applyConfidenceThreshold($finalReport, $confidenceThreshold);
        } else {
            $finalReport = $this->fallbackReport();
        }

        $finalReport['agent_steps'] = $agentSteps;
        return $finalReport;
    }

    // ══════════════════════════════════════════════════════════════════
    // SYSTEM PROMPT
    // ══════════════════════════════════════════════════════════════════

    private function getSystemPrompt(): string
    {
        return <<<PROMPT
You are the COSMAS SENTRY Inspection Agent — an autonomous AI quality-control system for surgical instrument manufacturing under FDA 21 CFR Part 820, ISO 13485, ISO 7153-1, and ASTM F899.

You have been given an image of a surgical instrument to inspect. Work through the inspection systematically using your tools.

STEP 1 — check_image_quality:
Always run this first. If quality is POOR or RESUBMIT, call finalize_inspection_report with verdict FLAGGED.

STEP 2 — run_yolo_scan:
Run the two-stage computer vision pipeline (instrument classifier + defect detector).
After receiving the result, check the instrument_class_confidence field.
  - If instrument_class_confidence < 0.55 OR instrument_class is "unknown": call request_focused_rescan
    before proceeding. Choose the focus_area most likely to reveal the instrument type
    (e.g., "center" for the body, "top" for the tip, "bottom" for the handle).
    This is your judgment call — use it. The system cannot classify an instrument it cannot see clearly.
  - If instrument_class_confidence >= 0.55: proceed to Step 3.

STEP 2b — request_focused_rescan (call ONLY when needed per Step 2 criteria):
Provide a focus_area and a reason explaining why you need a second look.
Compare the rescan result to the original. Use whichever gives you higher confidence.

STEP 3 — lookup_regulatory_context:
Look up FDA/ISO standards for the specific defect type AND instrument class.
Always pass both. The instrument class determines which ISO 7153-x standard applies.

STEP 4 — calculate_risk_score:
IMPORTANT: Always pass instrument_class. The calculate_risk_score tool applies
instrument-specific severity multipliers from a clinical profile database (ADR-007).
This means the same defect type produces a different CRS for a scalpel vs. a retractor —
because the clinical consequences are genuinely different. This is by design.
When you receive the result, look at the instrument_profile_applied field —
it tells you exactly what multiplier was applied and why.

STEP 5 — finalize_inspection_report:
Generate the final structured verdict. In your reasoning, explicitly state:
  - What the instrument is and its clinical role
  - What defect was detected and at what confidence
  - What instrument-specific severity multiplier was applied and why
  - What the CRS is and what risk tier it falls in
  - What the recommended action is and the cost justification

RULES:
- Never skip steps. Each tool call informs the next.
- request_focused_rescan is for YOUR use when you are uncertain — exercise judgment.
- The instrument profile multiplier is already applied inside calculate_risk_score.
  Do not manually adjust severity in your reasoning to compensate — trust the formula.
- You augment human judgment — you do not replace it.
PROMPT;
    }

    // ══════════════════════════════════════════════════════════════════
    // TOOL DEFINITIONS
    // ══════════════════════════════════════════════════════════════════

    private function getToolDefinitions(): array
    {
        return [
            [
                'name'         => 'check_image_quality',
                'description'  => 'Performs image quality pre-assessment. Checks sharpness, exposure, and whether the instrument is properly framed. Returns quality_status (OK / POOR / RESUBMIT), a numeric quality_score 0–100, and a reason string. Run this first, always.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'image_path' => ['type' => 'string', 'description' => 'Pass the literal string "uploaded_image"'],
                    ],
                    'required' => ['image_path'],
                ],
            ],
            [
                'name'         => 'run_yolo_scan',
                'description'  => 'Runs the two-stage YOLOv8 computer vision pipeline. Stage 1 classifies instrument type (scalpel, forceps, scissors, needle_holder, clamp, retractor) with a confidence score. Stage 2 detects surface defects (cracks, corrosion, scratches, porosity, none). Returns instrument_class, instrument_class_confidence, defect detections, bounding boxes, and confidence scores. Check instrument_class_confidence after this call — if below 0.55 or instrument is unclassified, call request_focused_rescan.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'image_path' => ['type' => 'string', 'description' => 'Pass the literal string "uploaded_image"'],
                    ],
                    'required' => ['image_path'],
                ],
            ],
            [
                'name'         => 'request_focused_rescan',
                'description'  => 'Call this when instrument_class_confidence from run_yolo_scan is below 0.55 or the instrument type is uncertain. Crops the image to a specific region and re-runs the full YOLO pipeline on that crop. Use when you need a closer look at a specific part of the instrument to get a reliable classification. Returns the same fields as run_yolo_scan plus rescan_note and focus_area.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'focus_area' => [
                            'type' => 'string',
                            'enum' => ['full', 'center', 'top', 'bottom', 'left_half', 'right_half'],
                            'description' => 'Which region of the image to focus on. Use "center" for instrument body, "top" for tip/blade, "bottom" for handle, "full" to re-run complete image.',
                        ],
                        'reason' => [
                            'type' => 'string',
                            'description' => 'Explain why you are requesting a focused rescan — what you are uncertain about and what you expect the closer look to reveal.',
                        ],
                    ],
                    'required' => ['focus_area', 'reason'],
                ],
            ],
            [
                'name'         => 'lookup_regulatory_context',
                'description'  => 'Retrieves FDA 21 CFR Part 820 and ISO 7153-x compliance context for a specific defect type and instrument class. Returns applicable_standard, severity_baseline (1–10), recall_prior probability, sterilization_risk flag, and required_documentation. Always pass both defect_type and instrument_class — the instrument class determines which ISO 7153 section applies.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'defect_type'      => ['type' => 'string', 'description' => 'Defect type from YOLO scan (e.g., crack, corrosion, scratch, porosity, no_defect)'],
                        'instrument_class' => ['type' => 'string', 'description' => 'Instrument type from YOLO Stage 1 (e.g., scalpel, forceps, scissors, needle_holder, clamp, retractor)'],
                    ],
                    'required' => ['defect_type', 'instrument_class'],
                ],
            ],
            [
                'name'         => 'calculate_risk_score',
                'description'  => 'Calculates the Composite Risk Score (CRS) using FMEA methodology with instrument-adaptive severity multipliers (ADR-007). Formula: CRS = (S × instrument_multiplier × O × D) × P(recall|defect) × trend_weight. The instrument_class drives the multiplier — a crack on a scalpel scores higher than a crack on a retractor because the clinical consequence differs. Always pass instrument_class. Returns CRS, risk_level, formula_breakdown including the instrument_profile_applied field, and cost_matrix.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'defect_type'      => ['type' => 'string'],
                        'severity_score'   => ['type' => 'number', 'description' => 'Severity baseline from regulatory lookup (1–10). The instrument profile multiplier will be applied on top of this.'],
                        'yolo_confidence'  => ['type' => 'number', 'description' => 'Best YOLO defect detection confidence (0–1). Use 0.5 if YOLO unavailable.'],
                        'instrument_class' => ['type' => 'string', 'description' => 'Required. Instrument type from Stage 1 — determines which clinical profile and severity multiplier to apply.'],
                        'recall_prior'     => ['type' => 'number', 'description' => 'P(recall|defect_type) from regulatory lookup'],
                    ],
                    'required' => ['defect_type', 'severity_score', 'yolo_confidence', 'instrument_class'],
                ],
            ],
            [
                'name'         => 'finalize_inspection_report',
                'description'  => 'Generates the final structured inspection report. ALWAYS call this as the last step. Your reasoning must reference the instrument class, the instrument profile that was applied, the defect detected, and the CRS formula breakdown.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'verdict'              => ['type' => 'string', 'enum' => ['PASS', 'FAIL', 'FLAGGED']],
                        'confidence'           => ['type' => 'integer', 'description' => 'AI confidence 0–100'],
                        'defect_type'          => ['type' => 'string'],
                        'instrument_class'     => ['type' => 'string'],
                        'reasoning'            => ['type' => 'string', 'description' => 'Full reasoning chain — must cite: instrument type and clinical role, defect detected, instrument profile multiplier applied, CRS value and risk tier, recommended action with cost justification.'],
                        'risk_level'           => ['type' => 'string', 'enum' => ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']],
                        'composite_risk_score' => ['type' => 'number'],
                        'regulatory_note'      => ['type' => 'string'],
                        'recommended_action'   => ['type' => 'string'],
                        'cost_matrix'          => [
                            'type'        => 'object',
                            'properties'  => [
                                'pass_expected_liability' => ['type' => 'number'],
                                'discard_cost'            => ['type' => 'number'],
                                'review_cost'             => ['type' => 'number'],
                                'recommendation'          => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'required' => ['verdict', 'confidence', 'defect_type', 'reasoning', 'risk_level'],
                ],
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // TOOL ROUTER
    // ══════════════════════════════════════════════════════════════════

    private function executeTool(string $name, array $input, string $imagePath): array
    {
        return match ($name) {
            'check_image_quality'       => $this->toolCheckImageQuality($imagePath),
            'run_yolo_scan'             => $this->toolRunYoloScan($imagePath),
            'request_focused_rescan'    => $this->toolRequestFocusedRescan(
                $imagePath,
                $input['focus_area'] ?? 'full',
                $input['reason']     ?? 'Low instrument confidence'
            ),
            'lookup_regulatory_context' => $this->toolLookupRegulatoryContext(
                $input['defect_type']      ?? 'unknown',
                $input['instrument_class'] ?? ''
            ),
            'calculate_risk_score'      => $this->toolCalculateRiskScore($input),
            default                     => ['error' => "Unknown tool: {$name}"],
        };
    }

    // ══════════════════════════════════════════════════════════════════
    // TOOL 1 — IMAGE QUALITY CHECK
    // ══════════════════════════════════════════════════════════════════

    private function toolCheckImageQuality(string $imagePath): array
    {
        try {
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return ['quality_status' => 'RESUBMIT', 'quality_score' => 0, 'reason' => 'File is not a valid image.', 'width' => 0, 'height' => 0, 'file_size_kb' => 0];
            }

            [$width, $height] = $imageInfo;
            $fileSizeKb = filesize($imagePath) / 1024;

            $score  = 100;
            $issues = [];

            if ($width < 200 || $height < 200) {
                $score -= 40;
                $issues[] = "Image resolution too low ({$width}×{$height}px). Minimum 200×200 required.";
            } elseif ($width < 400 || $height < 400) {
                $score -= 15;
                $issues[] = "Low resolution ({$width}×{$height}px). Higher resolution improves accuracy.";
            }

            if ($fileSizeKb < 10) {
                $score -= 30;
                $issues[] = "File size very small ({$fileSizeKb}KB) — possible compression artifact or corrupt image.";
            }

            if (extension_loaded('gd') && $width <= 4000 && $height <= 4000) {
                $img = match ($imageInfo[2]) {
                    IMAGETYPE_JPEG => imagecreatefromjpeg($imagePath),
                    IMAGETYPE_PNG  => imagecreatefrompng($imagePath),
                    default        => null,
                };

                if ($img) {
                    $sampleSize = min(200, $width, $height);
                    $sample     = imagecreatetruecolor($sampleSize, $sampleSize);
                    imagecopy($sample, $img, 0, 0,
                        (int)(($width - $sampleSize) / 2),
                        (int)(($height - $sampleSize) / 2),
                        $sampleSize, $sampleSize);

                    $pixelValues = [];
                    $step = max(1, (int)($sampleSize / 20));
                    for ($x = 0; $x < $sampleSize; $x += $step) {
                        for ($y = 0; $y < $sampleSize; $y += $step) {
                            $rgb = imagecolorat($sample, $x, $y);
                            $r = ($rgb >> 16) & 0xFF;
                            $g = ($rgb >> 8)  & 0xFF;
                            $b = $rgb         & 0xFF;
                            $pixelValues[] = 0.299 * $r + 0.587 * $g + 0.114 * $b;
                        }
                    }

                    if (count($pixelValues) > 1) {
                        $mean     = array_sum($pixelValues) / count($pixelValues);
                        $variance = array_sum(array_map(fn($v) => ($v - $mean) ** 2, $pixelValues)) / count($pixelValues);

                        if ($variance < 50) {
                            $score -= 35;
                            $issues[] = 'Image appears blurry or overexposed (low pixel variance). Resubmit with better focus and lighting.';
                        } elseif ($variance < 150) {
                            $score -= 10;
                            $issues[] = 'Image sharpness is marginal. Results may be less accurate.';
                        }
                    }

                    imagedestroy($img);
                    imagedestroy($sample);
                }
            }

            $score  = max(0, $score);
            $status = match (true) {
                $score >= 70 => 'OK',
                $score >= 40 => 'POOR',
                default      => 'RESUBMIT',
            };

            return [
                'quality_status' => $status,
                'quality_score'  => $score,
                'reason'         => empty($issues) ? 'Image quality is acceptable for analysis.' : implode(' ', $issues),
                'width'          => $width,
                'height'         => $height,
                'file_size_kb'   => round($fileSizeKb, 1),
            ];
        } catch (\Exception $e) {
            Log::error('Image quality check failed: ' . $e->getMessage());
            return ['quality_status' => 'OK', 'quality_score' => 60, 'reason' => 'Quality check inconclusive — proceeding with analysis.'];
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // TOOL 2 — YOLO SCAN
    // ══════════════════════════════════════════════════════════════════

    private function toolRunYoloScan(string $imagePath): array
    {
        try {
            $health = Http::timeout(3)->get("{$this->yoloServiceUrl}/health");
            if (!$health->successful()) {
                return $this->yoloUnavailable();
            }
        } catch (\Exception) {
            return $this->yoloUnavailable();
        }

        try {
            $response = Http::timeout(30)
                ->attach('file', file_get_contents($imagePath), basename($imagePath))
                ->post("{$this->yoloServiceUrl}/detect");

            if ($response->successful()) {
                $data = $response->json();
                $data['summary'] = $this->buildYoloSummary($data);
                return $data;
            }

            Log::error('YOLO detect error: ' . $response->body());
            return $this->yoloUnavailable();
        } catch (\Exception $e) {
            Log::error('YOLO exception: ' . $e->getMessage());
            return $this->yoloUnavailable();
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // TOOL 2b — FOCUSED RESCAN (Agentic re-inspection)
    //
    // Claude calls this autonomously when instrument confidence is low.
    // Crops the image to the requested region and re-runs the full
    // YOLO pipeline on the crop. Claude then synthesizes both scans.
    //
    // This is NOT a fallback — it is an intentional agent behavior.
    // The system prompt instructs Claude to call this when it needs
    // a closer look, just as a human inspector would examine a specific
    // part of an instrument more carefully when unsure.
    // ══════════════════════════════════════════════════════════════════

    private function toolRequestFocusedRescan(string $imagePath, string $focusArea, string $reason): array
    {
        try {
            // GD is required for image cropping. If not installed, fall back to a full
            // re-run of the original image rather than crashing. The catch uses \Throwable
            // (not \Exception) because PHP 8 "Call to undefined function" errors are \Error,
            // which extends \Throwable but NOT \Exception.
            if (!extension_loaded('gd')) {
                Log::warning('Focused rescan: php-gd not installed — falling back to full rescan.');
                $result = $this->toolRunYoloScan($imagePath);
                return array_merge($result, [
                    'rescan_note'     => "GD extension not available — full rescan performed instead of cropped '{$focusArea}' region. Reason: {$reason}",
                    'focus_area'      => $focusArea,
                    'reason'          => $reason,
                    'rescan_performed'=> true,
                ]);
            }

            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return array_merge($this->yoloUnavailable(), [
                    'rescan_note' => 'Could not process image for crop. Original scan results stand.',
                    'focus_area'  => $focusArea,
                    'reason'      => $reason,
                ]);
            }

            [$width, $height] = $imageInfo;

            // Determine crop coordinates based on Claude's chosen focus area
            [$cropX, $cropY, $cropW, $cropH] = match ($focusArea) {
                'center'     => [(int)($width * 0.2),  (int)($height * 0.2),  (int)($width * 0.6),  (int)($height * 0.6)],
                'top'        => [0,                     0,                     $width,                (int)($height * 0.5)],
                'bottom'     => [0,                     (int)($height * 0.5), $width,                (int)($height * 0.5)],
                'left_half'  => [0,                     0,                    (int)($width * 0.5),   $height],
                'right_half' => [(int)($width * 0.5),  0,                    (int)($width * 0.5),   $height],
                default      => [0,                     0,                     $width,                $height],
            };

            // Load source image via GD
            $img = match ($imageInfo[2]) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($imagePath),
                IMAGETYPE_PNG  => imagecreatefrompng($imagePath),
                default        => null,
            };

            if (!$img) {
                // GD cannot load this format — re-run full scan as fallback
                $result = $this->toolRunYoloScan($imagePath);
                return array_merge($result, [
                    'rescan_note'     => "Could not crop image (unsupported format). Full rescan performed instead.",
                    'focus_area'      => $focusArea,
                    'reason'          => $reason,
                    'rescan_performed'=> true,
                ]);
            }

            // Crop to the focus region
            $cropped = imagecreatetruecolor($cropW, $cropH);
            imagecopy($cropped, $img, 0, 0, $cropX, $cropY, $cropW, $cropH);

            // Save crop to a temp file
            $tempPath = sys_get_temp_dir() . '/cosmas_rescan_' . uniqid() . '.jpg';
            imagejpeg($cropped, $tempPath, 92);
            imagedestroy($img);
            imagedestroy($cropped);

            // Run YOLO on the cropped image
            $result = $this->toolRunYoloScan($tempPath);
            @unlink($tempPath);

            // Annotate the result so Claude understands the context
            $result['rescan_note']      = "Focused rescan on '{$focusArea}' region ({$cropW}x{$cropH}px crop from {$width}x{$height}px original). Reason: {$reason}";
            $result['focus_area']       = $focusArea;
            $result['reason']           = $reason;
            $result['rescan_performed'] = true;

            return $result;

        } catch (\Throwable $e) {
            // Catch \Throwable not \Exception — PHP 8 undefined function calls throw \Error
            Log::error('Focused rescan failed: ' . $e->getMessage());
            $fallback = $this->toolRunYoloScan($imagePath);
            return array_merge($fallback, [
                'rescan_note'     => 'Focused rescan encountered an error — full original scan used. Error: ' . $e->getMessage(),
                'focus_area'      => $focusArea,
                'reason'          => $reason,
                'rescan_performed'=> false,
            ]);
        }
    }

    private function buildYoloSummary(array $yolo): string
    {
        $instrumentInfo = '';
        if (!empty($yolo['instrument_class'])) {
            $conf = isset($yolo['instrument_class_confidence'])
                ? round($yolo['instrument_class_confidence'] * 100) . '%'
                : 'unknown confidence';
            $instrumentInfo = "Stage 1 instrument classification: {$yolo['instrument_class']} ({$conf}). ";
        }

        if (empty($yolo['detections'])) {
            return $instrumentInfo . 'Stage 2 defect scan: No defects detected above 25% confidence threshold.';
        }

        $lines = [$instrumentInfo . "Stage 2 defect scan ({$yolo['model_used']}): {$yolo['count']} detection(s) in {$yolo['inference_ms']}ms:"];
        foreach ($yolo['detections'] as $i => $det) {
            $conf    = round($det['confidence'] * 100);
            $lines[] = "  " . ($i + 1) . ". {$det['class_name']} — {$conf}% confidence — Severity: {$det['severity']}";
        }
        return implode("\n", $lines);
    }

    private function yoloUnavailable(): array
    {
        return [
            'success'                    => false,
            'detections'                 => [],
            'count'                      => 0,
            'model_used'                 => 'unavailable',
            'inference_ms'               => null,
            'finetuned'                  => false,
            'instrument_class'           => 'unknown',
            'instrument_class_confidence'=> 0,
            'summary'                    => 'YOLOv8 service unavailable — Claude will perform direct visual analysis only.',
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // TOOL 3 — REGULATORY CONTEXT LOOKUP
    // ══════════════════════════════════════════════════════════════════

    private function toolLookupRegulatoryContext(string $defectType, string $instrumentClass): array
    {
        $defectKey = strtolower(preg_replace('/[\s\-]+/', '_', $defectType));

        $regulatoryMap = [
            'surface_crack' => [
                'applicable_standard'    => 'FDA 21 CFR Part 820.80(c); ISO 7153-1 §6.2; ASTM F899-12a §7.4',
                'severity_baseline'      => 9,
                'recall_prior'           => 0.71,
                'sterilization_risk'     => true,
                'required_documentation' => 'DMR nonconformance report (NCR). Batch quarantine. Root cause analysis required before release.',
                'clinical_consequence'   => 'Crack propagation during procedure risks fragment retention in surgical site. Sterility breach via micro-fissure.',
            ],
            'crack' => [
                'applicable_standard'    => 'FDA 21 CFR Part 820.80(c); ISO 7153-1 §6.2',
                'severity_baseline'      => 9,
                'recall_prior'           => 0.71,
                'sterilization_risk'     => true,
                'required_documentation' => 'DMR nonconformance report. Batch quarantine.',
                'clinical_consequence'   => 'Structural failure risk during procedure. Sterility breach.',
            ],
            'corrosion' => [
                'applicable_standard'    => 'FDA 21 CFR Part 820.80(c); ISO 7153-1 §5.3; ASTM A967',
                'severity_baseline'      => 8,
                'recall_prior'           => 0.63,
                'sterilization_risk'     => true,
                'required_documentation' => 'Material review board (MRB) disposition required. Passivation verification.',
                'clinical_consequence'   => 'Metal ion leaching. Compromised sterility. Accelerated fatigue failure.',
            ],
            'rust' => [
                'applicable_standard'    => 'FDA 21 CFR Part 820.80(c); ASTM A967',
                'severity_baseline'      => 8,
                'recall_prior'           => 0.63,
                'sterilization_risk'     => true,
                'required_documentation' => 'MRB disposition. Passivation verification.',
                'clinical_consequence'   => 'Metal ion leaching. Compromised sterility.',
            ],
            'dimensional_variance' => [
                'applicable_standard'    => 'FDA 21 CFR Part 820.70(b); ISO 13485 §7.5.1; ASTM F899-12a §6',
                'severity_baseline'      => 7,
                'recall_prior'           => 0.44,
                'sterilization_risk'     => false,
                'required_documentation' => 'CMM inspection report. Engineering disposition required.',
                'clinical_consequence'   => 'Instrument may not fit intended anatomical space. Functional failure during procedure.',
            ],
            'surface_scratch' => [
                'applicable_standard'    => 'ISO 7153-1 §6.1; ASTM F899-12a §7.2',
                'severity_baseline'      => 4,
                'recall_prior'           => 0.12,
                'sterilization_risk'     => false,
                'required_documentation' => 'Visual inspection log entry. Supervisor discretion for release.',
                'clinical_consequence'   => 'Cosmetic concern. Functional impact minimal unless on cutting edge or sealing surface.',
            ],
            'scratch' => [
                'applicable_standard'    => 'ISO 7153-1 §6.1',
                'severity_baseline'      => 4,
                'recall_prior'           => 0.12,
                'sterilization_risk'     => false,
                'required_documentation' => 'Visual inspection log entry.',
                'clinical_consequence'   => 'Cosmetic concern. Minimal functional impact.',
            ],
            'contamination' => [
                'applicable_standard'    => 'FDA 21 CFR Part 820.75; ISO 11607-1; ISO 13485 §7.5.2',
                'severity_baseline'      => 9,
                'recall_prior'           => 0.78,
                'sterilization_risk'     => true,
                'required_documentation' => 'Sterility assurance review. Batch hold mandatory. FDA MDR evaluation required if distributed.',
                'clinical_consequence'   => 'Direct patient infection risk. Potential systemic sepsis.',
            ],
            'misalignment' => [
                'applicable_standard'    => 'FDA 21 CFR Part 820.70(b); ISO 13485 §7.5.1',
                'severity_baseline'      => 6,
                'recall_prior'           => 0.31,
                'sterilization_risk'     => false,
                'required_documentation' => 'Functional test report. Engineering disposition.',
                'clinical_consequence'   => 'Reduced precision. Potential for unintended tissue damage.',
            ],
            'porosity' => [
                'applicable_standard'    => 'ASTM F899-12a §7.3; FDA 21 CFR Part 820.80(c)',
                'severity_baseline'      => 8,
                'recall_prior'           => 0.58,
                'sterilization_risk'     => true,
                'required_documentation' => 'Metallurgical analysis. Batch quarantine.',
                'clinical_consequence'   => 'Micro-pores harbor bacteria that survive sterilization. Silent sterility failure.',
            ],
            'burr' => [
                'applicable_standard'    => 'ISO 7153-1 §6.3; ASTM F899-12a §7.5',
                'severity_baseline'      => 5,
                'recall_prior'           => 0.18,
                'sterilization_risk'     => false,
                'required_documentation' => 'Deburring rework order. Re-inspection required.',
                'clinical_consequence'   => 'Unintended tissue laceration. Glove puncture risk for surgeon.',
            ],
            'discoloration' => [
                'applicable_standard'    => 'ISO 7153-1 §5.3; ASTM A967',
                'severity_baseline'      => 3,
                'recall_prior'           => 0.08,
                'sterilization_risk'     => false,
                'required_documentation' => 'Visual inspection log. Passivation check recommended.',
                'clinical_consequence'   => 'May indicate early corrosion. Cosmetic concern if isolated.',
            ],
            'no_defect' => [
                'applicable_standard'    => 'FDA 21 CFR Part 820.80(a) — Acceptance criteria met',
                'severity_baseline'      => 1,
                'recall_prior'           => 0.01,
                'sterilization_risk'     => false,
                'required_documentation' => 'Standard acceptance record in DHR.',
                'clinical_consequence'   => 'None identified.',
            ],
            'no_defect_detected' => [
                'applicable_standard'    => 'FDA 21 CFR Part 820.80(a) — Acceptance criteria met',
                'severity_baseline'      => 1,
                'recall_prior'           => 0.01,
                'sterilization_risk'     => false,
                'required_documentation' => 'Standard acceptance record in DHR.',
                'clinical_consequence'   => 'None identified.',
            ],
        ];

        $matched = $regulatoryMap[$defectKey] ?? null;

        if (!$matched) {
            foreach ($regulatoryMap as $key => $context) {
                if (str_contains($defectKey, $key) || str_contains($key, $defectKey)) {
                    $matched = $context;
                    break;
                }
            }
        }

        if (!$matched) {
            $matched = [
                'applicable_standard'    => 'FDA 21 CFR Part 820.80 — Requires engineering evaluation',
                'severity_baseline'      => 6,
                'recall_prior'           => 0.35,
                'sterilization_risk'     => false,
                'required_documentation' => 'Engineering evaluation required before disposition.',
                'clinical_consequence'   => 'Unknown — requires qualified personnel review.',
            ];
        }

        // Append instrument-specific ISO standard note if profile exists
        $instrumentKey = strtolower(str_replace([' ', '-'], '_', $instrumentClass));
        if (isset(self::INSTRUMENT_PROFILES[$instrumentKey])) {
            $profile = self::INSTRUMENT_PROFILES[$instrumentKey];
            $matched['instrument_iso_standard'] = $profile['iso_standard'];
            $matched['instrument_clinical_role'] = $profile['clinical_role'];
            $matched['instrument_profile_note']  = "Instrument-specific severity multiplier will be applied in calculate_risk_score per ADR-007.";
        }

        $matched['defect_type']      = $defectType;
        $matched['instrument_class'] = $instrumentClass ?: 'unspecified';
        return $matched;
    }

    // ══════════════════════════════════════════════════════════════════
    // TOOL 4 — COMPOSITE RISK SCORE (FMEA + BAYESIAN + INSTRUMENT PROFILE)
    //
    // CRS = (S × instrument_multiplier × O × D) × P(recall | defect_type) × trend_weight
    //
    // S  = Severity (1–10, from regulatory lookup × instrument profile multiplier)
    // O  = Occurrence (1–10, from 30-day inspection history)
    // D  = Detection difficulty (inverse of YOLO confidence)
    // instrument_multiplier = from INSTRUMENT_PROFILES[instrument_class][defect_type]
    // P(recall|defect)  = Bayesian prior from FDA MAUDE database
    // trend_weight      = 7-day vs 30-day defect rate ratio
    //
    // This is where Stage 1 (instrument classification) finally drives Stage 2
    // (defect severity interpretation). ADR-007 documents the clinical rationale.
    // ══════════════════════════════════════════════════════════════════

    private function toolCalculateRiskScore(array $input): array
    {
        $severityBase    = min(10, max(1, (float)($input['severity_score']  ?? 5)));
        $yoloConfidence  = min(1,  max(0, (float)($input['yolo_confidence'] ?? 0.5)));
        $recallPrior     = min(1,  max(0.01, (float)($input['recall_prior'] ?? 0.35)));
        $defectType      = strtolower($input['defect_type'] ?? 'unknown');
        $instrumentRaw   = $input['instrument_class'] ?? '';
        $instrumentKey   = strtolower(str_replace([' ', '-'], '_', $instrumentRaw));

        // ── Instrument-Adaptive Severity (ADR-007) ───────────────────
        // Look up the clinical profile for this instrument. Apply the
        // defect-specific severity multiplier to S before computing RPN.
        // This is the bridge between Stage 1 classification and Stage 2 scoring.
        $profile            = self::INSTRUMENT_PROFILES[$instrumentKey] ?? null;
        $severityMultiplier = 1.0;
        $profileApplied     = 'No instrument profile matched — default multiplier (1.0x) applied.';

        if ($profile) {
            // Match defect type against profile multipliers (fuzzy match)
            $defectNorm = preg_replace('/[\s_\-]+/', '', $defectType);
            foreach ($profile['severity_multipliers'] as $key => $multiplier) {
                if ($key === 'default') continue;
                if (str_contains($defectNorm, $key) || str_contains($key, $defectNorm)) {
                    $severityMultiplier = $multiplier;
                    break;
                }
            }
            // Fall back to profile's default multiplier if no specific match
            if ($severityMultiplier === 1.0) {
                $severityMultiplier = $profile['severity_multipliers']['default'] ?? 1.0;
            }

            $adjustedS = round(min(10, $severityBase * $severityMultiplier), 2);

            $profileApplied = implode(' ', [
                "Instrument profile '{$instrumentKey}' loaded (ADR-007).",
                "ISO standard: {$profile['iso_standard']}.",
                "Clinical role: {$profile['clinical_role']}.",
                "Defect '{$defectType}' multiplier: x{$severityMultiplier}.",
                "Base S={$severityBase} adjusted to S={$adjustedS}.",
                $severityMultiplier > 1.0
                    ? "Severity ELEVATED — this defect is more dangerous on a {$instrumentKey}."
                    : ($severityMultiplier < 1.0
                        ? "Severity REDUCED — this defect is less critical on a {$instrumentKey}."
                        : "Severity UNCHANGED — profile neutral for this defect type."),
            ]);

            $severityScore = $adjustedS;
        } else {
            $severityScore = $severityBase;
        }

        // ── Occurrence: 30-day defect rate → 1–10 ────────────────────
        $total30   = Inspection::where('created_at', '>=', now()->subDays(30))->count();
        $defects30 = Inspection::where('created_at', '>=', now()->subDays(30))
            ->whereIn('pass_fail', ['FAIL', 'FLAGGED'])->count();
        $defectRate30 = $total30 > 0 ? $defects30 / $total30 : 0.15;
        $occurrence   = (int) max(1, min(10, round($defectRate30 * 10 + 1)));

        // ── Detection difficulty: inverse YOLO confidence → 1–10 ─────
        $detection = (int) max(1, min(10, round((1 - $yoloConfidence) * 9 + 1)));

        // ── RPN ───────────────────────────────────────────────────────
        $rpn = $severityScore * $occurrence * $detection;

        // ── Trend weight: 7-day vs 30-day rate ───────────────────────
        $total7   = Inspection::where('created_at', '>=', now()->subDays(7))->count();
        $defects7 = Inspection::where('created_at', '>=', now()->subDays(7))
            ->whereIn('pass_fail', ['FAIL', 'FLAGGED'])->count();
        $defectRate7 = $total7 > 0 ? $defects7 / $total7 : $defectRate30;
        $trendWeight = $defectRate30 > 0
            ? max(0.5, min(2.0, $defectRate7 / $defectRate30))
            : 1.0;

        // ── CRS ───────────────────────────────────────────────────────
        $crs = round($rpn * $recallPrior * $trendWeight, 1);

        $riskLevel = match (true) {
            $crs >= 300 => 'CRITICAL',
            $crs >= 150 => 'HIGH',
            $crs >= 50  => 'MEDIUM',
            default     => 'LOW',
        };

        // ── Instrument critical-defect override ───────────────────────
        // Some defects are always FAIL for specific instruments regardless of CRS.
        $criticalOverride = false;
        $criticalNote     = null;
        if ($profile && !empty($profile['critical_defects'])) {
            $defectNorm = preg_replace('/[\s_\-]+/', '', $defectType);
            foreach ($profile['critical_defects'] as $critDefect) {
                if (str_contains($defectNorm, $critDefect) || str_contains($critDefect, $defectNorm)) {
                    $criticalOverride = true;
                    $criticalNote = "Critical defect override: '{$defectType}' is a critical defect for {$instrumentKey} per ADR-007. Verdict must be FAIL or FLAGGED regardless of CRS tier.";
                    break;
                }
            }
        }

        // ── Expected Value cost matrix ────────────────────────────────
        $unitCost   = 45;
        $recallCost = 2_500_000;
        $reviewCost = 59;

        $pDefectReachesPatient = min(0.95, ($crs / 1000) * $recallPrior);
        $ePass    = ($crs >= 300 || $criticalOverride) ? PHP_INT_MAX : round($pDefectReachesPatient * $recallCost);
        $eDiscard = $unitCost;
        $eReview  = $reviewCost;

        $recommendation = match (true) {
            $criticalOverride                  => "FAIL — {$instrumentKey} critical defect. Instrument must not reach OR.",
            $crs >= 300                        => 'DISCARD — CRS CRITICAL. Liability exposure exceeds all alternatives.',
            $ePass <= $eDiscard                => 'PASS — expected liability acceptable.',
            $eDiscard <= $eReview              => 'DISCARD — cheaper than review, far cheaper than liability.',
            default                            => 'REVIEW — cost-optimal given uncertainty.',
        };

        return [
            'composite_risk_score'    => $crs,
            'risk_level'              => $riskLevel,
            'critical_defect_override'=> $criticalOverride,
            'critical_override_note'  => $criticalNote,
            'instrument_profile_applied' => $profileApplied,
            'formula_breakdown'       => [
                'S_severity_base'      => $severityBase,
                'S_instrument_mult'    => $severityMultiplier,
                'S_adjusted'           => $severityScore,
                'O_occurrence'         => $occurrence,
                'D_detection'          => $detection,
                'RPN'                  => $rpn,
                'recall_prior'         => $recallPrior,
                'trend_weight'         => round($trendWeight, 2),
                'formula'              => "CRS = ({$severityScore} × {$occurrence} × {$detection}) × {$recallPrior} × {$trendWeight} = {$crs}",
            ],
            'historical_context'      => [
                '30_day_inspections'   => $total30,
                '30_day_defect_rate'   => round($defectRate30 * 100, 1) . '%',
                '7_day_defect_rate'    => round($defectRate7 * 100, 1) . '%',
                'trend'                => $trendWeight > 1.1 ? 'WORSENING' : ($trendWeight < 0.9 ? 'IMPROVING' : 'STABLE'),
            ],
            'cost_matrix'             => [
                'pass_expected_liability' => $ePass === PHP_INT_MAX ? 'BLOCKED — critical defect or CRS CRITICAL' : $ePass,
                'discard_cost'            => $eDiscard,
                'review_cost'             => $eReview,
                'recommendation'          => $recommendation,
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // CLAUDE API CALL
    // ══════════════════════════════════════════════════════════════════

    private function callClaude(array $messages, array $tools): ?array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->anthropicApiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->withoutVerifying()->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 4096,
                'tools'      => $tools,
                'messages'   => $messages,
            ]);

            if (!$response->successful()) {
                Log::error('Claude API error: ' . $response->body());
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Claude API exception: ' . $e->getMessage());
            return null;
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════

    private function applyConfidenceThreshold(array $report, int $threshold): array
    {
        if (strtoupper($report['verdict'] ?? '') === 'PASS'
            && ($report['confidence'] ?? 0) < $threshold) {
            $report['verdict']            = 'FLAGGED';
            $report['reasoning']          = ($report['reasoning'] ?? '')
                . " [Auto-flagged: AI confidence ({$report['confidence']}%) is below your QC threshold ({$threshold}%). Human review required per FDA 21 CFR Part 11.]";
            $report['recommended_action'] = 'Flag for supervisor review — confidence threshold not met';
        }
        return $report;
    }

    private function fallbackReport(): array
    {
        return [
            'verdict'              => 'FAIL',
            'confidence'           => 0,
            'defect_type'          => 'Analysis Failed',
            'instrument_class'     => 'Unknown',
            'reasoning'            => 'The orchestrator failed to complete analysis. Check logs for API errors.',
            'risk_level'           => 'HIGH',
            'composite_risk_score' => 0,
            'regulatory_note'      => 'Manual inspection required — automated analysis unavailable.',
            'recommended_action'   => 'Route to manual QC inspection.',
            'cost_matrix'          => null,
        ];
    }
}
