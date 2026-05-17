<x-app-layout>
    <x-slot name="header">
        <span style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; color:#8A9BAE; letter-spacing:0.12em; text-transform:uppercase;">
            [ COMMAND CENTER ]
        </span>
        <a href="{{ route('inspections.upload') }}"
           style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; letter-spacing:0.1em; color:#060F1E; background:#C9963E; padding:8px 18px; text-decoration:none; text-transform:uppercase;">
            + NEW INSPECTION
        </a>
    </x-slot>

    {{-- ── KPI Strip ─────────────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(6,1fr); gap:1px; background:rgba(201,150,62,0.1); margin-bottom:1px;">

        <div class="tac-card">
            <div class="tac-label">Total Inspections</div>
            <div class="tac-value">{{ $total }}</div>
            <div class="tac-sub">All time</div>
        </div>

        <div class="tac-card">
            <div class="tac-label">Pass Rate</div>
            <div class="tac-value" style="color:{{ $passRate >= 80 ? '#2ECC71' : '#F39C12' }};">{{ $passRate }}%</div>
            <div class="tac-sub">{{ $passed }} passed</div>
        </div>

        <div class="tac-card">
            <div class="tac-label">Cost Saved</div>
            <div class="tac-value" style="color:#C9963E;">${{ $costSaved }}</div>
            <div class="tac-sub">vs manual @ $0.15/ea</div>
        </div>

        <div class="tac-card">
            <div class="tac-label">Flagged</div>
            <div class="tac-value" style="color:#F39C12;">{{ $flagged }}</div>
            <div class="tac-sub">Needs review</div>
        </div>

        <div class="tac-card">
            <div class="tac-label">Rejected</div>
            <div class="tac-value" style="color:#E74C3C;">{{ $failed }}</div>
            <div class="tac-sub">Defects caught</div>
        </div>

        <div class="tac-card">
            <div class="tac-label">Avg Confidence</div>
            <div class="tac-value" style="color:#4A7C9E;">
                @if($avgConfidence !== null) {{ $avgConfidence }}% @else — @endif
            </div>
            <div class="tac-sub">Model certainty</div>
        </div>

    </div>

    {{-- ── ROI Banner ─────────────────────────────────────────────────── --}}
    <div style="background:linear-gradient(90deg, #0B1F3A 0%, #0D2440 100%); border:1px solid rgba(201,150,62,0.2); border-left:3px solid #C9963E; padding:18px 24px; margin-bottom:1px; display:flex; align-items:center; justify-content:space-between;">
        <div>
            <div class="tac-label" style="margin-bottom:6px;">[ ROI VS. MANUAL INSPECTION ]</div>
            <div style="color:#8A9BAE; font-size:12px; line-height:1.8;">
                Manual: <span style="color:#E8EDF2;">$0.15/inspection</span>
                &nbsp;·&nbsp; AI: <span style="color:#E8EDF2;">$0.01/inspection</span>
                &nbsp;·&nbsp; <span style="color:#C9963E; font-weight:700;">$0.14 saved per unit</span>
            </div>
            <div style="color:#8A9BAE; font-size:12px;">
                10,000 inspections/month → <span style="color:#2ECC71; font-weight:700;">$1,400/mo · $16,800/yr</span>
                &nbsp;·&nbsp; Industry benchmark: <span style="color:#C9963E;">374% 3-yr ROI</span> (Forrester)
            </div>
        </div>
        <div style="text-align:right; flex-shrink:0; padding-left:32px;">
            <div style="font-size:36px; font-weight:700; color:#C9963E; line-height:1;">${{ $costSaved }}</div>
            <div class="tac-sub">saved so far</div>
        </div>
    </div>

    {{-- ── Main Grid: Table + Chart ──────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:1fr 380px; gap:1px; background:rgba(201,150,62,0.1); margin-bottom:1px;">

        {{-- Recent Inspections Table --}}
        <div class="tac-card" style="padding:0; overflow:hidden;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid rgba(201,150,62,0.15);">
                <div class="tac-label" style="margin:0;">[ RECENT INSPECTIONS ]</div>
                <a href="{{ route('inspections.audit-log') }}"
                   style="font-size:10px; color:#C9963E; text-decoration:none; letter-spacing:0.08em; font-weight:600;">
                    VIEW ALL →
                </a>
            </div>

            @if($recent->count())
            <table>
                <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th style="width:100px;">STATUS</th>
                        <th>DEFECT / INSTRUMENT</th>
                        <th style="width:90px;">CONF.</th>
                        <th style="width:120px;">TIMESTAMP</th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recent as $insp)
                    @php $pf = strtoupper($insp->pass_fail ?? ''); @endphp
                    <tr>
                        <td style="color:#8A9BAE; font-size:11px;">#{{ $insp->id }}</td>
                        <td>
                            @if($pf === 'PASS')
                                <span class="badge-pass">✓ PASS</span>
                            @elseif($pf === 'FAIL')
                                <span class="badge-fail">✗ FAIL</span>
                            @elseif($pf === 'FLAGGED')
                                <span class="badge-flag">⚑ FLAG</span>
                            @else
                                <span class="badge-grey">{{ $insp->pass_fail }}</span>
                            @endif
                        </td>
                        <td style="color:#8A9BAE; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px;">
                            {{ $insp->defect_type ?? '—' }}
                        </td>
                        <td style="color:#4A7C9E; font-size:12px;">
                            {{ $insp->confidence ? round($insp->confidence) . '%' : '—' }}
                        </td>
                        <td style="color:#8A9BAE; font-size:11px;">
                            {{ $insp->created_at->format('M d · H:i') }}
                        </td>
                        <td>
                            <a href="{{ route('inspections.results', $insp->id) }}"
                               style="color:#C9963E; text-decoration:none; font-size:10px; font-weight:700; letter-spacing:0.08em;">
                                VIEW
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div style="padding:48px; text-align:center; color:#8A9BAE;">
                <div style="font-size:32px; opacity:0.2; margin-bottom:12px;">◈</div>
                <div style="margin-bottom:8px;">No inspections yet.</div>
                <a href="{{ route('inspections.upload') }}" style="color:#C9963E; text-decoration:none; font-size:11px; font-weight:700; letter-spacing:0.08em;">
                    UPLOAD YOUR FIRST IMAGE →
                </a>
            </div>
            @endif
        </div>

        {{-- 14-Day Trend Chart --}}
        <div class="tac-card">
            <div class="tac-label" style="margin-bottom:16px;">[ 14-DAY TREND ]</div>
            <canvas id="trendChart" height="200"></canvas>
            <div style="display:flex; gap:16px; margin-top:12px; justify-content:center;">
                <span style="font-size:10px; color:#2ECC71; display:flex; align-items:center; gap:4px;"><span style="display:inline-block; width:10px; height:3px; background:#2ECC71;"></span>PASS</span>
                <span style="font-size:10px; color:#E74C3C; display:flex; align-items:center; gap:4px;"><span style="display:inline-block; width:10px; height:3px; background:#E74C3C;"></span>FAIL</span>
                <span style="font-size:10px; color:#F39C12; display:flex; align-items:center; gap:4px;"><span style="display:inline-block; width:10px; height:3px; background:#F39C12;"></span>FLAG</span>
            </div>
        </div>

    </div>

    {{-- ── Pipeline Explainer ─────────────────────────────────────────── --}}
    <div class="tac-card">
        <div class="tac-label" style="margin-bottom:20px;">[ AI INSPECTION PIPELINE ]</div>
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1px; background:rgba(201,150,62,0.1);">
            <div style="background:#0D1A2E; padding:20px; text-align:center;">
                <div style="font-size:28px; color:rgba(201,150,62,0.4); margin-bottom:10px; font-weight:700;">01</div>
                <div style="color:#E8EDF2; font-size:12px; font-weight:600; margin-bottom:6px; letter-spacing:0.06em;">UPLOAD IMAGE</div>
                <div style="color:#8A9BAE; font-size:11px; line-height:1.5;">Photo of surgical instrument from production line</div>
            </div>
            <div style="background:#0D1A2E; padding:20px; text-align:center;">
                <div style="font-size:28px; color:rgba(201,150,62,0.4); margin-bottom:10px; font-weight:700;">02</div>
                <div style="color:#E8EDF2; font-size:12px; font-weight:600; margin-bottom:6px; letter-spacing:0.06em;">YOLOv8 DETECT</div>
                <div style="color:#8A9BAE; font-size:11px; line-height:1.5;">Fine-tuned CV identifies instrument class in ~200ms</div>
            </div>
            <div style="background:#0D1A2E; padding:20px; text-align:center;">
                <div style="font-size:28px; color:rgba(201,150,62,0.4); margin-bottom:10px; font-weight:700;">03</div>
                <div style="color:#E8EDF2; font-size:12px; font-weight:600; margin-bottom:6px; letter-spacing:0.06em;">CLAUDE REASONING</div>
                <div style="color:#8A9BAE; font-size:11px; line-height:1.5;">Vision model assesses defects, references ISO/FDA standards</div>
            </div>
            <div style="background:#0D1A2E; padding:20px; text-align:center;">
                <div style="font-size:28px; color:rgba(201,150,62,0.4); margin-bottom:10px; font-weight:700;">04</div>
                <div style="color:#E8EDF2; font-size:12px; font-weight:600; margin-bottom:6px; letter-spacing:0.06em;">QC DECISION</div>
                <div style="color:#8A9BAE; font-size:11px; line-height:1.5;">PASS / FAIL / FLAGGED with regulatory note and audit trail</div>
            </div>
        </div>
    </div>

    {{-- Chart.js --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const labels = @json($trendLabels);
        const pass   = @json($trendPass);
        const fail   = @json($trendFail);
        const flag   = @json($trendFlagged);

        new Chart(document.getElementById('trendChart'), {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'PASS',    data: pass, backgroundColor: 'rgba(46,204,113,0.7)',  borderColor: '#2ECC71', borderWidth: 1 },
                    { label: 'FAIL',    data: fail, backgroundColor: 'rgba(231,76,60,0.7)',   borderColor: '#E74C3C', borderWidth: 1 },
                    { label: 'FLAGGED', data: flag, backgroundColor: 'rgba(243,156,18,0.7)',  borderColor: '#F39C12', borderWidth: 1 },
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0B1F3A',
                        borderColor: 'rgba(201,150,62,0.3)',
                        borderWidth: 1,
                        titleColor: '#C9963E',
                        bodyColor: '#E8EDF2',
                        titleFont: { family: 'JetBrains Mono', size: 11 },
                        bodyFont:  { family: 'JetBrains Mono', size: 11 },
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { color: 'rgba(201,150,62,0.07)', display: false },
                        ticks: { color: '#8A9BAE', font: { family: 'JetBrains Mono', size: 10 } },
                        border: { color: 'rgba(201,150,62,0.15)' }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#8A9BAE', font: { family: 'JetBrains Mono', size: 10 } },
                        grid: { color: 'rgba(201,150,62,0.07)' },
                        border: { color: 'rgba(201,150,62,0.15)' }
                    }
                }
            }
        });
    })();
    </script>
    @endpush

</x-app-layout>
