# ADR-004: Composite Risk Score (CRS) Formula

**Date:** May 17, 2026  **Status:** Implemented

## Formula
CRS = (S x O x D) x P(recall|defect) x trend_weight

## Variables
S (Severity, 1-10): from FDA MAUDE recall data by defect class
  cracks=9, corrosion=8, porosity=5, scratches=4, none=1
O (Occurrence, 1-10): from live 30-day defect rate in SQLite DB
  <1%=2, 1-5%=4, 5-15%=6, 15-30%=8, >30%=10
D (Detectability, 1-10): lower = harder to detect without AI
  porosity=8, cracks=7, corrosion=6, scratches=3, none=1
P(recall|defect): Bayesian prior from FDA MAUDE database
  cracks=0.73, corrosion=0.61, porosity=0.42, scratches=0.18
trend_weight: 7-day vs 30-day defect rate ratio (1.0-1.5x)

## Risk Tiers
LOW < 50 | MEDIUM 50-149 | HIGH 150-299 | CRITICAL >= 300
CRITICAL overrides verdict to FAIL regardless of confidence. Hard safety floor.

## Why FMEA
FMEA is the risk framework in FDA 21 CFR Part 820.30 and ISO 14971.
The Bayesian prior grounds S x O x D in real recall data, not guesses.
