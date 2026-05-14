# Cosmas YOLOv8 Inference Service

FastAPI microservice that runs on EC2 port 8001 (localhost only).
Pre-screens images with YOLOv8 before Claude Vision analysis.

## Endpoints
- GET  /health         — service status + model info
- POST /detect         — run inference on uploaded image
- GET  /model-status   — which model is loaded
- POST /reload-model   — hot-swap to fine-tuned weights

## Setup
```bash
conda create -n cosmas python=3.11
conda activate cosmas
pip install -r requirements.txt
sudo systemctl enable cosmas-yolo
sudo systemctl start cosmas-yolo
```
