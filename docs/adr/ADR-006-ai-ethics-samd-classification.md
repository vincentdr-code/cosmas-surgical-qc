# ADR-006: AI Ethics, SaMD Classification, and Regulatory Positioning

**Date:** 2026-05-22  
**Status:** Accepted  
**Author:** Daniel Vincent  
**Rubric:** Documentation & Communication (10pts) · Problem Definition & Relevance (10pts)

---

## Context

COSMAS SENTRY uses Claude AI (tool-use agentic loop) and YOLOv8 computer vision to inspect surgical instruments for surface defects. As an AI system operating in a medical manufacturing context, the project must explicitly address:

1. Whether the software qualifies as a Software as a Medical Device (SaMD) under the IMDRF framework
2. What FDA regulatory claims can and cannot be made
3. How the system handles AI uncertainty without hiding it from users
4. Where human judgment remains mandatory in the workflow

This ADR documents those decisions so judges, regulators, and users can evaluate them independently.

---

## Decision 1: SaMD Classification

### Framework Applied
COSMAS SENTRY's classification follows the **IMDRF SaMD Risk Framework (N12, 2014)**, the international standard adopted by the FDA for AI/ML-based software.

SaMD risk is determined by two axes:

| Axis | COSMAS SENTRY Assessment |
|------|--------------------------|
| **State of healthcare situation** | Non-serious: the system operates in a pre-surgery manufacturing QC setting, not in a clinical or critical patient-care environment |
| **Significance of information provided** | Inform clinical management: outputs are recommendations to human QC inspectors, not autonomous treatment or diagnostic decisions |

**Result: IMDRF SaMD Class I (lowest risk category)**

This classification holds because:
- No output directly drives a patient care decision
- Every verdict is explicitly a **recommendation for human review**, not a final decision
- The system operates upstream of clinical use — at the point of manufacturing, not point of care
- Surgical instruments flagged FAIL are reviewed by a human QC technician before being rejected or reworked

**If the design ever changes** such that a FAIL verdict automatically removes an instrument from a surgical tray without human confirmation, the SaMD classification escalates. This ADR must be revisited if that feature is added.

---

## Decision 2: FDA Regulatory Claims

### What We Claim

| Claim | Basis |
|-------|-------|
| "Designed to support FDA 21 CFR Part 820-compliant quality management systems" | Every inspection creates an auditable record per the QMSR framework |
| "Generates Device History Records per 21 CFR 820.184" | Each inspection stores: image path, defect type, confidence, verdict, Claude reasoning, YOLO detections, CRS score, timestamp, and user identity |
| "Supports 21 CFR Part 11 electronic record requirements" | Audit trail is immutable, timestamped, and exportable to CSV |
| "Risk scoring grounded in ISO 14971 FMEA methodology" | CRS = (S × O × D) × P(recall\|defect) × trend_weight, with Severity calibrated from FDA MAUDE recall data |

### What We Do Not Claim

| Claim | Why It Is Not Made |
|-------|-------------------|
| FDA 510(k) clearance | Cosmas is a QMS tool; the instruments inspected require 510(k), not the inspection software |
| ISO 13485 certification | A third-party audit of the full QMS is required; that has not occurred |
| Clinical validation | No controlled trial against a clinically validated gold standard has been conducted |
| Replacement of human QC judgment | Explicitly contradicted by the system design (see Decision 3) |
| "Prevents patient harm" | This is an unsubstantiated medical claim; the system mitigates risk, it does not eliminate it |

---

## Decision 3: Human-in-the-Loop as a Hard Architectural Requirement

The system is designed so that no inspection outcome results in an autonomous action affecting patient safety without human confirmation.

**Workflow:**
```
Image uploaded → AI pipeline runs → Verdict emitted
    PASS  → Instrument cleared by human QC reviewer
    FLAGGED → Mandatory human review with cost justification shown
    FAIL  → Instrument removed from line by human QC technician
    CRITICAL CRS (≥300) → Automatic FAIL override; still reviewed by human
```

The AI does not physically remove instruments. It does not automatically update downstream systems. It issues a structured recommendation that a human acts on.

**Why this matters for SaMD classification:** A system that informs a human decision remains Class I. A system that drives an autonomous action without human confirmation escalates to Class II or higher. This architectural constraint is not a limitation — it is a deliberate design choice that keeps the system in the lowest risk tier while maintaining full auditability.

---

## Decision 4: Uncertainty Transparency

The system never hides confidence scores or model limitations.

| Principle | Implementation |
|-----------|---------------|
| Confidence always visible | Results page displays confidence % prominently alongside verdict |
| Low-confidence PASSes escalate | If confidence < user-configured threshold (default 70%), a PASS automatically escalates to FLAGGED |
| Reasoning chain always shown | Full 5-step agent reasoning is collapsed but accessible on every results page |
| Model limitations documented | mAP50 = 0.764 on the defect detection model; this is disclosed in the README and represents a known baseline, not a production-validated accuracy |
| No certainty inflation | The system never rounds 73% confidence to "high confidence" in displayed text |

---

## Decision 5: Data Ethics

| Principle | Implementation |
|-----------|---------------|
| Minimum necessary data | Only image file, user ID, and inspection metadata stored; no PII beyond auth credentials |
| No third-party sharing | Inspection images and results are stored on the EC2 instance only; not transmitted to external services beyond the Claude API (which processes the image transiently) |
| Audit trail honesty | The TRANSPARENCY.md file documents all git history rewrites, duplicate commits, and known limitations |
| No misleading demos | Demo credentials are publicly documented; the system demo uses real inspection results, not staged outputs |

---

## Consequences

**Accepted trade-offs:**
- Remaining in Class I constrains what the system can autonomously do — this is intentional
- Not claiming ISO 13485 certification means the business case is aspirational, not certified — judges should evaluate the pathway, not current certification status
- Disclosing mAP50 = 0.764 rather than claiming higher accuracy builds credibility at the cost of appearing less polished than competitors who hide model limitations

**Path to higher regulatory standing:**
1. Formal V&V testing per 21 CFR 820.30 against a statistically valid instrument sample set
2. ISO 13485 gap assessment with a Notified Body
3. FDA pre-submission meeting to confirm SaMD classification and QMS alignment
4. Third-party penetration testing of the web application

---

## Related ADRs
- ADR-001: Technology stack selection (Laravel + FastAPI + YOLOv8)
- ADR-002: Two-stage YOLO pipeline architecture
- ADR-003: Claude tool-use agentic loop design
- ADR-004: Composite Risk Score (CRS) formula
- ADR-005: AWS EC2 Free Tier deployment strategy

---

*This document is part of the COSMAS SENTRY design record. It is intended to demonstrate regulatory awareness and ethical reasoning, not to constitute legal or regulatory advice.*
