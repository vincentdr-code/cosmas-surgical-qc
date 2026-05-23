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

    <div style="max-width:800px; margin:0 auto;">

        @if(session('error'))
        <div style="background:rgba(231,76,60,0.12); border:1px solid rgba(231,76,60,0.4); border-left:3px solid #E74C3C; padding:14px 20px; margin-bottom:20px; color:#E8EDF2; font-size:12px;">
            ⚠ {{ session('error') }}
        </div>
        @endif

        @if($errors->any())
        <div style="background:rgba(231,76,60,0.12); border:1px solid rgba(231,76,60,0.4); border-left:3px solid #E74C3C; padding:14px 20px; margin-bottom:20px; color:#E8EDF2; font-size:12px;">
            @foreach($errors->all() as $error)
                <div>⚠ {{ $error }}</div>
            @endforeach
        </div>
        @endif

        <div class="tac-card">
            <div class="tac-label" style="margin-bottom:24px;">[ SUBMIT INSPECTION ]</div>

            <form action="{{ route('inspections.store') }}" method="POST" enctype="multipart/form-data" id="inspectForm">
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
                        <label style="display:block; font-size:10px; letter-spacing:0.1em; color:#8A9BAE; font-weight:600; margin-bottom:6px; text-transform:uppercase;">INSTRUMENT ID</label>
                        <input type="text" name="instrument_id" placeholder="e.g. SCALPEL-42A"
                               value="{{ old('instrument_id') }}"
                               style="width:100%; padding:10px 14px;">
                    </div>
                    <div>
                        <label style="display:block; font-size:10px; letter-spacing:0.1em; color:#8A9BAE; font-weight:600; margin-bottom:6px; text-transform:uppercase;">OPERATOR ID</label>
                        <input type="text" name="operator_id" placeholder="e.g. OPS-007"
                               value="{{ old('operator_id') }}"
                               style="width:100%; padding:10px 14px;">
                    </div>
                </div>

                <div style="margin-top:12px;">
                    <label style="display:block; font-size:10px; letter-spacing:0.1em; color:#8A9BAE; font-weight:600; margin-bottom:6px; text-transform:uppercase;">NOTES (optional)</label>
                    <textarea name="notes" rows="3" placeholder="Production line, batch number, shift info..."
                              style="width:100%; padding:10px 14px; resize:vertical;">{{ old('notes') }}</textarea>
                </div>

                {{-- Submit --}}
                <div style="margin-top:20px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="font-size:10px; color:rgba(138,155,174,0.6); letter-spacing:0.08em;">
                        TWO-STAGE AI: YOLOv8 → CLAUDE VISION · FDA 21 CFR PART 820
                    </div>
                    <button type="submit" id="submitBtn"
                        style="background:#C9963E; color:#060F1E; font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; letter-spacing:0.1em; padding:10px 28px; border:none; cursor:pointer; text-transform:uppercase;">
                        RUN INSPECTION →
                    </button>
                </div>

            </form>
        </div>

        {{-- Info Panel --}}
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1px; background:rgba(201,150,62,0.1); margin-top:1px;">
            <div class="tac-card" style="text-align:center;">
                <div style="color:#C9963E; font-size:20px; margin-bottom:6px;">~2s</div>
                <div class="tac-label">Analysis Time</div>
            </div>
            <div class="tac-card" style="text-align:center;">
                <div style="color:#2ECC71; font-size:20px; margin-bottom:6px;">5</div>
                <div class="tac-label">Defect Classes</div>
            </div>
            <div class="tac-card" style="text-align:center;">
                <div style="color:#4A7C9E; font-size:20px; margin-bottom:6px;">FDA</div>
                <div class="tac-label">21 CFR Part 820</div>
            </div>
        </div>
    </div>

    {{-- Full-screen analysis lockout overlay --}}
    <div id="analysisOverlay" style="
        display:none; position:fixed; inset:0; z-index:9999;
        background:rgba(6,15,30,0.97);
        flex-direction:column; align-items:center; justify-content:center;
        gap:0; backdrop-filter:blur(4px);
        font-family:'JetBrains Mono',monospace;
    ">
        {{-- Spinning shield SVG --}}
        <div id="shieldSpin" style="margin-bottom:28px; animation:spinSlow 3s linear infinite;">
            <svg width="72" height="86" viewBox="0 0 200 240" fill="none">
                <path d="M 28,32 L 172,32 L 180,132 C 160,210 140,218 100,218 C 60,218 40,210 20,132 Z"
                    fill="#0B1F3A" stroke="#C9963E" stroke-width="4" stroke-opacity="0.5"/>
                <path d="M 22,118 Q 100,66 178,118" stroke="#C9963E" stroke-width="3" stroke-linecap="round" fill="none" opacity="0.7"/>
                <path d="M 22,118 Q 100,156 178,118" stroke="#C9963E" stroke-width="3" stroke-linecap="round" fill="none" opacity="0.7"/>
                <circle cx="100" cy="118" r="40" stroke="#C9963E" stroke-width="4" fill="none"/>
                <circle cx="100" cy="118" r="36" fill="#162D52"/>
                <circle cx="100" cy="118" r="18" fill="#0B1F3A"/>
                <circle cx="109" cy="109" r="6" fill="#C9963E" opacity="0.9"/>
            </svg>
        </div>

        {{-- Primary status --}}
        <div style="color:#C9963E; font-size:13px; font-weight:700; letter-spacing:6px; text-transform:uppercase; margin-bottom:10px;">
            ANALYZING INSTRUMENT
        </div>
        <div style="color:#4A7C9E; font-size:11px; letter-spacing:2px; margin-bottom:36px;">
            RUNNING 5-TOOL AGENTIC LOOP &middot; CLAUDE + YOLO
        </div>

        {{-- Step list --}}
        <div id="stepList" style="display:flex; flex-direction:column; gap:0;">
            @foreach([
                'IMAGE QUALITY VERIFICATION',
                'TWO-STAGE YOLO SCAN',
                'REGULATORY CONTEXT LOOKUP',
                'COMPOSITE RISK SCORE CALCULATION',
                'FINALIZING INSPECTION REPORT',
            ] as $i => $step)
            <div class="analysis-step" data-delay="{{ $i * 3500 + 1000 }}" style="
                display:flex; align-items:center; gap:10; padding:5px 0;
                opacity:0; transition:opacity 0.4s ease, transform 0.4s ease;
                transform:translateX(-8px);
            ">
                <svg width="8" height="8" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" fill="#C9963E" opacity="0.7"/></svg>
                <span style="color:#8899AA; font-size:10px; letter-spacing:2px;">{{ $step }}</span>
            </div>
            @endforeach
        </div>

        {{-- Do not refresh warning --}}
        <div style="
            margin-top:40px; padding:10px 24px;
            border:1px solid rgba(231,76,60,0.4);
            background:rgba(231,76,60,0.06);
            display:flex; align-items:center; gap:10;
        ">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M7 1L13 12H1L7 1Z" stroke="#E74C3C" stroke-width="1.5" fill="none"/>
                <line x1="7" y1="5" x2="7" y2="8.5" stroke="#E74C3C" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="7" cy="10.5" r="0.7" fill="#E74C3C"/>
            </svg>
            <span style="color:#E74C3C; font-size:10px; font-weight:700; letter-spacing:2px;">
                DO NOT CLOSE OR REFRESH &mdash; ANALYSIS IN PROGRESS
            </span>
        </div>
    </div>

    <style>
    @keyframes spinSlow {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }
    </style>

    @push('scripts')
    <script>
    function handleFile(file) {
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('previewName').textContent = file.name + ' (' + (file.size/1024).toFixed(0) + ' KB)';
            document.getElementById('dropText').style.display = 'none';
            document.getElementById('previewBox').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    const dz = document.getElementById('dropZone');
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
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

    document.getElementById('inspectForm').addEventListener('submit', function() {
        // Disable button
        const btn = document.getElementById('submitBtn');
        btn.textContent = 'ANALYZING...';
        btn.disabled = true;
        btn.style.opacity = '0.7';

        // Show full-screen lockout overlay
        const overlay = document.getElementById('analysisOverlay');
        overlay.style.display = 'flex';

        // Fade in step indicators one by one
        document.querySelectorAll('.analysis-step').forEach(function(el) {
            const delay = parseInt(el.dataset.delay, 10);
            setTimeout(function() {
                el.style.opacity = '1';
                el.style.transform = 'translateX(0)';
            }, delay);
        });

        // Warn on accidental refresh/close
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = 'Inspection is in progress. Leaving will cancel the analysis.';
        });
    });
    </script>
    @endpush
</x-app-layout>
