# ADR-005: FDA Regulatory Positioning

**Date:** May 17, 2026  **Status:** Implemented

## Decision
Claim '21 CFR Part 820 and Part 11 alignment' - not 510(k) clearance.

## What We Claim
Designed to support FDA 21 CFR Part 820-compliant Quality Management
Systems and 21 CFR Part 11 electronic record requirements.

## What This Means in Practice
Every inspection creates an immutable audit log entry (Device History Record)
per 21 CFR 820.184. Paginated, filterable, exportable to CSV.
Confidence scores always shown. Verdicts require human sign-off.

## What We Do NOT Claim
FDA approval or clearance. Clinical validation. 510(k) equivalence.
Replacement of certified QC inspector judgment.

## Why This Framing Is Correct
The audit log IS a Device History Record per 21 CFR 820.184.
Every result cites the specific FDA/ISO standard for the defect type.
Legally accurate. Ethically honest.
