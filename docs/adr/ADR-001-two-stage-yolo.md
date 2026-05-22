# ADR-001: Two-Stage YOLO Pipeline

**Date:** May 14-19, 2026  **Status:** Implemented

## Decision
Use two separate YOLOv8 models in sequence, not one multi-class model.

## Context
The pipeline answers two different visual questions:
1. What instrument is this? (silhouette, morphology)
2. What surface defects does it have? (local texture anomalies)
A single model learning both feature sets degrades both tasks.

## Options Considered
Option A: Single YOLOv8 - all classes (6 instruments x 5 defects)
  Requires paired labels. Severe class imbalance. Fights between feature sets.

Option B (chosen): Two models in sequence
  Stage 1: yolov8n_real_finetuned.pt - instrument classifier (6 classes)
  Stage 2: yolov8s_defect_v3.pt - defect detector (5 classes)
  Trained on 10,764 industrial surface defect images. mAP50 = 0.764.

## Consequences
Latency ~150ms vs ~80ms single model - still well under 60s requirement.
Each model retrains independently. Claude gets instrument type AND bounding boxes.
