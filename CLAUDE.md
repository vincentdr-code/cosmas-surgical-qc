# DAMIAN by Cosmas — CLAUDE.md

> This file is the authoritative guide for any AI agent (Claude or otherwise) working on this codebase.
> Read this before touching anything. It overrides assumptions.

---

## What This Project Is

**DAMIAN** (by Cosmas) is an AI-powered defect detection system for surgical instrument manufacturing (SIC 3841).

It answers: *How can AI-powered defect detection reduce QC costs while improving patient safety and maintaining FDA compliance?*

Built for the **CUA AI Vibe Coding Competition** (deadline June 13, 2026). Goal: 100/100 points.

Live URL: **https://cosmas-damian.duckdns.org**
GitHub: **https://github.com/vincentdr-code/cosmas-surgical-qc**
Marketing site: **https://cosmas-website.vercel.app**

---

## Architecture

```
EC2 t2.micro (18.216.244.44)
  nginx (443/80) -> React SPA (Vite dist)
                -> Laravel 11 PHP-FPM (PHP 8.5)
                   /home/ubuntu/cosmas/

  FastAPI + YOLOv8 (localhost:8001, uvicorn)
    /home/ubuntu/cosmas-yolo/main.py
    Models:
      yolov8n_real_finetuned.pt  (instrument type, 6 classes)
      yolov8s_defect_v3.pt       (surface defects, 5 classes, mAP50=0.764)

  SQLite: /home/ubuntu/cosmas/database/database.sqlite
```

**Two-stage YOLO pipeline:**
1. Stage 1 — instrument classifier: 6 classes (scalpel, forceps, scissors, etc.), Spanish to English mapped
2. Stage 2 — defect detector: 5 classes (cracks, corrosion, scratches, porosity, none), mAP50=0.764, 77 epochs, 10,764 training images

**5-tool Claude agentic loop (the core AI innovation):**
```
Tool 1: check_image_quality       -> resolution + sharpness via pixel variance
Tool 2: run_yolo_scan             -> calls FastAPI, injects YOLO detections
Tool 3: lookup_regulatory_context -> structured lookup (not hallucination)
Tool 4: calculate_risk_score      -> CRS = S*O*D * P(recall|defect) * trend_weight
Tool 5: finalize_inspection_report -> structured verdict stored in DB
```
All tool calls stored in `agent_steps` JSON column — fully auditable. This is NOT a chatbot wrapper.

---

## Repository Structure (EC2)

```
/home/ubuntu/cosmas/              <- Laravel 11 app
  app/Http/Controllers/           <- InspectionController has the agentic loop
  resources/views/                <- Blade templates (dashboard, audit-log, etc.)
  database/database.sqlite        <- Production DB
  .env                            <- ANTHROPIC_API_KEY lives here only

/home/ubuntu/cosmas-yolo/         <- FastAPI + YOLOv8 service
  main.py                         <- Two-stage pipeline endpoint
  models/
    yolov8n_real_finetuned.pt
    yolov8s_defect_v3.pt

/home/ubuntu/cosmas-react/        <- React SPA (Vite)
  src/App.jsx                     <- Single-file, no router
  dist/                           <- Built output served by nginx at /
```

---

## Key Commands

### EC2 Access
```bash
ssh -i cosmas-keypair.pem ubuntu@18.216.244.44
```

### Services
```bash
# Status
sudo systemctl status cosmas-yolo
sudo systemctl status php8.5-fpm
sudo systemctl status nginx

# Restart after code changes
sudo systemctl restart cosmas-yolo
sudo systemctl restart php8.5-fpm
sudo systemctl reload nginx

# Logs
sudo journalctl -u cosmas-yolo -f
sudo tail -f /var/log/nginx/error.log
sudo tail -f /home/ubuntu/cosmas/storage/logs/laravel.log
```

### Laravel
```bash
cd /home/ubuntu/cosmas
php artisan migrate
php artisan migrate:status
php artisan cache:clear && php artisan config:clear
php artisan route:list
```

### React SPA
```bash
cd /home/ubuntu/cosmas-react
npm run build       # rebuilds dist/ — nginx serves it automatically
```

### Git sync (after local push)
```bash
cd /home/ubuntu/cosmas
git pull origin main
git log --oneline -5
```

### Local Deployment Scripts (PowerShell — run from Cosmas folder)
```powershell
.\push_from_local.ps1            # Push doc/ADR commits via HTTPS clone (bypasses EC2)
.\deploy_website_audit_fixes.ps1 # Push cosmas-website -> Vercel auto-deploys
```

---

## Database Schema (inspections table — key columns)

```
id, user_id, image_path
defect_type          -- cracks | corrosion | scratches | porosity | none
instrument_class     -- scalpel | forceps | scissors | ...
confidence           -- 0.0-1.0
pass_fail            -- PASS | FLAGGED | FAIL
claude_reasoning     -- 2700-3500 char agentic reasoning text
bounding_box         -- JSON array of YOLO bounding boxes
yolo_detections      -- raw YOLO output JSON
yolo_count           -- number of detections
inference_ms         -- pipeline latency
composite_risk_score -- CRS formula result (numeric)
risk_level           -- LOW | MEDIUM | HIGH | CRITICAL
agent_steps          -- JSON: all 5 tool calls + responses (the audit trail)
cost_matrix          -- per-inspection cost breakdown
created_at, updated_at
```

---

## Composite Risk Score (CRS) Formula

```
CRS = (S * O * D) * P(recall|defect) * trend_weight

S (Severity 1-10):    cracks=9, corrosion=8, porosity=5, scratches=4, none=1
O (Occurrence 1-10):  from live 30-day defect rate in SQLite
D (Detectability):    porosity=8, cracks=7, corrosion=6, scratches=3, none=1
P(recall|defect):     cracks=0.73, corrosion=0.61, porosity=0.42, scratches=0.18
trend_weight:         7-day vs 30-day defect rate ratio (1.0-1.5x)

Risk tiers: LOW<50 | MEDIUM 50-149 | HIGH 150-299 | CRITICAL>=300
CRITICAL always overrides verdict to FAIL. Hard safety floor.
```

Grounded in FDA MAUDE recall data and FMEA (FDA 21 CFR 820.30, ISO 14971).

---

## Regulatory Positioning

**What we claim:** Designed to support FDA 21 CFR Part 820-compliant QMS and 21 CFR Part 11 electronic record requirements.

**What we do NOT claim:** FDA approval, 510(k) clearance, clinical validation, replacement of human QC judgment.

The audit log is a Device History Record per 21 CFR 820.184. Every result cites the specific FDA/ISO standard for the defect type. The system augments QC judgment — it does not replace it.

---

## Coding Conventions

### Commit Format (non-negotiable)
```
type(scope): short description

Body explaining what changed, why, and what it fixes or enables.

Rubric: Category Name (Xpts)
```

Types: feat, fix, docs, refactor, test, chore, ci
Scopes: api, yolo, dashboard, audit-log, auth, adr, readme, deploy

Bad:  "fix bugs" / "update files" / "changes"
Good: "fix(yolo): normalize confidence to 0-1 scale — was returning 0-100, breaking CRS threshold"

### PHP / Laravel
- No raw SQL — use Eloquent or Query Builder
- All inspection logic stays in InspectionController — do not scatter
- Never log ANTHROPIC_API_KEY or user image data
- Migrations are append-only — never edit a migration that has already run

### Python / FastAPI
- Two-stage pipeline in main.py — do not split unless endpoint count exceeds 5
- Model loading at startup (singleton) — never reload on each request
- Return JSON with: instrument_class, defect_classes, confidence_scores, bounding_boxes, pipeline_summary, inference_ms

### React / Vite
- Single App.jsx file, no router
- Tailwind utility classes only — no custom CSS files
- No localStorage — all state in React useState

---

## Security Rules (hard constraints)

- .env is NEVER committed — lives on EC2 only
- cosmas-keypair.pem is NEVER committed
- ANTHROPIC_API_KEY is in .env on EC2 only — never in code, never in logs
- No PII in public URLs
- .env.example documents all required keys with placeholder values

---

## Demo Test Checklist

Run before every judge demo:
1. Upload test_crack.jpg -> expect FAIL, CRS >= 150, cracks detected
2. Upload test_corrosion.jpg -> expect FAIL or FLAGGED, corrosion detected
3. Upload test_clean.jpg -> expect PASS, none detected
4. Verify agent_steps column populated on results page
5. Verify audit log shows new entry, CSV export works
6. Check dashboard KPI tiles updated (total inspections, defect rate, cost savings)
7. Resize to 375px — confirm mobile layout intact

---

## Video Review (claude-video / /watch)

Upload any video directly or use /watch <url> to have Claude analyze it:

- Demo walkthroughs: "Review this demo recording — identify where a judge might get confused"
- Training videos: "Watch this QC inspection training video and extract the defect classification criteria"
- Competition references: "Watch this winning demo and tell me what we are missing"
- Competitor analysis: "Watch this product demo and compare their AI feature to our agentic loop"

Frame budget scales automatically (max 100 frames at 2fps). Use --start/--end for focused clips.
Requires yt-dlp + ffmpeg + Whisper (Groq preferred for speed).

---

## Rubric Reference (100 pts)

| Category               | Pts | Status  | Key Evidence                           |
|------------------------|-----|---------|----------------------------------------|
| Problem Definition     | 10  | Strong  | FDA context, real market, SIC 3841     |
| AI Feature Innovation  | 20  | Strong  | 5-tool agentic loop, two-stage YOLO    |
| Technical Execution    | 20  | Good    | Two models, orchestrator, CI tests     |
| UX & Design            | 10  | Good    | React SPA terminal + Laravel blade     |
| Business Impact        | 10  | Good    | ROI calc, cost KPI, FDA audit trail    |
| GitHub Transparency    | 10  | ~8/10   | 65+ commits, ADRs, TRANSPARENCY.md     |
| Documentation          | 10  | ~8/10   | README rewritten, ADR-001 through 005  |
| Deployment             | 10  | Strong  | Live HTTPS, EC2, systemd, nginx SSL    |

---

## Known Issues (as of May 22, 2026)

1. Model returns "No Defect Detected" on most generic images — need controlled test set with reliable FAIL/FLAGGED images for demo
2. Duplicate commits in history (f7544d3 / a4b2ad0) — cosmetic, documented in TRANSPARENCY.md
3. EC2 SSH was unreachable May 21 — sync via `git pull origin main` when back

---

## Competition Deadline

**June 13, 2026 at midnight.**

Phase plan:
- May 20-25:  Demo hardening — test images, results page polish, README
- May 26-June 5:  Documentation, rubric audit, final GitHub commits
- June 6-12:  Demo video, architecture diagram, submit prep
- June 13:  Submit
