<?php
namespace App\Services;

/**
 * Builds the Claude Vision prompt for surgical instrument QC inspection.
 *
 * Extracted from InspectionController for testability and single-responsibility.
 * Prompt engineering is treated as a first-class concern: versioned, documented,
 * and separately auditable — consistent with FDA 21 CFR Part 11 software validation.
 */
class InspectionPromptService
{
    /** Current prompt version — increment when logic changes so audit trail is clear. */
    public string $PROMPT_VERSION = '2.1';

    /**
     * Build the full analysis prompt, incorporating YOLO pre-screen context.
     */
    public function build(array $yoloResult, ?int $confidenceThreshold = 70): string
    {
        $yoloContext = $this->buildYoloContext($yoloResult);
        $threshold   = $confidenceThreshold ?? 70;

        return <<<PROMPT
You are Cosmas Sentry (prompt v{$this->PROMPT_VERSION}), an AI quality-control assistant for surgical instrument manufacturing under ISO 7153-1 and ASTM F899 standards.
{$yoloContext}
Analyze this surgical instrument image and respond ONLY with valid JSON in this exact format:
{
  "defect_type": "string (e.g. Surface Corrosion, Dimensional Non-conformance, Scratch/Burr, No Defect Detected)",
  "confidence": integer 0-100,
  "pass_fail": "PASS" or "FAIL",
  "reasoning": "2-3 sentences explaining your assessment, referencing relevant standards and computer vision findings",
  "recommended_action": "string (e.g. Release for use, Flag for supervisor review, Remove from production)",
  "regulatory_note": "Brief note on FDA/ISO compliance relevance"
}
Calibration rules:
- Confidence < {$threshold}%: lower your confidence score accordingly; human review will be triggered automatically.
- Surface defects visible to the eye → FAIL unless clearly cosmetic and non-functional.
- When uncertain between PASS and FAIL, choose FAIL and note the uncertainty.
- Do not hallucinate defects. If the image is clean, say so with high confidence.
PROMPT;
    }

    public function buildYoloContext(array $yoloResult): string
    {
        if (!($yoloResult['success'] ?? false) || empty($yoloResult['detections'])) {
            if (($yoloResult['model_used'] ?? '') === 'unavailable') {
                return 'Computer Vision Pre-screen: Service unavailable — relying on your direct image analysis.';
            }
            return 'Computer Vision Pre-screen: YOLOv8 detected NO objects of concern (0 detections above 25% confidence threshold).';
        }

        $model     = $yoloResult['model_used'];
        $finetuned = ($yoloResult['finetuned'] ?? false) ? 'fine-tuned on surgical instruments' : 'base COCO model';
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
        $lines[] = "\nHigh-severity detections should increase FAIL likelihood.";
        return implode("\n", $lines);
    }
}
