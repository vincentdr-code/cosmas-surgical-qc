# ADR-003: Claude Tool-Use Agentic Loop

**Date:** May 17, 2026  **Status:** Implemented

## Decision
Use Claude tool-use API with a 5-tool agentic loop, not a single prompt.

## Problem with single prompt (prior implementation)
1. Unstructured output - no guaranteed fields
2. YOLO scan disconnected from Claude reasoning (two unrelated API calls)
3. Regulatory citations invented by Claude, not grounded in a lookup table
4. Reasoning is a black box - judges cannot see the steps

## Chosen: 5-tool agentic loop
Claude decides which tool to call next - not scripted, it reasons:
  Tool 1: check_image_quality - resolution, sharpness via GD pixel-variance
  Tool 2: run_yolo_scan - calls FastAPI YOLO service, injects detections
  Tool 3: lookup_regulatory_context - structured lookup, not hallucination
  Tool 4: calculate_risk_score - CRS formula with live DB defect rate data
  Tool 5: finalize_inspection_report - structured verdict, stored in DB

All tool calls stored in agent_steps JSON column - visible to judges.

## Consequences
Cost ~0.02-0.04/inspection. Latency 10-45s. Fully auditable.
The agentic loop IS the AI innovation - not a chatbot wrapper.
