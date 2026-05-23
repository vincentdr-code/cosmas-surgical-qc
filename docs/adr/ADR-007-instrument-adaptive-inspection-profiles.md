# ADR-007: Instrument-Adaptive Inspection Profiles

**Status:** Accepted  
**Date:** 2026-05-22  
**Author:** Daniel Vincent  
**Rubric:** AI Feature Innovation (20pts) | Technical Execution (20pts) | Problem Definition (10pts)

---

## Context

COSMAS SENTRY uses a two-stage YOLO pipeline:

- **Stage 1** — Instrument classifier (`yolov8n_real_finetuned.pt`, 6 classes): identifies *what kind* of surgical instrument is in the image
- **Stage 2** — Defect detector (`yolov8s_defect_v3.pt`, 5 classes, mAP50=0.764): identifies *what surface defects* are present

Prior to this ADR, Stage 1 output was passed to Claude as a label in the context, and referenced in a crude lookup inside `toolLookupRegulatoryContext()` that added a flat +1 to severity for high-criticality instruments. Stage 2 verdict interpretation did not change based on Stage 1 output.

**The problem:** A crack on a scalpel blade and a scratch on a retractor are not equivalently dangerous defects. The same YOLO detection confidence should not produce the same risk tier on instruments with fundamentally different clinical roles.

- A crack on a scalpel risks intraoperative blade fragmentation and fragment retention in the surgical site — catastrophic.
- A crack on a retractor is serious but does not carry the same immediate fragmentation risk during use.
- A scratch on a scalpel compromises blade edge precision and incision cleanliness.
- A scratch on a forceps handle is largely cosmetic.

If Stage 1 does not materially drive Stage 2 interpretation, the two-stage pipeline is not an integrated system — it is two disconnected models with a label passed between them. That is not true AI innovation.

---

## Decision

Implement `INSTRUMENT_PROFILES` as a private constant in `InspectionOrchestratorService`, encoding per-instrument clinical context that is applied inside `toolCalculateRiskScore()` before any CRS computation.

### Structure

Each profile contains:

| Field | Type | Purpose |
|-------|------|---------|
| `confidence_threshold` | float (0.0–1.0) | Minimum YOLO defect confidence before auto-escalation to FLAGGED. Stricter for cutting instruments; more lenient for structural retractors. |
| `severity_multipliers` | array[defect → float] | Applied to `S` (Severity, 1–10) in the FMEA RPN before computing CRS. Grounds severity in clinical consequence for this instrument type. |
| `critical_defects` | array[string] | Defect types that always override verdict to FAIL regardless of CRS tier. Hard safety floor. |
| `iso_standard` | string | Applicable ISO 7153-x standard for this instrument class. Surfaced in Claude's reasoning chain and regulatory lookup. |
| `clinical_role` | string | One-line description used to enrich Claude's reasoning. Makes the agentic loop's explanation interpretable to a QC engineer, not just a developer. |

### Modified CRS Formula

```
CRS = (S × instrument_multiplier × O × D) × P(recall|defect) × trend_weight
```

Previously: `CRS = (S × O × D) × P(recall|defect) × trend_weight`

The addition of `instrument_multiplier` makes the Severity component instrument-aware. The same surface crack produces different CRS values depending on which instrument class Stage 1 identified.

### Clinical Profile Table

| Instrument | Confidence Threshold | Crack | Corrosion | Porosity | Scratch | Critical Defects |
|------------|---------------------|-------|-----------|----------|---------|-----------------|
| Scalpel | 0.45 | ×1.5 | ×1.4 | ×1.4 | ×1.3 | crack, corrosion, porosity |
| Forceps | 0.55 | ×1.3 | ×1.2 | ×1.1 | ×0.9 | crack, corrosion |
| Scissors | 0.50 | ×1.4 | ×1.2 | ×1.2 | ×1.0 | crack, corrosion |
| Needle Holder | 0.55 | ×1.3 | ×1.1 | ×1.1 | ×0.8 | crack |
| Clamp | 0.60 | ×1.2 | ×1.0 | ×1.0 | ×0.7 | crack |
| Retractor | 0.65 | ×1.2 | ×0.9 | ×0.9 | ×0.6 | (none) |

**Rationale for thresholds:** Cutting instruments (scalpel, scissors) have tighter confidence thresholds because false negatives on defect detection are more dangerous than false positives. Structural instruments (retractor, clamp) accept higher detection confidence before triggering escalation — their margin for cosmetic defects is wider.

### Critical Defect Override

When Claude calls `calculate_risk_score`, the tool checks `critical_defects` for the detected instrument. If the detected defect type appears in that list, the return value includes `critical_defect_override: true`, which `finalize_inspection_report` treats as an unconditional FAIL — regardless of CRS tier and regardless of confidence score.

This is a hard safety floor, not a soft recommendation.

---

## Consequences

### Positive

1. **Stage 1 now drives Stage 2 interpretation materially.** The two-stage pipeline is a genuine integrated system. A judge reviewing the `agent_steps` JSON column will see `instrument_profile_applied: true` and `severity_multiplier: 1.5` in the tool response — explicit evidence that Stage 1 influenced the risk calculation.

2. **CRS variance by instrument class is demonstrable in the demo.** The same corrosion detection will produce CRITICAL on a scalpel (CRS ≥ 300) and MEDIUM on a retractor (CRS 50–149). This is the kind of nuanced, clinically-grounded behavior that distinguishes a proof-of-concept from a research prototype.

3. **The system prompt now instructs Claude to request focused rescans autonomously.** When `instrument_class_confidence < 0.55` (or below the profile threshold), Claude calls `request_focused_rescan` before proceeding. This is genuine multi-turn agentic decision-making — not scripted branching.

4. **Every profile decision is traceable to a published standard.** ISO 7153-1 (scalpels), ISO 7153-2 (scissors), ISO 7153-11 (forceps), FDA MAUDE recall database. ADR-006 covers the SaMD Class I positioning.

### Negative / Trade-offs

1. **Profiles encode clinical judgment that has not been validated against certified QC inspectors.** The multiplier values (e.g., scalpel crack = ×1.5) are grounded in the literature and FMEA principles, but are not the output of a calibration study. They should be treated as structured expert estimates, not validated parameters. This is documented in TRANSPARENCY.md.

2. **The "unknown" instrument fallback uses all multipliers = 1.0.** If Stage 1 returns a class not in `INSTRUMENT_PROFILES`, the system applies default severity and logs the unknown class. This is safe but means the adaptive benefit is lost for unrecognized instruments.

3. **Focused rescan uses GD image cropping, not a true sub-image re-inference.** The crop-and-re-run approach gives a better view of the region in question but does not change the YOLO model's context awareness. A production system would use a higher-resolution camera with targeted capture rather than software cropping.

---

## Alternatives Considered

### Alternative A: Static severity table by defect type only (status quo before this ADR)
**Rejected.** Does not use Stage 1 output. Stage 1 becomes a label generator rather than a pipeline component. Two-stage claim is not substantiated.

### Alternative B: Train a single combined model on instrument+defect pairs
**Rejected.** Not feasible within competition timeline. Requires a labeled dataset of specific instrument types with specific defect types — which does not exist as an open corpus. The two-stage approach achieves the same conceptual goal using available models.

### Alternative C: Hardcode severity by instrument×defect in a lookup matrix
**Considered but modified.** A pure lookup matrix is valid but gives Claude no profile context. `INSTRUMENT_PROFILES` exposes `clinical_role` and `iso_standard` to Claude's reasoning chain, which enriches the explanatory output that judges evaluate in the demo.

---

## References

- ISO 7153-1:2016 — Surgical instruments — Metallic materials — Part 1: Stainless steel
- ISO 7153-2 — Surgical instruments — Metallic materials — Part 2: Corrosion resistance
- ISO 7153-11 — Surgical instruments — Metallic materials — Part 11: Forceps
- ISO 14971:2019 — Medical devices — Application of risk management
- FDA MAUDE Database — Surgical instrument failure reports (SIC 3841)
- ECRI Institute Hazard Reports — Retained surgical instrument fragments
- ASTM F899-12a — Standard specification for wrought stainless steel for surgical instruments
- ADR-006 — AI Ethics and SaMD Classification (IMDRF Non-Serious × Inform positioning)
- TRANSPARENCY.md — Known limitations, V&V status, scope decisions
