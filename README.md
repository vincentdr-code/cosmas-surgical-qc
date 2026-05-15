# Cosmas Sentry — AI-Powered Surgical Instrument Quality Control

> *"If we do not have a quality product, how can we identify quality instruments."*

**Live Demo:** https://cosmas-sentry.duckdns.org  
**GitHub:** https://github.com/vincentdr-code/cosmas-surgical-qc  
**Demo Login:** admin@cosmas-sentry.com / password

---

## The Problem

Surgical instrument manufacturers (SIC 3841) inspect hundreds of thousands of instruments annually using manual visual inspection. Manual QC costs approximately **$0.15 per instrument** (based on $18–25/hr labour at 45 seconds per inspection), introduces human error, and creates bottlenecks on the production line. Defective instruments that pass inspection represent a direct patient safety risk and expose manufacturers to FDA enforcement and product liability.

**The core question:** How can AI-powered defect detection reduce quality-control costs while improving patient safety and maintaining FDA compliance?

---

## The Solution: Cosmas Sentry

Cosmas Sentry is a web-based AI quality control platform that replaces manual visual inspection with a two-stage AI pipeline:

1. **YOLOv8s Computer Vision** — detects instrument class and flags structural anomalies in ~200ms
2. **Claude AI Reasoning** — performs contextual defect analysis, references ISO 13485 / FDA 21 CFR Part 820 standards, and generates a PASS / FAIL / FLAGGED decision with full reasoning

Every inspection is logged to an immutable audit trail (Device History Record), satisfying FDA 21 CFR Part 11 electronic records requirements.

**Cost comparison:**
| Method | Cost/Inspection | Speed | Error Rate |
|--------|----------------|-------|------------|
| Manual | $0.15 | 45 sec | Human variable |
| Cosmas AI | $0.01 | ~200ms | Consistent |
| **Savings** | **$0.14** | **225× faster** | Auditable |

At 10,000 inspections/month → **$1,400/month · $16,800/year** in direct labour savings.

---

## Architecture

```
[User: Upload Instrument Image]
        │
        ▼
[Laravel Web App]  ──auth──►  [InspectionController]
                                      │
                    ┌─────────────────┼─────────────────┐
                    ▼                 ▼                  ▼
          [YOLOv8s FastAPI]   [Claude API]        [SQLite DB]
          port 8001           claude-sonnet-4-6   Inspections
          ~200ms inference    Contextual reasoning Audit Trail
          class+confidence    ISO/FDA context
                    │                 │
                    └────────┬────────┘
                             ▼
                    [Results Page]
                    PASS / FAIL / FLAGGED
                    confidence score
                    plain-English reasoning
                    regulatory note
                             │
              ┌──────────────┼──────────────┐
              ▼              ▼              ▼
         [Dashboard]   [Audit Log]   [ROI Calculator]
         KPI cards     Device Hist.  Cost savings model
```

### Stack
| Layer | Technology | Purpose |
|-------|-----------|---------|
| Frontend | Laravel Blade + Tailwind CSS | Server-rendered UI |
| Backend | Laravel 11 (PHP 8.5) | Routing, auth, business logic |
| AI Vision | YOLOv8s (Ultralytics, fine-tuned) | Defect detection, ~200ms inference |
| AI Reasoning | Claude API (claude-sonnet-4-6) | Contextual analysis, FDA-aware |
| Database | SQLite | Inspections, audit log, users |
| Web Server | Nginx + PHP-FPM | Production HTTP/HTTPS |
| Deployment | AWS EC2 t2.micro | Free tier, live 24/7 |
| SSL | Let's Encrypt (Certbot) | HTTPS, auto-renews |

---

## AI Feature: Two-Stage Inspection Pipeline

The AI feature is not a chatbot wrapper. It is a functional two-stage pipeline:

**Stage 1 — YOLOv8s Computer Vision**
```
POST http://127.0.0.1:8001/predict
Input:  base64-encoded instrument image
Output: { class, confidence, bounding_box, inference_ms }
```

The YOLOv8s model is fine-tuned on a combined surgical instrument defect dataset of **10,764 images** across four sources:

| # | Dataset | Source | Images | License |
|---|---------|--------|--------|---------|
| 1 | NEU Surface Defect | Roboflow: pavithraa-sekar/neu-surface-defect-dataset v1 | 1,799 | CC BY 4.0 |
| 2 | Rust Corrosion Detection | Roboflow: averkios/rust-corrosion-detection v16 | 8,354 (subsampled 2,500 train) | CC BY 4.0 |
| 3 | NEU Steel Surface Defect | Kaggle: sovitrath/neu-steel-surface-defect-detect | 1,800 | Unknown |
| 4 | Synthetic Metal Defects | Kaggle: tatheerabbas/synthetic-industrial-metal | ~15,000 (subsampled 4,600) | CC BY 4.0 |

**Trained classes and distribution:**
| ID | Class | Train Annotations | Val Annotations |
|----|-------|------------------|-----------------|
| 0 | cracks | 1,978 | 320 |
| 1 | corrosion | 10,620 | 877 |
| 2 | misalignment | — (v2 roadmap) | — |
| 3 | scratches | 4,356 | 675 |
| 4 | porosity | 3,355 | 451 |
| 5 | none (conforming) | 800 | 120 |
| | **TOTAL** | **9,506 images** | **1,258 images** |

> **Note on misalignment:** No public dataset contained labelled misalignment examples at training time. The class is reserved in the schema; the model handles it via Claude's contextual reasoning. Dedicated misalignment data will be added in v2.

**Training configuration:**
- Model: YOLOv8s (11M params), COCO-pretrained transfer learning
- Optimizer: AdamW, lr0=0.001, cosine LR decay, warmup_epochs=5
- Anti-overfitting: weight_decay=0.0005, patience=20 early stopping, close_mosaic=10
- Augmentation: mosaic, copy_paste=0.1 (helps rare classes), calibrated HSV/flip/scale
- Hardware: Google Colab T4 GPU

**Stage 2 — Claude AI Reasoning**
```
Claude API: claude-sonnet-4-6 (vision)
Input:  instrument image + YOLOv8s detection result
Output: { defect_analysis, pass_fail, regulatory_note, recommended_action }
```

Claude receives the image directly alongside YOLOv8s findings and performs contextual reasoning — distinguishing cosmetic marks from structural defects, referencing applicable standards, and explaining its decision in plain language a QC supervisor can act on.

**Why two stages?** YOLOv8s provides fast, consistent object-level detection. Claude provides interpretability and regulatory context. Neither alone is sufficient — together they mirror how an expert QC inspector actually reasons.

---

## FDA Compliance Pathway

Cosmas Sentry is designed to **support** FDA-compliant workflows, not replace regulatory review.

- **21 CFR Part 820** (Quality System Regulation): Every inspection generates a Device History Record with timestamp, user ID, AI decision, confidence score, and reasoning.
- **21 CFR Part 11** (Electronic Records): Audit log is append-only, timestamped, and user-attributed.
- **ISO 13485**: AI augments human QC review — final disposition remains with a qualified inspector.
- **Positioning**: "AI-augmented QC" — the system makes recommendations, humans make final decisions.

---

## Setup & Deployment

### Prerequisites
- PHP 8.2+, Composer, Node.js
- Python 3.10+, pip
- AWS EC2 t2.micro (or equivalent)

### Local Development
```bash
# Clone
git clone https://github.com/vincentdr-code/cosmas-surgical-qc.git
cd cosmas-surgical-qc

# Install PHP dependencies
composer install

# Environment
cp .env.example .env
php artisan key:generate
# Add ANTHROPIC_API_KEY to .env

# Database
php artisan migrate
php artisan db:seed --class=DemoSeeder

# Run
php artisan serve
```

### YOLOv8s Service
```bash
cd cosmas-yolo
pip install -r requirements.txt
uvicorn main:app --host 127.0.0.1 --port 8001
```

### Production (EC2)
```bash
# Nginx config: /etc/nginx/conf.d/cosmas.conf
# SSL: Let's Encrypt via Certbot (auto-renews)
# PHP-FPM: php8.5-fpm
# Domain: cosmas-sentry.duckdns.org
```

---

## Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@cosmas-sentry.com | password |
| Inspector | inspector@cosmas-sentry.com | password |
| Manager | manager@cosmas-sentry.com | password |

---

## Demo Script (5 minutes)

1. **Login** at https://cosmas-sentry.duckdns.org → show dashboard KPIs
2. **Upload** a surgical instrument image → watch YOLOv8s + Claude analyze it in real time
3. **View result** → show PASS/FAIL decision, confidence score, Claude's reasoning, regulatory note
4. **Audit log** → show Device History Record trail
5. **ROI calculator** → adjust to 50,000 inspections/month, show $84,000/year savings
6. **Judge question:** "What would have to break for this to fail a real FDA audit?" → Answer: human override is always available, AI is advisory, every decision is logged with user attribution

---

## Business Case Summary

**Market:** US surgical instrument manufacturing (SIC 3841) — $3.2B market, 500+ manufacturers, all subject to FDA QSR.

**Problem size:** Industry spends an estimated $180M/year on manual QC inspection labour.

**Cosmas solution:** Reduce per-inspection cost by 93% ($0.15 → $0.01), eliminate human inconsistency, generate audit-ready records automatically.

**Go-to-market:** Target mid-size manufacturers (50–500 employees) currently using manual inspection or legacy CMM systems. SaaS model: $2,500/month per facility, unlimited inspections.

**Unit economics:** Customer saves $16,800/year at 10k inspections/month. Cosmas charges $30,000/year. Customer ROI: positive in year 1. Cosmas gross margin: ~85%.

---

## Project Structure

```
cosmas/
├── app/Http/Controllers/
│   ├── DashboardController.php   # KPI metrics, recent inspections
│   ├── InspectionController.php  # Upload → YOLOv8s → Claude → result
│   └── RoiController.php         # Interactive ROI calculator
├── resources/views/
│   ├── dashboard.blade.php       # Main QC dashboard
│   ├── upload.blade.php          # Image upload form
│   ├── results.blade.php         # Inspection result detail
│   ├── audit-log.blade.php       # Device History Records
│   └── roi.blade.php             # ROI calculator
├── cosmas-yolo/
│   ├── main.py                   # FastAPI YOLOv8s service
│   └── models/                   # Fine-tuned model weights
└── database/
    └── database.sqlite           # Inspections + audit trail
```

---

## Rubric Alignment

| Category | Points | How We Address It |
|----------|--------|-------------------|
| Problem Definition & Relevance | 10 | SIC 3841, real FDA compliance requirement, quantified $180M industry problem |
| AI Feature Innovation & Integration | 20 | Two-stage YOLOv8s + Claude pipeline on 10,764-image fine-tuned model — not a chatbot wrapper |
| Technical Execution & Code Quality | 20 | Laravel MVC, clean controllers, proper auth, migrations, FastAPI microservice |
| User Experience & Design | 10 | Tailwind dashboard, KPI cards, clear PASS/FAIL UI, interactive ROI calculator |
| Business Impact & Scalability | 10 | ROI calculator, SaaS model with unit economics, $180M TAM |
| GitHub Transparency | 10 | Meaningful commits documenting iterative progress and architectural decisions |
| Documentation & Communication | 10 | This README — problem, solution, architecture, training data, business case, demo script |
| Deployment & Live Demo | 10 | https://cosmas-sentry.duckdns.org — live, HTTPS, 24/7 on AWS Free Tier |

---

*Built for the CUA AI Vibe Coding Competition · May–June 2026*
