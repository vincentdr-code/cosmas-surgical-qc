<x-app-layout>
    <x-slot name="header">
        <span style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; color:#8A9BAE; letter-spacing:0.12em; text-transform:uppercase;">
            [ DAMIAN ]
        </span>
        <a href="{{ route('dashboard') }}"
           style="font-family:'JetBrains Mono',monospace; font-size:11px; color:#8A9BAE; text-decoration:none; letter-spacing:0.08em;">
            <- DASHBOARD
        </a>
    </x-slot>

    <div style="max-width:860px; margin:0 auto;">

        <div class="tac-card" style="margin-bottom:1px;">
            <div class="tac-label" style="margin-bottom:4px;">[ DAMIAN -- QC INTELLIGENCE ]</div>
            <div style="color:rgba(138,155,174,0.5); font-size:9px; letter-spacing:0.08em; margin-bottom:20px;">
                PATRON SAINT OF MEDICINE & SURGERY -- ASK ANYTHING ABOUT YOUR INSPECTION DATA
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:20px;">
                @foreach([
                    "What's the defect rate this week?",
                    "Which defect type is most common?",
                    "How many FAILs in the last 30 days?",
                    "What's the average confidence score?",
                    "Show me the highest risk inspections",
                ] as $q)
                <button onclick="setQuestion(this.dataset.q)" data-q="{{ $q }}"
                    style="background:rgba(201,150,62,0.08); border:1px solid rgba(201,150,62,0.25); color:#8A9BAE;
                           font-family:'JetBrains Mono',monospace; font-size:9px; letter-spacing:0.08em;
                           padding:5px 10px; cursor:pointer; text-transform:uppercase;">
                    {{ $q }}
                </button>
                @endforeach
            </div>

            <div style="display:flex; gap:8px; align-items:flex-start;">
                <textarea id="questionInput" rows="2"
                    placeholder="e.g. What caused the corrosion spike this week?"
                    style="flex:1; padding:10px 14px; resize:none; font-family:'JetBrains Mono',monospace; font-size:11px;"></textarea>
                <button id="queryBtn" onclick="runQuery()"
                    style="background:#C9963E; color:#060F1E; font-family:'JetBrains Mono',monospace;
                           font-size:11px; font-weight:700; letter-spacing:0.1em; padding:10px 20px;
                           border:none; cursor:pointer; text-transform:uppercase; white-space:nowrap;">
                    ASK DAMIAN ->
                </button>
            </div>
        </div>

        <div id="responsePanel" class="tac-card" style="display:none; margin-top:1px;">
            <div class="tac-label" style="margin-bottom:14px;">[ DAMIAN RESPONDS ]</div>
            <div id="responseText" style="color:#E8EDF2; font-size:12px; line-height:1.8; white-space:pre-wrap;"></div>
            <div id="responseTime" style="color:rgba(138,155,174,0.5); font-size:9px; letter-spacing:0.08em; margin-top:12px;"></div>
        </div>

        <div id="historyPanel" style="margin-top:1px; display:none;">
            <div class="tac-card">
                <div class="tac-label" style="margin-bottom:14px;">[ QUERY HISTORY ]</div>
                <div id="historyList" style="display:flex; flex-direction:column; gap:12px;"></div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1px; background:rgba(201,150,62,0.1); margin-top:1px;">
            <div class="tac-card" style="text-align:center;">
                <div style="color:#C9963E; font-size:16px; margin-bottom:4px;">NL->SQL</div>
                <div class="tac-label">Claude Translates</div>
            </div>
            <div class="tac-card" style="text-align:center;">
                <div style="color:#2ECC71; font-size:16px; margin-bottom:4px;">READ-ONLY</div>
                <div class="tac-label">No Data Modified</div>
            </div>
            <div class="tac-card" style="text-align:center;">
                <div style="color:#4A7C9E; font-size:16px; margin-bottom:4px;">21 CFR</div>
                <div class="tac-label">Audit Logged</div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    const queryHistory = [];

    function setQuestion(q) {
        document.getElementById('questionInput').value = q;
        document.getElementById('questionInput').focus();
    }

    async function runQuery() {
        const q = document.getElementById('questionInput').value.trim();
        if (!q) return;

        const btn = document.getElementById('queryBtn');
        btn.textContent = 'DAMIAN IS THINKING...';
        btn.disabled = true;

        const panel = document.getElementById('responsePanel');
        panel.style.display = 'block';
        document.getElementById('responseText').textContent = '[ DAMIAN IS QUERYING THE DATABASE... ]';
        document.getElementById('responseTime').textContent = '';

        const start = Date.now();

        try {
            const res = await fetch('{{ route("intel.query") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ question: q }),
            });

            const data = await res.json();
            const elapsed = ((Date.now() - start) / 1000).toFixed(1);

            document.getElementById('responseText').textContent = data.answer || data.error || 'No response.';
            document.getElementById('responseTime').textContent =
                'DAMIAN RESPONDED IN ' + elapsed + 's -- READ-ONLY -- NO DATA MODIFIED';

            queryHistory.unshift({ q, a: data.answer, t: elapsed });
            renderHistory();

        } catch (e) {
            document.getElementById('responseText').textContent = '[ ERROR: ' + e.message + ' ]';
        }

        btn.textContent = 'ASK DAMIAN ->';
        btn.disabled = false;
    }

    function renderHistory() {
        if (queryHistory.length === 0) return;
        document.getElementById('historyPanel').style.display = 'block';
        const list = document.getElementById('historyList');
        list.innerHTML = queryHistory.slice(0, 5).map(h => `
            <div style="border-left:2px solid rgba(201,150,62,0.3); padding-left:12px;">
                <div style="color:#C9963E; font-size:10px; letter-spacing:0.08em; margin-bottom:4px;">Q: ${h.q}</div>
                <div style="color:#8A9BAE; font-size:11px; white-space:pre-wrap;">${h.a}</div>
                <div style="color:rgba(138,155,174,0.4); font-size:9px; margin-top:4px;">${h.t}s</div>
            </div>
        `).join('');
    }

    document.getElementById('questionInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); runQuery(); }
    });
    </script>
    @endpush
</x-app-layout>
