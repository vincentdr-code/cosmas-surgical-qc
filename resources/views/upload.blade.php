<x-app-layout>
    <x-slot name="header">
        <span style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; color:#8A9BAE; letter-spacing:0.12em; text-transform:uppercase;">
            [ NEW INSPECTION ]
        </span>
        <a href="{{ route('dashboard') }}"
           style="font-family:'JetBrains Mono',monospace; font-size:11px; color:#8A9BAE; text-decoration:none; letter-spacing:0.08em;">
            ← DASHBOARD
        </a>
    </x-slot>

    {{-- ─── UPLOAD FORM ───────────────────────────────────────────── --}}
    <div id="uploadSection" style="max-width:800px; margin:0 auto;">
        @if(session('error'))
        <div style="background:rgba(231,76,60,0.12); border:1px solid rgba(231,76,60,0.4); border-left:3px solid #E74C3C; padding:14px 20px; margin-bottom:20px; color:#E8EDF2; font-size:12px; font-family:'JetBrains Mono',monospace;">
            ⚠ {{ session('error') }}
        </div>
        @endif
        @if($errors->any())
        <div style="background:rgba(231,76,60,0.12); border:1px solid rgba(231,76,60,0.4); border-left:3px solid #E74C3C; padding:14px 20px; margin-bottom:20px; color:#E8EDF2; font-size:12px; font-family:'JetBrains Mono',monospace;">
            @foreach($errors->all() as $error)
                <div>⚠ {{ $error }}</div>
            @endforeach
        </div>
        @endif

        <div class="tac-card">
            <div class="tac-label" style="margin-bottom:24px;">[ SUBMIT INSPECTION ]</div>
            <form id="inspectForm" enctype="multipart/form-data">
                @csrf
                {{-- Drop Zone --}}
                <div class="drop-zone" id="dropZone" onclick="document.getElementById('imageInput').click();">
                    <div id="dropText">
                        <div style="font-size:32px; color:rgba(201,150,62,0.3); margin-bottom:12px;">⬆</div>
                        <div style="color:#8A9BAE; font-size:12px; margin-bottom:4px;">DROP IMAGE HERE OR CLICK TO BROWSE</div>
                        <div style="color:rgba(138,155,174,0.6); font-size:10px; letter-spacing:0.08em;">JPG · PNG · WEBP · MAX 20MB</div>
                    </div>
                    <div id="previewBox" style="display:none; margin-top:12px;">
                        <img id="previewImg" style="max-height:240px; max-width:100%; object-fit:contain; border:1px solid rgba(201,150,62,0.2);" />
                        <div id="previewName" style="color:#C9963E; font-size:11px; margin-top:8px;"></div>
                    </div>
                </div>
                <input type="file" id="imageInput" name="image" accept="image/*" style="display:none;" onchange="handleFile(this.files[0])">

                {{-- Metadata Row --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:20px;">
                    <div>
                        <label style="display:block; font-size:10px; letter-spacing:0.1em; color:#8A9BAE; font-weight:600; margin-bottom:6px; text-transform:uppercase; font-family:'JetBrains Mono',monospace;">INSTRUMENT ID</label>
                        <input type="text" name="instrument_id" placeholder="e.g. SCALPEL-42A"
                               value="{{ old('instrument_id') }}"
                               style="width:100%; padding:10px 14px; font-family:'JetBrains Mono',monospace; font-size:11px;">
                    </div>
                    <div>
                        <label style="display:block; font-size:10px; letter-spacing:0.1em; color:#8A9BAE; font-weight:600; margin-bottom:6px; text-transform:uppercase; font-family:'JetBrains Mono',monospace;">OPERATOR ID</label>
                        <input type="text" name="operator_id" placeholder="e.g. OPS-007"
                               value="{{ old('operator_id') }}"
                               style="width:100%; padding:10px 14px; font-family:'JetBrains Mono',monospace; font-size:11px;">
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <label style="display:block; font-size:10px; letter-spacing:0.1em; color:#8A9BAE; font-weight:600; margin-bottom:6px; text-transform:uppercase; font-family:'JetBrains Mono',monospace;">NOTES (optional)</label>
                    <textarea name="notes" rows="3" placeholder="Production line, batch number, shift info..."
                              style="width:100%; padding:10px 14px; resize:vertical; font-family:'JetBrains Mono',monospace; font-size:11px;">{{ old('notes') }}</textarea>
                </div>

                {{-- Inspector framing --}}
                <div style="margin-top:20px; padding:12px 16px; background:rgba(201,150,62,0.05); border:1px solid rgba(201,150,62,0.15); border-left:3px solid rgba(201,150,62,0.5); font-size:10px; color:rgba(138,155,174,0.75); line-height:1.6; letter-spacing:0.04em; font-family:'JetBrains Mono',monospace;">
                    DAMIAN surfaces defect evidence and regulatory context. <strong style="color:rgba(201,150,62,0.85);">You make the final call.</strong>
                </div>

                {{-- Submit --}}
                <div style="margin-top:14px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="font-size:10px; color:rgba(138,155,174,0.6); letter-spacing:0.08em; font-family:'JetBrains Mono',monospace;">
                        YOLOv8 → CLAUDE VISION · FDA 21 CFR PART 820
                    </div>
                    <button type="submit" id="submitBtn"
                        style="background:#C9963E; color:#060F1E; font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; letter-spacing:0.1em; padding:10px 28px; border:none; cursor:pointer; text-transform:uppercase; transition:opacity 0.2s;">
                        RUN INSPECTION →
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ─── PIPELINE OVERLAY ──────────────────────────────────────── --}}
    <div id="pipelineOverlay">
        {{-- Thumbnail + scanline --}}
        <div id="thumbWrap">
            <img id="thumbImg" src="" alt="instrument" />
            <div id="scanLine"></div>
        </div>

        {{-- Header --}}
        <div id="pipelineHeader">
            <span id="headerLabel">DAMIAN · AI INSPECTION PIPELINE</span>
            <span id="headerStatus">INITIALIZING</span>
        </div>

        {{-- Nodes --}}
        <div id="nodesRow">
            <div class="pipe-node" id="node-0">
                <div class="node-num">01</div>
                <div class="node-icon">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                        <circle cx="14" cy="14" r="6" stroke="currentColor" stroke-width="1.5"/>
                        <circle cx="14" cy="14" r="2.5" fill="currentColor"/>
                        <line x1="14" y1="4" x2="14" y2="7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="14" y1="21" x2="14" y2="24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="4" y1="14" x2="7" y2="14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="21" y1="14" x2="24" y2="14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="node-label">IMAGE<br>QUALITY</div>
                <div class="node-spinner"></div>
                <div class="node-check">✓</div>
            </div>
            <div class="pipe-connector" id="conn-0"><div class="conn-dot"></div></div>

            <div class="pipe-node" id="node-1">
                <div class="node-num">02</div>
                <div class="node-icon">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                        <circle cx="14" cy="14" r="9" stroke="currentColor" stroke-width="1.5"/>
                        <circle cx="14" cy="14" r="5" stroke="currentColor" stroke-width="1.5"/>
                        <circle cx="14" cy="14" r="1.5" fill="currentColor"/>
                        <line x1="14" y1="2" x2="14" y2="5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="14" y1="23" x2="14" y2="26" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="2" y1="14" x2="5" y2="14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="23" y1="14" x2="26" y2="14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="node-label">YOLO<br>SCAN</div>
                <div class="node-spinner"></div>
                <div class="node-check">✓</div>
            </div>
            <div class="pipe-connector" id="conn-1"><div class="conn-dot"></div></div>

            <div class="pipe-node" id="node-2">
                <div class="node-num">03</div>
                <div class="node-icon">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                        <rect x="5" y="4" width="18" height="20" rx="2" stroke="currentColor" stroke-width="1.5"/>
                        <line x1="9" y1="9" x2="19" y2="9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="9" y1="13" x2="19" y2="13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="9" y1="17" x2="15" y2="17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="node-label">REGULATORY<br>CONTEXT</div>
                <div class="node-spinner"></div>
                <div class="node-check">✓</div>
            </div>
            <div class="pipe-connector" id="conn-2"><div class="conn-dot"></div></div>

            <div class="pipe-node" id="node-3">
                <div class="node-num">04</div>
                <div class="node-icon">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                        <text x="5" y="20" font-family="JetBrains Mono,monospace" font-size="18" fill="currentColor" font-weight="700">Σ</text>
                    </svg>
                </div>
                <div class="node-label">FMEA<br>RISK SCORE</div>
                <div class="node-spinner"></div>
                <div class="node-check">✓</div>
            </div>
            <div class="pipe-connector" id="conn-3"><div class="conn-dot"></div></div>

            <div class="pipe-node" id="node-4">
                <div class="node-num">05</div>
                <div class="node-icon">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                        <path d="M6 14 L11 20 L22 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="node-label">FINAL<br>REPORT</div>
                <div class="node-spinner"></div>
                <div class="node-check">✓</div>
            </div>
        </div>

        {{-- Step log --}}
        <div id="stepLog"></div>

        {{-- Verdict panel --}}
        <div id="verdictPanel">
            <div id="verdictBadge"></div>
            <div id="crsRow">
                <span style="color:#8899AA; font-size:11px; letter-spacing:3px;">COMPOSITE RISK SCORE</span>
                <span id="crsValue" style="color:#C9963E; font-size:22px; font-weight:700; margin-left:16px;">0</span>
            </div>
            <div id="defectRow"></div>
            <a id="reportLink" href="#" style="display:inline-block; margin-top:20px; padding:11px 32px; font-size:11px; font-weight:700; letter-spacing:3px; text-decoration:none; text-transform:uppercase; transition:opacity 0.2s;">
                VIEW FULL REPORT →
            </a>
        </div>

        {{-- Do not close warning --}}
        <div id="dncWarning">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M6 1L11 10H1L6 1Z" stroke="#E74C3C" stroke-width="1.2" fill="none"/>
                <line x1="6" y1="4.5" x2="6" y2="7" stroke="#E74C3C" stroke-width="1.2" stroke-linecap="round"/>
                <circle cx="6" cy="8.5" r="0.6" fill="#E74C3C"/>
            </svg>
            DO NOT CLOSE OR REFRESH — ANALYSIS IN PROGRESS
        </div>
    </div>

    {{-- ─── STYLES ─────────────────────────────────────────────────── --}}
    <style>
    /* ---------- overlay ---------- */
    #pipelineOverlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: #060F1E;
        font-family: 'JetBrains Mono', 'IBM Plex Mono', monospace;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0;
    }

    /* ---------- thumbnail ---------- */
    #thumbWrap {
        position: absolute;
        top: 24px;
        left: 28px;
        width: 88px;
        height: 88px;
        border: 1px solid rgba(201,150,62,0.25);
        overflow: hidden;
        background: #0B1F3A;
    }
    #thumbImg {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.85;
    }
    #scanLine {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, transparent, rgba(74,246,255,0.6), transparent);
        animation: scanSweep 2s ease-in-out infinite;
    }
    @keyframes scanSweep {
        0%   { top: 0%;   opacity: 1; }
        90%  { top: 100%; opacity: 0.4; }
        100% { top: 0%;   opacity: 0; }
    }

    /* ---------- header ---------- */
    #pipelineHeader {
        position: absolute;
        top: 28px;
        right: 28px;
        text-align: right;
    }
    #headerLabel {
        display: block;
        font-size: 10px;
        letter-spacing: 4px;
        color: #4A7C9E;
        margin-bottom: 4px;
    }
    #headerStatus {
        display: inline-block;
        font-size: 10px;
        letter-spacing: 3px;
        color: #C9963E;
        padding: 3px 8px;
        border: 1px solid rgba(201,150,62,0.3);
    }

    /* ---------- nodes row ---------- */
    #nodesRow {
        display: flex;
        align-items: center;
        gap: 0;
        margin-bottom: 32px;
    }

    /* ---------- node ---------- */
    .pipe-node {
        width: 90px;
        height: 100px;
        border: 1px solid #1a3050;
        background: #080F1E;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 5px;
        position: relative;
        transition: border-color 0.3s cubic-bezier(0.4,0,0.2,1),
                    background 0.3s cubic-bezier(0.4,0,0.2,1),
                    box-shadow 0.3s cubic-bezier(0.4,0,0.2,1);
        color: #2a4060;
        cursor: default;
    }
    .node-num {
        position: absolute;
        top: 6px;
        left: 8px;
        font-size: 9px;
        letter-spacing: 1px;
        color: #2a4060;
        transition: color 0.3s;
    }
    .node-icon { transition: color 0.3s; }
    .node-label {
        font-size: 8px;
        letter-spacing: 1px;
        text-align: center;
        line-height: 1.4;
        color: #2a4060;
        text-transform: uppercase;
        transition: color 0.3s;
    }
    .node-spinner {
        display: none;
        position: absolute;
        inset: -1px;
        border: 1px solid transparent;
        border-top-color: #4AF6FF;
        border-radius: 0;
        animation: nodeSpinRing 0.9s linear infinite;
    }
    .node-check {
        display: none;
        position: absolute;
        bottom: 6px;
        right: 8px;
        font-size: 11px;
        color: #2ECC71;
    }

    /* running state */
    .pipe-node.running {
        border-color: #4AF6FF;
        background: #071825;
        box-shadow: 0 0 20px rgba(74,246,255,0.18), inset 0 0 12px rgba(74,246,255,0.06);
        animation: nodePulse 1.4s ease-in-out infinite;
        color: #4AF6FF;
    }
    .pipe-node.running .node-num   { color: rgba(74,246,255,0.5); }
    .pipe-node.running .node-label { color: #ffffff; }
    .pipe-node.running .node-spinner { display: block; }

    /* complete state */
    .pipe-node.complete {
        border-color: #2ECC71;
        background: #071a0f;
        box-shadow: 0 0 16px rgba(46,204,113,0.18);
        animation: nodeFlash 0.4s ease-out forwards;
        color: #2ECC71;
    }
    .pipe-node.complete .node-num   { color: rgba(46,204,113,0.5); }
    .pipe-node.complete .node-label { color: #2ECC71; }
    .pipe-node.complete .node-spinner { display: none; }
    .pipe-node.complete .node-check { display: block; }

    /* fail state */
    .pipe-node.fail {
        border-color: #E74C3C;
        background: #1a0707;
        box-shadow: 0 0 16px rgba(231,76,60,0.2);
        color: #E74C3C;
    }
    .pipe-node.fail .node-label { color: #E74C3C; }
    .pipe-node.fail .node-spinner { display: none; }

    @keyframes nodePulse {
        0%,100% { box-shadow: 0 0 20px rgba(74,246,255,0.18), inset 0 0 12px rgba(74,246,255,0.06); }
        50%      { box-shadow: 0 0 32px rgba(74,246,255,0.35), inset 0 0 16px rgba(74,246,255,0.12); }
    }
    @keyframes nodeFlash {
        0%   { box-shadow: 0 0 40px rgba(46,204,113,0.7); }
        100% { box-shadow: 0 0 16px rgba(46,204,113,0.18); }
    }
    @keyframes nodeSpinRing {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }

    /* ---------- connectors ---------- */
    .pipe-connector {
        width: 52px;
        height: 2px;
        background: #1a3050;
        position: relative;
        overflow: hidden;
        flex-shrink: 0;
        transition: background 0.3s;
    }
    .pipe-connector.active { background: #1a3050; }
    .conn-dot {
        position: absolute;
        top: -2px;
        left: -16px;
        width: 16px;
        height: 6px;
        background: linear-gradient(90deg, transparent, #4AF6FF, transparent);
        opacity: 0;
    }
    .pipe-connector.active .conn-dot {
        opacity: 1;
        animation: connFlow 0.7s cubic-bezier(0.4,0,0.2,1) forwards;
    }
    @keyframes connFlow {
        from { left: -16px; }
        to   { left: 100%; }
    }

    /* ---------- step log ---------- */
    #stepLog {
        min-height: 20px;
        margin-bottom: 24px;
        font-size: 10px;
        letter-spacing: 2px;
        color: #4A7C9E;
        text-align: center;
        transition: color 0.3s;
    }

    /* ---------- verdict ---------- */
    #verdictPanel {
        display: none;
        flex-direction: column;
        align-items: center;
        animation: verdictRise 0.5s cubic-bezier(0.34,1.56,0.64,1) forwards;
        text-align: center;
    }
    @keyframes verdictRise {
        from { opacity: 0; transform: translateY(24px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    #verdictBadge {
        font-size: 56px;
        font-weight: 700;
        letter-spacing: 10px;
        margin-bottom: 12px;
        line-height: 1;
    }
    #crsRow { margin-bottom: 6px; }
    #defectRow {
        font-size: 10px;
        letter-spacing: 3px;
        color: #8899AA;
        text-transform: uppercase;
    }
    #reportLink {
        background: #C9963E;
        color: #060F1E;
        font-family: 'JetBrains Mono', monospace;
    }
    #reportLink:hover { opacity: 0.85; }

    /* ---------- scanline overlay on verdict ---------- */
    #verdictPanel.scanpulse::after {
        content: '';
        position: fixed;
        inset: 0;
        background: repeating-linear-gradient(
            0deg,
            rgba(0,0,0,0.08) 0px,
            rgba(0,0,0,0.08) 1px,
            transparent 1px,
            transparent 3px
        );
        pointer-events: none;
        animation: scanPulse 0.6s ease-out forwards;
        z-index: 10000;
    }
    @keyframes scanPulse {
        0%   { opacity: 1; }
        100% { opacity: 0; }
    }

    /* ---------- DNC warning ---------- */
    #dncWarning {
        position: absolute;
        bottom: 24px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 9px;
        letter-spacing: 2px;
        color: rgba(231,76,60,0.6);
        text-transform: uppercase;
    }
    </style>

    @push('scripts')
    <script>
    /* ═══════════════════════════════════════════════════════════
       DAMIAN — SSE Pipeline Animation
       Intercepts form submit, streams from /api/react/inspect-stream,
       drives node states in real time, reveals verdict on complete.
    ═══════════════════════════════════════════════════════════ */

    const STEP_LABELS = [
        'IMAGE QUALITY CHECK',
        'TWO-STAGE YOLO SCAN',
        'REGULATORY CONTEXT',
        'FMEA RISK SCORE',
        'FINALIZING REPORT',
    ];

    // ── file preview ──────────────────────────────────────────
    function handleFile(file) {
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('previewName').textContent =
                file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
            document.getElementById('dropText').style.display  = 'none';
            document.getElementById('previewBox').style.display = 'block';
            // cache for overlay thumbnail
            document.getElementById('thumbImg').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    // ── drag-drop ─────────────────────────────────────────────
    const dz = document.getElementById('dropZone');
    dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('dragover'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
    dz.addEventListener('drop', e => {
        e.preventDefault();
        dz.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file) {
            document.getElementById('imageInput').files = e.dataTransfer.files;
            handleFile(file);
        }
    });

    // ── node helpers ──────────────────────────────────────────
    function setNodeState(idx, state) {
        const node = document.getElementById('node-' + idx);
        if (!node) return;
        node.classList.remove('running', 'complete', 'fail');
        if (state) node.classList.add(state);
    }

    function activateConnector(idx) {
        const conn = document.getElementById('conn-' + idx);
        if (!conn) return;
        conn.classList.remove('active');
        void conn.offsetWidth; // reflow to restart animation
        conn.classList.add('active');
    }

    function setLog(msg) {
        document.getElementById('stepLog').textContent = msg;
    }

    function setStatus(msg) {
        document.getElementById('headerStatus').textContent = msg;
    }

    // ── CRS counter ───────────────────────────────────────────
    function animateCRS(target) {
        const el = document.getElementById('crsValue');
        const duration = 1200;
        const start = performance.now();
        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            el.textContent = (eased * target).toFixed(1);
            if (progress < 1) requestAnimationFrame(tick);
            else el.textContent = target.toFixed(1);
        }
        requestAnimationFrame(tick);
    }

    // ── typewriter ────────────────────────────────────────────
    function typewrite(el, text, speed) {
        el.textContent = '';
        let i = 0;
        const iv = setInterval(() => {
            el.textContent += text[i++];
            if (i >= text.length) clearInterval(iv);
        }, speed);
    }

    // ── show verdict ──────────────────────────────────────────
    function showVerdict(data) {
        setStatus('COMPLETE');
        document.getElementById('dncWarning').style.display = 'none';

        const passThrough = (data.pass_fail || data.verdict || 'FAIL').toUpperCase();
        const crs  = parseFloat(data.composite_risk_score ?? data.crs ?? 0);
        const def  = (data.defect_type  || 'unknown').toUpperCase();
        const inst = (data.instrument_class || '').toUpperCase();
        const id   = data.inspection_id;

        const colorMap = { PASS: '#2ECC71', FAIL: '#E74C3C', FLAGGED: '#C9963E' };
        const color = colorMap[passThrough] || '#C9963E';

        const badge = document.getElementById('verdictBadge');
        badge.textContent = passThrough;
        badge.style.color = color;
        badge.style.textShadow = `0 0 40px ${color}`;

        animateCRS(crs);

        const defectRow = document.getElementById('defectRow');
        typewrite(defectRow, (inst ? inst + ' · ' : '') + def, 40);

        if (id) {
            const link = document.getElementById('reportLink');
            link.href = '/results/' + id;
            link.style.background = color;
        }

        const panel = document.getElementById('verdictPanel');
        panel.style.display = 'flex';

        // CRT scanline pulse
        setTimeout(() => {
            panel.classList.add('scanpulse');
            setTimeout(() => panel.classList.remove('scanpulse'), 700);
        }, 200);
    }

    // ── SSE consumer ──────────────────────────────────────────
    async function runInspection(formData) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        let response;
        try {
            response = await fetch('/api/react/inspect-stream', {
                method: 'POST',
                body:   formData,
                headers: { 'X-CSRF-TOKEN': csrfToken },
            });
        } catch (err) {
            setLog('CONNECTION ERROR — ' + err.message);
            setStatus('ERROR');
            return;
        }

        if (!response.ok) {
            setLog('SERVER ERROR ' + response.status);
            setStatus('ERROR');
            return;
        }

        const reader  = response.body.getReader();
        const decoder = new TextDecoder();
        let   buffer  = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });

            // SSE messages are separated by double newline
            const messages = buffer.split('\n\n');
            buffer = messages.pop();

            for (const msg of messages) {
                const dataLine = msg.split('\n').find(l => l.startsWith('data: '));
                if (!dataLine) continue;
                let event;
                try { event = JSON.parse(dataLine.slice(6)); }
                catch { continue; }
                handleSSEEvent(event);
            }
        }
    }

    function handleSSEEvent(event) {
        if (event.type === 'step') {
            const idx = event.step_index ?? 0;
            if (event.status === 'running') {
                setNodeState(idx, 'running');
                setLog('[ ' + (STEP_LABELS[idx] || 'STEP ' + (idx+1)) + ' ]');
                setStatus('STEP ' + (idx + 1) + ' / 5');
                if (idx > 0) activateConnector(idx - 1);
            } else if (event.status === 'complete') {
                setNodeState(idx, 'complete');
                if (idx < 4) activateConnector(idx);
            }
        } else if (event.type === 'complete') {
            // All nodes green
            for (let i = 0; i < 5; i++) setNodeState(i, 'complete');
            setTimeout(() => showVerdict(event), 400);
        } else if (event.type === 'error') {
            setLog('⚠ ' + (event.message || 'Inspection error'));
            setStatus('ERROR');
        }
    }

    // ── form submit ───────────────────────────────────────────
    document.getElementById('inspectForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const imageInput = document.getElementById('imageInput');
        if (!imageInput.files.length) {
            alert('Please select an instrument image first.');
            return;
        }

        // Lock the button
        const btn = document.getElementById('submitBtn');
        btn.textContent = 'ANALYZING...';
        btn.disabled = true;
        btn.style.opacity = '0.6';

        // Build FormData
        const formData = new FormData(this);

        // Show overlay
        const overlay = document.getElementById('pipelineOverlay');
        overlay.style.display = 'flex';

        // Warn on close
        window.addEventListener('beforeunload', e => {
            e.preventDefault();
            e.returnValue = '';
        });

        // Stream
        await runInspection(formData);
    });
    </script>
    @endpush
</x-app-layout>
