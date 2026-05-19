"""
Cosmas YOLOv8 Inference Service — v2
FastAPI microservice: runs on EC2 port 8001 (localhost only)
Two-stage pipeline: instrument type ID (Stage 1) + surface defect detection (Stage 2)
Laravel calls /detect before sending context to Claude for reasoning.
"""

from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.responses import JSONResponse
import tempfile
import os
import time
from pathlib import Path

app = FastAPI(
    title="Cosmas YOLOv8 Inference Service",
    description="Two-stage AI pipeline: instrument classification + surface defect detection",
    version="2.0.0"
)

# ---------------------------------------------------------------------------
# Model paths
# Stage 1 — Instrument type classifier (UR5e fine-tuned, Spanish labels → English)
# Stage 2 — Surface defect detector (cosmas_defect_v3, 5-class English)
# ---------------------------------------------------------------------------
INSTRUMENT_MODEL_PATH = Path("/home/ubuntu/cosmas-yolo/models/yolov8n_real_finetuned.pt")
DEFECT_MODEL_PATH     = Path("/home/ubuntu/cosmas-yolo/models/yolov8s_defect_v3.pt")

_instrument_model = None
_defect_model     = None


def load_models():
    global _instrument_model, _defect_model
    from ultralytics import YOLO

    if INSTRUMENT_MODEL_PATH.exists():
        print(f"[Cosmas] Loading instrument classifier: {INSTRUMENT_MODEL_PATH}")
        _instrument_model = YOLO(str(INSTRUMENT_MODEL_PATH))
    else:
        print(f"[Cosmas] WARNING: Instrument model not found at {INSTRUMENT_MODEL_PATH}")

    if DEFECT_MODEL_PATH.exists():
        print(f"[Cosmas] Loading defect detector: {DEFECT_MODEL_PATH}")
        _defect_model = YOLO(str(DEFECT_MODEL_PATH))
    else:
        print(f"[Cosmas] WARNING: Defect model not found at {DEFECT_MODEL_PATH}")

    print("[Cosmas] Model loading complete. Service ready.")


@app.on_event("startup")
def startup_event():
    load_models()


# ---------------------------------------------------------------------------
# Class maps
# ---------------------------------------------------------------------------

# Stage 1 — instrument classifier (Spanish class names from UR5e dataset)
INSTRUMENT_CLASS_MAP = {
    "bisturi":        "Scalpel",
    "pinzas":         "Forceps/Tweezers",
    "tijeras_curvas": "Curved Scissors",
    "tijeras_rectas": "Straight Scissors",
    "mano":           "Hand (surgeon)",
    "no_objeto":      "No Object",
}

# Stage 2 — defect detector (5-class English schema)
# Class indices: 0=cracks, 1=corrosion, 2=scratches, 3=porosity, 4=none
DEFECT_CLASS_MAP = {
    "cracks":    {"severity": "HIGH",    "description": "Structural crack — instrument integrity compromised"},
    "corrosion": {"severity": "HIGH",    "description": "Corrosion/oxidation — contamination risk"},
    "scratches": {"severity": "MEDIUM",  "description": "Surface scratch — inspect depth and location"},
    "porosity":  {"severity": "MEDIUM",  "description": "Surface porosity — sterility concern"},
    "none":      {"severity": "LOW",     "description": "No defect detected — passes visual QC"},
}


# ---------------------------------------------------------------------------
# Health / status endpoints
# ---------------------------------------------------------------------------

@app.get("/health")
def health_check():
    return {
        "status": "ok",
        "instrument_model_loaded": _instrument_model is not None,
        "defect_model_loaded":     _defect_model is not None,
        "pipeline": "two-stage" if (_instrument_model and _defect_model) else "partial",
    }


@app.get("/model-status")
def model_status():
    return {
        "instrument_model_path":   str(INSTRUMENT_MODEL_PATH),
        "instrument_model_exists": INSTRUMENT_MODEL_PATH.exists(),
        "instrument_model_loaded": _instrument_model is not None,
        "defect_model_path":       str(DEFECT_MODEL_PATH),
        "defect_model_exists":     DEFECT_MODEL_PATH.exists(),
        "defect_model_loaded":     _defect_model is not None,
    }


@app.post("/reload-model")
def reload_model():
    global _instrument_model, _defect_model
    from ultralytics import YOLO
    loaded = []
    if INSTRUMENT_MODEL_PATH.exists():
        _instrument_model = YOLO(str(INSTRUMENT_MODEL_PATH))
        loaded.append(str(INSTRUMENT_MODEL_PATH))
    if DEFECT_MODEL_PATH.exists():
        _defect_model = YOLO(str(DEFECT_MODEL_PATH))
        loaded.append(str(DEFECT_MODEL_PATH))
    if not loaded:
        raise HTTPException(status_code=404, detail="No model files found on disk.")
    return {"success": True, "models_loaded": loaded}


# ---------------------------------------------------------------------------
# Primary detection endpoint — two-stage pipeline
# ---------------------------------------------------------------------------

@app.post("/detect")
async def detect(file: UploadFile = File(...)):
    allowed = {"image/jpeg", "image/png", "image/jpg", "image/webp"}
    if file.content_type not in allowed:
        raise HTTPException(status_code=415, detail=f"Unsupported content type: {file.content_type}")

    if _defect_model is None and _instrument_model is None:
        raise HTTPException(status_code=503, detail="No models loaded. Check /model-status.")

    suffix  = Path(file.filename).suffix if file.filename else ".jpg"
    content = await file.read()

    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        tmp.write(content)
        tmp_path = tmp.name

    try:
        t0 = time.time()

        # ------------------------------------------------------------------
        # Stage 1 — Instrument type classification
        # ------------------------------------------------------------------
        instrument_detections = []
        identified_instrument = "Unknown instrument"

        if _instrument_model is not None:
            inst_results = _instrument_model(tmp_path, conf=0.30, verbose=False)
            for result in inst_results:
                for box in result.boxes:
                    class_id   = int(box.cls[0])
                    confidence = float(box.conf[0])
                    raw_name   = result.names[class_id]
                    en_name    = INSTRUMENT_CLASS_MAP.get(raw_name.lower(), raw_name)
                    bbox       = box.xyxy[0].tolist()
                    instrument_detections.append({
                        "class_id":     class_id,
                        "class_name":   raw_name,
                        "display_name": en_name,
                        "confidence":   round(confidence, 4),
                        "bbox":         [round(v, 1) for v in bbox],
                    })

            # Identify dominant instrument (highest-confidence, ignore non-instruments)
            useful = [d for d in instrument_detections
                      if d["display_name"] not in ("No Object", "Hand (surgeon)")]
            if useful:
                best = max(useful, key=lambda d: d["confidence"])
                identified_instrument = best["display_name"]

        # ------------------------------------------------------------------
        # Stage 2 — Surface defect detection
        # ------------------------------------------------------------------
        defect_detections = []
        overall_result  = "PASS"
        highest_severity = "LOW"

        if _defect_model is not None:
            defect_results = _defect_model(tmp_path, conf=0.25, verbose=False)
            for result in defect_results:
                for box in result.boxes:
                    class_id   = int(box.cls[0])
                    confidence = float(box.conf[0])
                    class_name = result.names[class_id]
                    bbox       = box.xyxy[0].tolist()
                    defect_info = DEFECT_CLASS_MAP.get(
                        class_name.lower(),
                        {"severity": "MONITOR", "description": class_name}
                    )
                    severity = defect_info["severity"]

                    defect_detections.append({
                        "class_id":     class_id,
                        "class_name":   class_name,
                        "display_name": class_name.capitalize(),
                        "confidence":   round(confidence, 4),
                        "bbox":         [round(v, 1) for v in bbox],
                        "severity":     severity,
                        "description":  defect_info["description"],
                    })

                    # Escalate overall result — "none" class never triggers FAIL
                    if class_name.lower() != "none":
                        if severity == "HIGH":
                            highest_severity = "HIGH"
                            overall_result   = "FAIL"
                        elif severity == "MEDIUM" and highest_severity != "HIGH":
                            highest_severity = "MEDIUM"
                            overall_result   = "FLAGGED"

        inference_ms = round((time.time() - t0) * 1000)

        # ------------------------------------------------------------------
        # Pipeline summary string — injected into Claude's reasoning prompt
        # ------------------------------------------------------------------
        active_defects = [d["class_name"] for d in defect_detections
                          if d["class_name"].lower() != "none"]
        defect_summary = (
            f"Defects detected: {', '.join(set(active_defects))}"
            if active_defects else "No defects detected"
        )
        pipeline_summary = (
            f"This is a {identified_instrument}. "
            f"{defect_summary}. "
            f"Assessment: {overall_result}."
        )

        # ------------------------------------------------------------------
        # Backward-compatible 'detections' key for InspectionOrchestratorService.
        # The orchestrator was built against the old single-model response format
        # which used: detections[], count, model_used, finetuned, inference_ms.
        # Map defect_detections → legacy format so the orchestrator needs no changes.
        # ------------------------------------------------------------------
        legacy_detections = [
            {
                "class_id":     d["class_id"],
                "class_name":   d["class_name"],
                "display_name": d["display_name"],
                "confidence":   d["confidence"],
                "bbox":         d["bbox"],
                "severity":     d["severity"],
                "bounding_box": {
                    "x1": d["bbox"][0], "y1": d["bbox"][1],
                    "x2": d["bbox"][2], "y2": d["bbox"][3],
                } if len(d["bbox"]) == 4 else None,
            }
            for d in defect_detections
            if d["class_name"].lower() != "none"
        ]

        return JSONResponse({
            "success":               True,
            "pipeline_summary":      pipeline_summary,
            "instrument":            identified_instrument,
            "instrument_detections": instrument_detections,
            "defect_detections":     defect_detections,
            "defect_count":          len(active_defects),
            "overall_result":        overall_result,
            "highest_severity":      highest_severity,
            "inference_ms":          inference_ms,
            "models_used": {
                "instrument": str(INSTRUMENT_MODEL_PATH) if _instrument_model else None,
                "defect":     str(DEFECT_MODEL_PATH)     if _defect_model     else None,
            },
            # --- Legacy keys (orchestrator compatibility) ---
            "detections":  legacy_detections,
            "count":       len(legacy_detections),
            "model_used":  str(DEFECT_MODEL_PATH) if _defect_model else "unavailable",
            "finetuned":   _defect_model is not None,
        })

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Inference error: {str(e)}")
    finally:
        os.unlink(tmp_path)
