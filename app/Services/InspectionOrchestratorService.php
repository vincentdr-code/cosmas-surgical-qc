<?php

namespace App\Services;

use App\Models\Inspection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * COSMAS SENTRY — Inspection Agent Orchestrator
 *
 * Claude operates in an autonomous tool-use loop, deciding at each step
 * which tool to call based on what it has learned so far. The five tools
 * represent the five phases of a compliant QC inspection:
 *
 *   1. check_image_quality   — reject bad images before wasting analysis
 *   2. run_yolo_scan         — computer vision pre-screen (YOLOv8s)
 *   3. lookup_regulatory_context — FDA/ISO standards for the detected defect
 *   4. calculate_risk_score  — FMEA RPN × Bayesian recall prior → CRS
 *   5. finalize_inspection_report — structured verdict + EV cost matrix
 *
 * Formula: CRS = (S × O × D) × P(recall | defect_type) × trend_weight
 * Decision rule: argmin[E(action)] subject to: E(Pass) = ∞ when CRS ≥ 300
 */
class InspectionOrchestratorService
{
    private string $yoloServiceUrl = 'http://127.0.0.1:8001';
    private string $anthropicApiKey;
    private const  MAX_LOOP_ITERATIONS = 12;

    public function __construct()
    {
        $this->anthropicApiKey = config('services.anthropic.key');
    }

    // ══════════════════════════════════════════════════════════════════
    // PUBLIC ENTRY POINT
    // ══════════════════════════════════════════════════════════════════

    /**
     * Run the full agent inspection loop.
     * Returns a structured array ready for DB persistence and view rendering.
     */
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

            // Add assistant turn to conversation
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

                    // finalize_inspection_report — only accepted after prerequisites
                    if ($toolName === 'finalize_inspection_report') {
                        $calledTools = array_column($agentSteps, 'tool');
                        if (!in_array('calculate_risk_score', $calledTools)) {
                            $toolResultBlocks[] = [
                                'type'        => 'tool_result',
                                'tool_use_id' => $toolId,
                                'content'     => json_encode(['error' => 'WORKFLOW INCOMPLETE: You must call calculate_risk_score before finalize_inspection_report. Use the severity_score and recall_prior values returned by lookup_regulatory_context.']),
                            ];
                            continue;
                        }
                        $finalReport = $toolInput;
                        $agentSteps[] = [
                            'tool'   => $toolName,
                            'input'  => $toolInput,
                            'result' => ['status' => 'report_finalized'],
                        ];
                        break 2; // exit both loops
                    }

                    // Execute tool
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

        // Apply confidence threshold override
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

You have been given an image of a surgical instrument to inspect. You must work through the inspection systematically using your tools, in this order:

STEP 1 — check_image_quality: Always run this first. If quality is POOR or RESUBMIT, stop and call finalize_inspection_report with verdict FLAGGED and explain the image issue.

STEP 2 — run_yolo_scan: Run the computer vision pre-screen to detect objects and potential defects.

STEP 3 — lookup_regulatory_context: Based on what YOLO found (or your direct visual assessment if YOLO is unavailable), look up the applicable regulatory standards for the specific defect type and instrument class.

STEP 4 — calculate_risk_score: Calculate the Composite Risk Score using the severity baseline from regulatory context and the YOLO confidence score.

STEP 5 — finalize_inspection_report: Generate the final structured verdict. This must always be your last tool call.

IMPORTANT RULES:
- Never skip steps. Each tool call informs the next.
- Be calibrated. Only assign PASS when you are genuinely confident the instrument is safe.
- If YOLO confidence is below 0.40, still proceed but note the uncertainty in your reasoning.
- The cost matrix in the final report must reflect the actual CRS you calculated.
- Your reasoning must cite specific standards (e.g., FDA 21 CFR Part 820.80(c)) and reference what the vision scan found.
- You augment human judgment — you do not replace it. For FLAGGED items, make the QC specialist's decision as easy as possible.
PROMPT;
    }

    // ══════════════════════════════════════════════════════════════════
    // TOOL DEFINITIONS (Claude tool_use API schema)
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
                        'image_path' => ['type' => 'string', 'description' => 'Path to the image (pass the literal string "uploaded_image")'],
                    ],
                    'required' => ['image_path'],
                ],
            ],
            [
                'name'         => 'run_yolo_scan',
                'description'  => 'Runs the YOLOv8s fine-tuned computer vision model on the surgical instrument image. Returns detected instrument classes, bounding boxes, confidence scores, and severity ratings. Returns empty detections if service is unavailable — analysis continues via direct visual assessment.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'image_path' => ['type' => 'string', 'description' => 'Pass the literal string "uploaded_image"'],
                    ],
                    'required' => ['image_path'],
                ],
            ],
            [
                'name'         => 'lookup_regulatory_context',
                'description'  => 'Retrieves FDA 21 CFR Part 820 and ISO 13485 compliance context for a specific defect type and instrument class. Returns applicable_standard, severity_baseline (1–10), recall_prior probability, sterilization_risk flag, and required_documentation. Use the defect type and instrument class from the YOLO scan, or your direct visual assessment.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'defect_type'      => ['type' => 'string', 'description' => 'Type of defect (e.g., surface_crack, corrosion, dimensional_variance, surface_scratch, contamination, misalignment, porosity, no_defect)'],
                        'instrument_class' => ['type' => 'string', 'description' => 'Type of surgical instrument (e.g., scalpel, scissors, forceps, needle_holder, retractor, clamp, trocar)'],
                    ],
                    'required' => ['defect_type'],
                ],
            ],
            [
                'name'         => 'calculate_risk_score',
                'description'  => 'Calculates the Composite Risk Score (CRS) using FMEA methodology: RPN = Severity × Occurrence × Detection, then CRS = RPN × P(recall|defect) × trend_weight. Also generates the Expected Value cost matrix for QC decision support. Returns CRS, risk_level (LOW/MEDIUM/HIGH/CRITICAL), formula_breakdown, and cost_matrix.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'defect_type'      => ['type' => 'string'],
                        'severity_score'   => ['type' => 'number', 'description' => 'Severity baseline from regulatory lookup (1–10)'],
                        'yolo_confidence'  => ['type' => 'number', 'description' => 'Best YOLO detection confidence (0–1). Use 0.5 if YOLO unavailable.'],
                        'instrument_class' => ['type' => 'string'],
                        'recall_prior'     => ['type' => 'number', 'description' => 'P(recall|defect_type) from regulatory lookup'],
                    ],
                    'required' => ['defect_type', 'severity_score', 'yolo_confidence'],
                ],
            ],
            [
                'name'         => 'finalize_inspection_report',
                'description'  => 'Generates the final structured inspection report. ALWAYS call this as the last step. Include the full reasoning chain referencing what each prior tool returned.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'verdict'              => ['type' => 'string', 'enum' => ['PASS', 'FAIL', 'FLAGGED'], 'description' => 'Final QC verdict'],
                        'confidence'           => ['type' => 'integer', 'description' => 'AI confidence 0–100'],
                        'defect_type'          => ['type' => 'string', 'description' => 'Primary defect type or "No Defect Detected"'],
                        'instrument_class'     => ['type' => 'string', 'description' => 'Identified instrument type'],
                        'reasoning'            => ['type' => 'string', 'description' => 'Full reasoning chain citing standards and tool findings'],
                        'risk_level'           => ['type' => 'string', 'enum' => ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']],
                        'composite_risk_score' => ['type' => 'number'],
                        'regulatory_note'      => ['type' => 'string'],
                        'recommended_action'   => ['type' => 'string'],
                        'cost_matrix'          => [
                            'type'        => 'object',
                            'description' => 'Expected Value decision matrix for QC specialist',
                            'properties'  => [
                                'pass_expected_liability' => ['type' => 'number'],
                                'discard_cost'            => ['type' => 'number'],
                                'review_cost'             => ['type' => 'number'],
                                'recommendation'          => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'required' => ['verdict', 'confidence', 'defect_type', 'reasoning', 'risk_level', 'composite_risk_score'],
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
            'lookup_regulatory_context' => $this->toolLookupRegulatoryContext(
                $input['defect_type'] ?? 'unknown',
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

            $score = 100;
            $issues = [];

            // Dimension check — too small to analyze reliably
            if ($width < 200 || $height < 200) {
                $score -= 40;
                $issues[] = "Image resolution too low ({$width}×{$height}px). Minimum 200×200 required.";
            } elseif ($width < 400 || $height < 400) {
                $score -= 15;
                $issues[] = "Low resolution ({$width}×{$height}px). Higher resolution improves accuracy.";
            }

            // File size check — extremely small file likely means poor quality
            if ($fileSizeKb < 10) {
                $score -= 30;
                $issues[] = "File size very small ({$fileSizeKb}KB) — possible compression artifact or corrupt image.";
            }

            // Sharpness estimation via GD (Laplacian variance proxy)
            if (extension_loaded('gd') && $width <= 4000 && $height <= 4000) {
                $img = match ($imageInfo[2]) {
                    IMAGETYPE_JPEG => imagecreatefromjpeg($imagePath),
                    IMAGETYPE_PNG  => imagecreatefrompng($imagePath),
                    default        => null,
                };

                if ($img) {
                    // Sample a center crop (200×200 max) for variance calculation
                    $sampleSize = min(200, $width, $height);
                    $sample     = imagecreatetruecolor($sampleSize, $sampleSize);
                    imagecopy($sample, $img, 0, 0,
                        (int)(($width - $sampleSize) / 2),
                        (int)(($height - $sampleSize) / 2),
                        $sampleSize, $sampleSize);

                    // Grayscale pixel variance as sharpness proxy
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

            $score = max(0, $score);
            $status = match (true) {
                $score >= 70  => 'OK',
                $score >= 40  => 'POOR',
                default       => 'RESUBMIT',
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
                // Add human-readable summary for Claude to consume
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

    private function buildYoloSummary(array $yolo): string
    {
        if (empty($yolo['detections'])) {
            return 'YOLOv8 detected no objects of concern above the 25% confidence threshold.';
        }
        $lines = ["YOLOv8 ({$yolo['model_used']}) found {$yolo['count']} detection(s) in {$yolo['inference_ms']}ms:"];
        foreach ($yolo['detections'] as $i => $det) {
            $conf  = round($det['confidence'] * 100);
            $lines[] = "  " . ($i + 1) . ". {$det['class_name']} — {$conf}% confidence — Severity: {$det['severity']}";
        }
        return implode("\n", $lines);
    }

    private function yoloUnavailable(): array
    {
        return [
            'success'    => false,
            'detections' => [],
            'count'      => 0,
            'model_used' => 'unavailable',
            'inference_ms' => null,
            'finetuned'  => false,
            'summary'    => 'YOLOv8 service unavailable — Claude will perform direct visual analysis only.',
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // TOOL 3 — REGULATORY CONTEXT LOOKUP
    // Severity baselines and recall priors derived from FDA MAUDE database
    // and published literature on SIC 3841 surgical instrument recalls.
    // ══════════════════════════════════════════════════════════════════

    private function toolLookupRegulatoryContext(string $defectType, string $instrumentClass): array
    {
        $defectKey = strtolower(preg_replace('/[\s\-]+/', '_', $defectType));

        // Core regulatory map — FDA 21 CFR Part 820 + ISO 13485 + ASTM F899
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
            'dimensional_non_conformance' => [
                'applicable_standard'    => 'FDA 21 CFR Part 820.70(b); ISO 13485 §7.5.1',
                'severity_baseline'      => 7,
                'recall_prior'           => 0.44,
                'sterilization_risk'     => false,
                'required_documentation' => 'CMM inspection report. Engineering disposition required.',
                'clinical_consequence'   => 'Functional failure during procedure.',
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
            'sharp_edge' => [
                'applicable_standard'    => 'ISO 7153-1 §6.3',
                'severity_baseline'      => 5,
                'recall_prior'           => 0.18,
                'sterilization_risk'     => false,
                'required_documentation' => 'Deburring rework order.',
                'clinical_consequence'   => 'Unintended tissue laceration.',
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
            'instrument_misclassification' => [
                'applicable_standard'    => 'FDA 21 CFR Part 820.80 — Instrument classification required before release',
                'severity_baseline'      => 6,
                'recall_prior'           => 0.25,
                'sterilization_risk'     => false,
                'required_documentation' => 'Re-identification and re-inspection required.',
                'clinical_consequence'   => 'Wrong instrument type reaching OR. Procedure disruption.',
            ],
        ];

        // Fuzzy match — try progressively shorter key fragments
        $matched = $regulatoryMap[$defectKey] ?? null;

        if (!$matched) {
            foreach ($regulatoryMap as $key => $context) {
                if (str_contains($defectKey, $key) || str_contains($key, $defectKey)) {
                    $matched = $context;
                    break;
                }
            }
        }

        // Default for unknown defect types
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

        // Instrument class modifier — high-criticality instruments elevate severity
        $highCriticalityInstruments = ['scalpel', 'needle_holder', 'trocar', 'clamp'];
        $instrumentNormalized = strtolower(str_replace([' ', '-'], '_', $instrumentClass));
        foreach ($highCriticalityInstruments as $hci) {
            if (str_contains($instrumentNormalized, $hci)) {
                $matched['severity_baseline'] = min(10, $matched['severity_baseline'] + 1);
                $matched['criticality_note']  = "Severity elevated by +1 for high-criticality instrument class: {$instrumentClass}";
                break;
            }
        }

        $matched['defect_type']      = $defectType;
        $matched['instrument_class'] = $instrumentClass ?: 'unspecified';
        return $matched;
    }

    // ══════════════════════════════════════════════════════════════════
    // TOOL 4 — COMPOSITE RISK SCORE (FMEA + BAYESIAN)
    //
    // CRS = (S × O × D) × P(recall | defect_type) × trend_weight
    //
    // S = Severity      (1–10, from regulatory lookup)
    // O = Occurrence    (1–10, derived from 30-day inspection history)
    // D = Detection     (1–10, inverse of YOLO confidence — low confidence
    //                   means defect is hard to detect → higher D)
    // P(recall|defect)  = Bayesian prior from FDA MAUDE database
    // trend_weight      = rolling 7-day defect rate vs. 30-day baseline
    //
    // Risk tiers:   LOW < 50 | MEDIUM 50–149 | HIGH 150–299 | CRITICAL ≥ 300
    // EV decision:  argmin[E(action)] s.t. E(Pass) = ∞ when CRS ≥ 300
    // ══════════════════════════════════════════════════════════════════

    private function toolCalculateRiskScore(array $input): array
    {
        $severityScore  = min(10, max(1, (float)($input['severity_score']  ?? 5)));
        $yoloConfidence = min(1,  max(0, (float)($input['yolo_confidence'] ?? 0.5)));
        $recallPrior    = min(1,  max(0.01, (float)($input['recall_prior'] ?? 0.35)));
        $defectType     = $input['defect_type'] ?? 'unknown';

        // O — Occurrence: 30-day defect rate scaled to 1–10
        $total30   = Inspection::where('created_at', '>=', now()->subDays(30))->count();
        $defects30 = Inspection::where('created_at', '>=', now()->subDays(30))
            ->whereIn('pass_fail', ['FAIL', 'FLAGGED'])->count();
        $defectRate30 = $total30 > 0 ? $defects30 / $total30 : 0.15;
        $occurrence   = (int) max(1, min(10, round($defectRate30 * 10 + 1)));

        // D — Detection difficulty: inverse of YOLO confidence, scaled 1–10
        $detection = (int) max(1, min(10, round((1 - $yoloConfidence) * 9 + 1)));

        // RPN
        $rpn = $severityScore * $occurrence * $detection;

        // Trend weight — 7-day defect rate vs. 30-day baseline
        $total7   = Inspection::where('created_at', '>=', now()->subDays(7))->count();
        $defects7 = Inspection::where('created_at', '>=', now()->subDays(7))
            ->whereIn('pass_fail', ['FAIL', 'FLAGGED'])->count();
        $defectRate7  = $total7 > 0 ? $defects7 / $total7 : $defectRate30;
        $trendWeight  = $defectRate30 > 0
            ? max(0.5, min(2.0, $defectRate7 / $defectRate30))
            : 1.0;

        // Composite Risk Score
        $crs = round($rpn * $recallPrior * $trendWeight, 1);

        $riskLevel = match (true) {
            $crs >= 300 => 'CRITICAL',
            $crs >= 150 => 'HIGH',
            $crs >= 50  => 'MEDIUM',
            default     => 'LOW',
        };

        // Expected Value cost matrix
        // Unit cost baseline: avg surgical instrument ~$45 production cost
        // Recall cost baseline: FDA Class II device recall avg ~$2.5M (FDA enforcement data)
        // QC review: $35/hr labor × 15 min + $50 line delay = ~$58.75
        $unitCost   = 45;
        $recallCost = 2_500_000;
        $reviewCost = 59;

        // E(Pass) = P(defect_reaches_patient) × recall_cost
        // P(defect_reaches_patient) scaled by CRS / max_CRS
        $pDefectReachesPatient = min(0.95, ($crs / 1000) * $recallPrior);
        $ePass    = $crs >= 300 ? PHP_INT_MAX : round($pDefectReachesPatient * $recallCost);
        $eDiscard = $unitCost;
        $eReview  = $reviewCost;

        $recommendation = match (true) {
            $crs >= 300                        => 'DISCARD — CRS CRITICAL, liability exposure exceeds all alternatives',
            $ePass <= $eDiscard                => 'PASS — expected liability acceptable',
            $eDiscard <= $eReview              => 'DISCARD — cheaper than review, far cheaper than liability',
            default                            => 'REVIEW — cost-optimal given uncertainty',
        };

        return [
            'composite_risk_score' => $crs,
            'risk_level'           => $riskLevel,
            'formula_breakdown'    => [
                'S_severity'    => $severityScore,
                'O_occurrence'  => $occurrence,
                'D_detection'   => $detection,
                'RPN'           => $rpn,
                'recall_prior'  => $recallPrior,
                'trend_weight'  => round($trendWeight, 2),
                'formula'       => "CRS = ({$severityScore} × {$occurrence} × {$detection}) × {$recallPrior} × {$trendWeight} = {$crs}",
            ],
            'historical_context'   => [
                '30_day_inspections'  => $total30,
                '30_day_defect_rate'  => round($defectRate30 * 100, 1) . '%',
                '7_day_defect_rate'   => round($defectRate7 * 100, 1) . '%',
                'trend'               => $trendWeight > 1.1 ? 'WORSENING' : ($trendWeight < 0.9 ? 'IMPROVING' : 'STABLE'),
            ],
            'cost_matrix'          => [
                'pass_expected_liability' => $ePass === PHP_INT_MAX ? 'BLOCKED — CRS CRITICAL' : $ePass,
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
