"""
Cosmas YOLOv8 Inference Service
FastAPI microservice — runs on EC2 port 8001 (localhost only)
Laravel calls this before sending to Claude for reasoning.
"""

from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.responses import JSONResponse
import tempfile
import os
import time
from pathlib import Path

app = FastAPI(
    title="Cosmas YOLOv8 Inference Service",
    description="Surgical instrument defect pre-screening via YOLOv8",
    version="1.0.0"
)

# Model paths — prefers fine-tuned, falls back to base
FINETUNED_MODEL = Path("/home/ubuntu/cosmas-yolo/models/yolov8s_defect_detector.pt")
BASE_MODEL_NAME = "yolov8n.pt"

_model = None
_model_path_used = None


def load_model():
    global _model, _model_path_used
    if _model is not None:
        return _model
    from ultralytics import YOLO
    if FINETUNED_MODEL.exists():
        _model_path_used = str(FINETUNED_MODEL)
        print(f"[Cosmas] Loading fine-tuned model: {FINETUNED_MODEL}")
    else:
        _model_path_used = BASE_MODEL_NAME
        print(f"[Cosmas] Fine-tuned not found. Loading base: {BASE_MODEL_NAME}")
    _model = YOLO(_model_path_used)
    return _model


@app.on_event("startup")
def startup_event():
    load_model()
    print("[Cosmas] YOLOv8 service ready.")


@app.get("/health")
def health_check():
    return {
        "status": "ok",
        "model": _model_path_used,
        "finetuned_available": FINETUNED_MODEL.exists(),
    }


@app.post("/detect")
async def detect(file: UploadFile = File(...)):
    allowed = {"image/jpeg", "image/png", "image/jpg", "image/webp"}
    if file.content_type not in allowed:
        raise HTTPException(status_code=415, detail=f"Unsupported: {file.content_type}")

    model = load_model()
    suffix = Path(file.filename).suffix if file.filename else ".jpg"

    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        content = await file.read()
        tmp.write(content)
        tmp_path = tmp.name

    try:
        t0 = time.time()
        results = model(tmp_path, conf=0.25, verbose=False)
        inference_ms = round((time.time() - t0) * 1000)

        detections = []
        for result in results:
            for box in result.boxes:
                class_id = int(box.cls[0])
                confidence = float(box.conf[0])
                bbox = box.xyxy[0].tolist()
                class_name = result.names[class_id]
                detections.append({
                    "class_id": class_id,
                    "class_name": class_name,
                    "display_name": _display_name(class_name),
                    "confidence": round(confidence, 4),
                    "bbox": [round(v, 1) for v in bbox],
                    "severity": _severity_hint(class_name, confidence),
                })

        return JSONResponse({
            "success": True,
            "detections": detections,
            "count": len(detections),
            "inference_ms": inference_ms,
            "model_used": _model_path_used,
            "finetuned": FINETUNED_MODEL.exists(),
        })

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Inference error: {str(e)}")
    finally:
        os.unlink(tmp_path)


@app.get("/model-status")
def model_status():
    return {
        "finetuned_path": str(FINETUNED_MODEL),
        "finetuned_exists": FINETUNED_MODEL.exists(),
        "current_model": _model_path_used,
    }


@app.post("/reload-model")
def reload_model():
    global _model, _model_path_used
    if not FINETUNED_MODEL.exists():
        raise HTTPException(status_code=404, detail=f"Not found: {FINETUNED_MODEL}")
    from ultralytics import YOLO
    _model = YOLO(str(FINETUNED_MODEL))
    _model_path_used = str(FINETUNED_MODEL)
    return {"success": True, "model_loaded": _model_path_used}


# Spanish class names from fine-tuned UR5e model → English display name + inherent risk
FINETUNED_CLASS_MAP = {
    "bisturi":         {"en": "Scalpel",          "severity": "HIGH"},
    "pinzas":          {"en": "Forceps/Tweezers",  "severity": "HIGH"},
    "tijeras_curvas":  {"en": "Curved Scissors",   "severity": "HIGH"},
    "tijeras_rectas":  {"en": "Straight Scissors",  "severity": "HIGH"},
    "mano":            {"en": "Hand (surgeon)",     "severity": "LOW"},
    "no_objeto":       {"en": "No Object",          "severity": "MONITOR"},
}

# English defect keywords for base yolov8n COCO model fallback
_HIGH_DEFECT  = {"crack", "fracture", "break", "deformation", "corrosion", "contamination"}
_MED_DEFECT   = {"scratch", "dent", "discoloration", "wear"}


def _severity_hint(class_name: str, confidence: float) -> str:
    """
    Returns severity level for Claude prompt context.
    Handles both fine-tuned model (Spanish instrument names)
    and base COCO model (English object names).
    """
    name_lower = class_name.lower()

    # Fine-tuned model: direct lookup by class name
    if name_lower in FINETUNED_CLASS_MAP:
        base_severity = FINETUNED_CLASS_MAP[name_lower]["severity"]
        # Downgrade HIGH to MEDIUM if confidence is low
        if base_severity == "HIGH" and confidence < 0.4:
            return "MEDIUM"
        return base_severity

    # Base COCO model: keyword scan for defect terms
    for term in _HIGH_DEFECT:
        if term in name_lower:
            return "HIGH"
    for term in _MED_DEFECT:
        if term in name_lower:
            return "MEDIUM" if confidence >= 0.5 else "LOW"

    # Generic fallback based on confidence alone
    if confidence >= 0.7:
        return "MEDIUM"
    elif confidence >= 0.4:
        return "LOW"
    return "MONITOR"


def _display_name(class_name: str) -> str:
    """Returns English display name for class — used in Claude prompt."""
    return FINETUNED_CLASS_MAP.get(class_name.lower(), {}).get("en", class_name)
