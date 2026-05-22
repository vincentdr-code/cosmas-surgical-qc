<x-app-layout>
    <x-slot name="header">
        <span style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700;
              letter-spacing:0.12em; text-transform:uppercase;
              background: linear-gradient(135deg, #f2f2f2 0%, #c8c8c8 30%, #f0f0f0 50%, #909090 100%);
              -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            [ AUDIT LOG -- FDA 21 CFR PART 820 ]
        </span>
        <div style="display:flex; gap:10px; align-items:center;">
            @if(Route::has('inspections.export-csv'))
            <a href="{{ route('inspections.export-csv') }}"
               style="font-family:'JetBrains Mono',monospace; font-size:10px; font-weight:700; letter-spacing:0.1em;
                      color:var(--gold); border:1px solid rgba(201,150,62,0.35); padding:6px 14px;
                      text-decoration:none; text-transform:uppercase; transition:border-color 0.15s, background 0.15s;"
               onmouseover="this.style.background='rgba(201,150,62,0.06)'"
               onmouseout="this.style.background='transparent'">
                EXPORT CSV
            </a>
            @endif
            <a href="{{ route('inspections.upload') }}"
               style="font-family:'JetBrains Mono',monospace; font-size:10px; font-weight:700; letter-spacing:0.1em;
                      color:#080808; background:var(--gold); padding:6px 14px; text-decoration:none; text-transform:uppercase;">
                + INSPECT
            </a>
        </div>
    </x-slot>

    {{-- Stats Strip --}}
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1px; background:rgba(255,255,255,0.04); margin-bottom:1px;">
        <div class="tac-card">
            <div class="tac-label" style="position:relative; z-index:1;">Total Records</div>
            <div class="tac-value-chrome" style="position:relative; z-index:1;">{{ $inspections->total() ?? $inspections->count() }}</div>
        </div>
        <div class="tac-card">
            <div class="tac-label" style="position:relative; z-index:1;">Last Entry</div>
            <div class="tac-value" style="font-size:16px; color:var(--muted); position:relative; z-index:1;">
                {{ $inspections->first() ? $inspections->first()->created_at->format('M d · H:i') : '—' }}
            </div>
        </div>
        <div class="tac-card">
            <div class="tac-label" style="position:relative; z-index:1;">Compliance</div>
            <div class="tac-value" style="font-size:16px; color:var(--pass); position:relative; z-index:1;">21 CFR 820</div>
        </div>
        <div class="tac-card">
            <div class="tac-label" style="position:relative; z-index:1;">Record Type</div>
            <div class="tac-value" style="font-size:16px; color:var(--gold); position:relative; z-index:1;">APPEND-ONLY</div>
        </div>
    </div>

    {{-- Mode Filter --}}
    <div style="display:flex; align-items:center; gap:8px; padding:12px 20px; margin-bottom:1px;
                background:var(--card); border:1px solid rgba(255,255,255,0.06);">
        <span style="font-family:'JetBrains Mono',monospace; font-size:9px; font-weight:700;
                     letter-spacing:0.14em; text-transform:uppercase; color:var(--muted); margin-right:6px;">SCOPE:</span>
        @foreach(['both'=>'ALL','single'=>'SINGLE','batch'=>'BATCH'] as $mVal=>$mLbl)
        @php $mActive = ($mode === $mVal); @endphp
        <a href="{{ request()->fullUrlWithQuery(['mode'=>$mVal, 'page'=>1]) }}"
           style="font-family:'JetBrains Mono',monospace; font-size:9px; font-weight:700;
                  letter-spacing:0.1em; text-transform:uppercase; text-decoration:none; padding:5px 14px;
                  {{ $mActive ? 'color:#080808; background:var(--gold); border:1px solid var(--gold);' : 'color:var(--muted); background:transparent; border:1px solid rgba(255,255,255,0.12);' }}"
           @if(!$mActive)
           onmouseover="this.style.borderColor='rgba(201,150,62,0.4)'; this.style.color='var(--gold)'"
           onmouseout="this.style.borderColor='rgba(255,255,255,0.12)'; this.style.color='var(--muted)'"
           @endif>
            {{ $mLbl }}
        </a>
        @endforeach
        <span style="font-family:'JetBrains Mono',monospace; font-size:9px; color:var(--muted); margin-left:12px; opacity:0.5;">
            {{ $inspections->total() }} record{{ $inspections->total() !== 1 ? 's' : '' }}
        </span>
    </div>

    {{-- Table --}}
    <div class="tac-card" style="padding:0; overflow:hidden;">
        @if($inspections->count())
        <table>
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th style="width:100px;">STATUS</th>
                    <th>DEFECT / INSTRUMENT</th>
                    <th style="width:80px;">CONF.</th>
                    <th style="width:130px;">RISK</th>
                    <th style="width:150px;">TIMESTAMP</th>
                    <th style="width:110px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($inspections as $insp)
                @php
                    $pf = strtoupper($insp->pass_fail ?? '');
                    $rl = strtoupper($insp->risk_level ?? '');
                    $riskColor = match($rl) {
                        'CRITICAL' => 'var(--fail)',
                        'HIGH'     => '#E67E22',
                        'MEDIUM'   => 'var(--flag)',
                        'LOW'      => 'var(--pass)',
                        default    => 'var(--muted)',
                    };
                @endphp
                <tr>
                    <td style="color:var(--muted); font-size:11px;">#{{ $insp->id }}</td>
                    <td>
                        @if($pf === 'PASS')    <span class="badge-pass">PASS</span>
                        @elseif($pf === 'FAIL') <span class="badge-fail">FAIL</span>
                        @elseif($pf === 'FLAGGED') <span class="badge-flag">FLAG</span>
                        @else <span class="badge-grey">{{ $insp->pass_fail }}</span>
                        @endif
                    </td>
                    <td style="color:var(--muted); font-size:12px; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $insp->defect_type ?? '—' }}
                    </td>
                    <td style="color:var(--steel); font-size:12px;">
                        @php $ac=is_numeric($insp->confidence)&&$insp->confidence>0?($insp->confidence<=1?round($insp->confidence*100):round($insp->confidence)):null; @endphp{{ $ac!==null?$ac.'%':'—' }}
                    </td>
                    <td style="font-size:11px; font-weight:700; color:{{ $riskColor }};">
                        {{ $rl ?: '—' }}
                        @if($insp->composite_risk_score !== null)
                        <span style="color:var(--muted); font-weight:400; font-size:10px;">&nbsp;{{ number_format($insp->composite_risk_score, 0) }}</span>
                        @endif
                    </td>
                    <td style="color:var(--muted); font-size:11px;">
                        {{ $insp->created_at->format('Y-m-d H:i:s') }}
                    </td>
                    <td style="white-space:nowrap;">
                        <button onclick="openTrace({{ $insp->id }})"
                                style="font-family:'JetBrains Mono',monospace; font-size:9px; font-weight:700;
                                       letter-spacing:0.1em; text-transform:uppercase; background:transparent;
                                       border:1px solid rgba(255,255,255,0.1); color:var(--muted);
                                       padding:3px 8px; cursor:pointer; margin-right:6px; transition:border-color 0.15s, color 0.15s;"
                                onmouseover="this.style.borderColor='rgba(201,150,62,0.5)'; this.style.color='var(--gold)'"
                                onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.color='var(--muted)'">
                            TRACE
                        </button>
                        <a href="{{ route('inspections.results', $insp->id) }}"
                           style="color:var(--gold); text-decoration:none; font-size:10px; font-weight:700; letter-spacing:0.08em;">
                            VIEW
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        @if(method_exists($inspections, 'links'))
        <div style="padding:16px 20px; border-top:1px solid rgba(255,255,255,0.06); position:relative; z-index:1;">
            {{ $inspections->links() }}
        </div>
        @endif

        @else
        <div style="padding:48px; text-align:center; color:var(--muted); position:relative; z-index:1;">
            <div style="font-size:32px; opacity:0.12; margin-bottom:12px;">o</div>
            <div>No inspection records yet.</div>
        </div>
        @endif
    </div>

    {{-- ── PIPELINE TRACE MODAL ─────────────────────────────────────────────── --}}
    <div id="trace-overlay" onclick="closeTrace(event)"
         style="display:none; position:fixed; inset:0; z-index:9999;
                background:rgba(8,8,8,0.92); backdrop-filter:blur(4px);
                overflow-y:auto; padding:40px 20px;">
        <div id="trace-modal" onclick="event.stopPropagation()"
             style="max-width:720px; margin:0 auto; background:var(--navy);
                    border:1px solid rgba(255,255,255,0.1); border-top:2px solid var(--gold);
                    font-family:'JetBrains Mono',monospace;">

            {{-- Modal header --}}
            <div style="padding:16px 24px; border-bottom:1px solid rgba(255,255,255,0.08);
                        display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <div style="font-size:9px; letter-spacing:0.18em; color:var(--gold); text-transform:uppercase; margin-bottom:4px;">
                        [ PIPELINE TRACE ]
                    </div>
                    <div id="trace-title" style="font-size:13px; font-weight:700; color:#e8e8e8; letter-spacing:0.06em;">
                        Loading...
                    </div>
                </div>
                <button onclick="closeTraceBtn()"
                        style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700;
                               letter-spacing:0.1em; background:transparent;
                               border:1px solid rgba(255,255,255,0.15); color:var(--muted);
                               padding:6px 12px; cursor:pointer;"
                        onmouseover="this.style.borderColor='var(--fail)'; this.style.color='var(--fail)'"
                        onmouseout="this.style.borderColor='rgba(255,255,255,0.15)'; this.style.color='var(--muted)'">
                    CLOSE
                </button>
            </div>

            {{-- Meta strip --}}
            <div id="trace-meta" style="display:none; padding:12px 24px; border-bottom:1px solid rgba(255,255,255,0.06);
                        display:flex; gap:24px; flex-wrap:wrap; background:rgba(255,255,255,0.02);">
            </div>

            {{-- Spinner --}}
            <div id="trace-spinner" style="padding:60px; text-align:center; color:var(--muted);">
                <div style="font-size:28px; margin-bottom:12px; animation:spin-slow 3s linear infinite; display:inline-block;">o</div>
                <div style="font-size:10px; letter-spacing:0.12em;">FETCHING TRACE DATA...</div>
            </div>

            {{-- Steps --}}
            <div id="trace-steps" style="display:none; padding:20px 24px;"></div>

            {{-- Error --}}
            <div id="trace-error" style="display:none; padding:40px; text-align:center; color:var(--fail); font-size:11px;"></div>
        </div>
    </div>

    <style>
        @keyframes spin-slow { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
    </style>

    <script>
    const TRACE_URL = '{{ url("/inspections") }}';

    const TOOL_META = {
        check_image_quality:       { label: 'IMAGE QUALITY CHECK',        icon: 'o' },
        run_yolo_scan:             { label: 'YOLOV8S VISION SCAN',         icon: '*' },
        lookup_regulatory_context: { label: 'REGULATORY CONTEXT LOOKUP',   icon: '+' },
        calculate_risk_score:      { label: 'COMPOSITE RISK SCORE (FMEA)', icon: 'S' },
        finalize_inspection_report:{ label: 'FINAL REPORT GENERATED',      icon: 'V' },
    };

    function openTrace(id) {
        // Reset modal state
        document.getElementById('trace-overlay').style.display = 'block';
        document.getElementById('trace-spinner').style.display = 'block';
        document.getElementById('trace-steps').style.display   = 'none';
        document.getElementById('trace-error').style.display   = 'none';
        document.getElementById('trace-meta').style.display    = 'none';
        document.getElementById('trace-title').textContent     = 'Inspection #' + id;
        document.body.style.overflow = 'hidden';

        fetch(TRACE_URL + '/' + id + '/trace', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.ok ? r.json() : Promise.reject('HTTP ' + r.status))
        .then(data => renderTrace(data))
        .catch(err => {
            document.getElementById('trace-spinner').style.display = 'none';
            document.getElementById('trace-error').style.display   = 'block';
            document.getElementById('trace-error').textContent     = 'Failed to load trace: ' + err;
        });
    }

    function renderTrace(data) {
        // Title
        const vColor = data.verdict === 'PASS' ? '#27ae60' : data.verdict === 'FAIL' ? '#e74c3c' : '#f39c12';
        document.getElementById('trace-title').innerHTML =
            'Inspection #' + data.id +
            ' <span style="color:' + vColor + '; font-size:11px; margin-left:10px;">' + (data.verdict||'') + '</span>' +
            ' <span style="color:var(--muted); font-size:11px; font-weight:400; margin-left:8px;">' + (data.defect_type||'') + '</span>';

        // Meta strip
        const meta = document.getElementById('trace-meta');
        meta.innerHTML =
            metaChip('Confidence', (data.confidence||0) + '%', 'var(--steel)') +
            metaChip('Risk Level', data.risk_level || 'N/A', data.risk_level === 'CRITICAL' ? 'var(--fail)' : data.risk_level === 'HIGH' ? '#e67e22' : data.risk_level === 'LOW' ? 'var(--pass)' : 'var(--flag)') +
            (data.crs !== null ? metaChip('CRS', Number(data.crs).toFixed(1), 'var(--text)') : '') +
            metaChip('Steps', (data.agent_steps||[]).length, 'var(--gold)') +
            metaChip('Timestamp', (data.created_at||'').replace('T',' ').replace('+00:00',''), 'var(--muted)');
        meta.style.display = 'flex';

        // Steps
        const steps  = data.agent_steps || [];
        const stepsEl = document.getElementById('trace-steps');
        stepsEl.innerHTML = '';

        if (steps.length === 0) {
            stepsEl.innerHTML = '<div style="color:var(--muted); font-size:11px;">No agent steps recorded for this inspection.</div>';
        } else {
            steps.forEach((step, idx) => {
                const meta2  = TOOL_META[step.tool] || { label: step.tool.toUpperCase(), icon: '·' };
                const result = step.result || {};
                const isLast = idx === steps.length - 1;

                const body = renderStepBody(step.tool, result);
                const summary = getStepSummary(step.tool, result);

                stepsEl.innerHTML +=
                    '<div style="display:flex; gap:14px; margin-bottom:' + (isLast ? '0' : '16px') + ';">' +
                        '<div style="display:flex; flex-direction:column; align-items:center; flex-shrink:0;">' +
                            '<div style="width:28px; height:28px; border:1px solid var(--gold); background:rgba(8,8,8,0.6);' +
                                ' display:flex; align-items:center; justify-content:center; font-size:12px; color:var(--gold);">' +
                                meta2.icon +
                            '</div>' +
                            (isLast ? '' : '<div style="width:1px; flex:1; min-height:24px; background:rgba(255,255,255,0.06); margin:4px 0;"></div>') +
                        '</div>' +
                        '<div style="flex:1; min-width:0;" id="trs-' + idx + '">' +
                            '<div onclick="toggleTraceStep(' + idx + ')" style="cursor:pointer; padding:2px 0 6px; display:flex; align-items:center; justify-content:space-between;"' +
                                ' onmouseover="this.querySelector(\'.ts-title\').style.color=\'var(--gold)\'"' +
                                ' onmouseout="this.querySelector(\'.ts-title\').style.color=\'#e8e8e8\'">' +
                                '<div>' +
                                    '<div class="ts-title" style="font-size:10px; font-weight:700; letter-spacing:0.12em; color:#e8e8e8; text-transform:uppercase; transition:color 0.15s;">' +
                                        'STEP ' + (idx + 1) + ' — ' + meta2.label +
                                    '</div>' +
                                    '<div id="trs-sum-' + idx + '" style="font-size:10px; color:var(--muted); margin-top:2px; display:none;">' + summary + '</div>' +
                                '</div>' +
                                '<div id="trs-chev-' + idx + '" style="font-size:10px; color:var(--gold); opacity:0.6; transform:rotate(' + (idx===0?'90':'0') + 'deg); transition:transform 0.2s; margin-left:12px; flex-shrink:0;">&#9654;</div>' +
                            '</div>' +
                            '<div id="trs-body-' + idx + '" style="display:' + (idx===0?'block':'none') + ';">' +
                                body +
                            '</div>' +
                        '</div>' +
                    '</div>';
            });
        }

        document.getElementById('trace-spinner').style.display = 'none';
        stepsEl.style.display = 'block';
    }

    function metaChip(label, value, color) {
        return '<div style="font-size:10px;">' +
            '<span style="color:var(--muted); letter-spacing:0.08em;">' + label + ':</span> ' +
            '<span style="color:' + color + '; font-weight:700;">' + value + '</span>' +
        '</div>';
    }

    function getStepSummary(tool, r) {
        switch(tool) {
            case 'check_image_quality':        return (r.quality_status||'?') + ' · score ' + (r.quality_score||'?') + '/100';
            case 'run_yolo_scan':              return (r.count||0) + ' detection(s) · ' + (r.inference_ms||'?') + 'ms';
            case 'lookup_regulatory_context':  return r.applicable_standard || '21 CFR 820';
            case 'calculate_risk_score':       return 'RPN ' + ((r.formula_breakdown||{}).RPN||'?') + ' · ' + (r.risk_level||'?').toUpperCase();
            case 'finalize_inspection_report': return (r.verdict||'').toUpperCase() + ' · confidence ' + (r.confidence||'?') + '%';
            default: return '';
        }
    }

    function renderStepBody(tool, r) {
        const muted  = 'color:var(--muted);';
        const text   = 'color:var(--text);';
        const gold   = 'color:var(--gold);';
        const pass   = 'color:var(--pass);';
        const fail   = 'color:var(--fail);';
        const flag   = 'color:var(--flag);';
        const steel  = 'color:var(--steel);';
        const base   = 'font-size:11px; font-family:"JetBrains Mono",monospace;';

        if (tool === 'check_image_quality') {
            const qcolor = r.quality_status === 'OK' ? pass : flag;
            return '<div style="' + base + qcolor + ' font-weight:700; margin-bottom:4px;">STATUS: ' + (r.quality_status||'?') + ' · SCORE: ' + (r.quality_score||'?') + '/100</div>' +
                   '<div style="' + base + muted + '">' + (r.reason||'') + '</div>' +
                   (r.width ? '<div style="font-size:10px;' + muted + ' margin-top:3px; opacity:0.6;">' + r.width + 'x' + r.height + 'px · ' + r.file_size_kb + 'KB</div>' : '');
        }

        if (tool === 'run_yolo_scan') {
            const dets = r.detections || [];
            if (dets.length === 0) return '<div style="' + base + muted + '">' + (r.summary||'No detections above confidence threshold.') + '</div>';
            let html = '<div style="' + base + text + ' margin-bottom:6px;">' + (r.count||0) + ' detection(s) · ' + (r.inference_ms||'?') + 'ms</div>';
            dets.forEach(d => {
                const sc = d.severity === 'high' ? fail : d.severity === 'medium' ? flag : pass;
                html += '<div style="display:flex; gap:10px; ' + base + muted + ' margin-bottom:3px;">' +
                    '<span style="' + steel + ' font-weight:600;">' + (d.class_name||'') + '</span>' +
                    '<span>' + Math.round((d.confidence||0)*100) + '% conf</span>' +
                    '<span style="' + sc + '">' + (d.severity||'').toUpperCase() + '</span>' +
                    '</div>';
            });
            return html;
        }

        if (tool === 'lookup_regulatory_context') {
            const sr = r.sterilization_risk ? fail : pass;
            return '<div style="' + base + steel + ' font-weight:600; margin-bottom:4px;">' + (r.applicable_standard||'') + '</div>' +
                '<div style="display:flex; gap:16px; ' + base + muted + ' margin-bottom:4px; flex-wrap:wrap;">' +
                '<span>Severity: <strong style="' + text + '">' + (r.severity_baseline||'—') + '/10</strong></span>' +
                '<span>Recall prior: <strong style="' + text + '">' + (r.recall_prior ? Math.round(r.recall_prior*100) + '%' : '—') + '</strong></span>' +
                '<span>Sterilization risk: <strong style="' + sr + '">' + (r.sterilization_risk ? 'YES' : 'NO') + '</strong></span>' +
                '</div>' +
                (r.clinical_consequence ? '<div style="font-size:10px;' + muted + ' font-style:italic;">' + r.clinical_consequence + '</div>' : '');
        }

        if (tool === 'calculate_risk_score') {
            const fb = r.formula_breakdown || {};
            const trend = (r.historical_context||{}).trend || '—';
            const tc = trend === 'WORSENING' ? fail : trend === 'IMPROVING' ? pass : muted;
            return '<div style="background:rgba(8,8,8,0.6); border:1px solid rgba(255,255,255,0.06); padding:10px 14px; ' + base + text + ' margin-bottom:8px;">' +
                '<span style="' + gold + '">' + (fb.formula||'') + '</span>' +
                '</div>' +
                '<div style="display:flex; gap:16px; ' + base + muted + ' flex-wrap:wrap;">' +
                '<span>S: <strong style="' + text + '">' + (fb.S_severity||'—') + '</strong></span>' +
                '<span>O: <strong style="' + text + '">' + (fb.O_occurrence||'—') + '</strong></span>' +
                '<span>D: <strong style="' + text + '">' + (fb.D_detection||'—') + '</strong></span>' +
                '<span>RPN: <strong style="' + text + '">' + (fb.RPN||'—') + '</strong></span>' +
                '<span>Trend: <strong style="' + tc + '">' + trend + '</strong></span>' +
                '</div>';
        }

        if (tool === 'finalize_inspection_report') {
            return '<div style="' + base + pass + '">Report finalized and persisted to audit log.</div>';
        }

        return '<div style="' + base + muted + ' word-break:break-all;">' + JSON.stringify(r).substring(0, 300) + '</div>';
    }

    function toggleTraceStep(idx) {
        const body  = document.getElementById('trs-body-' + idx);
        const chev  = document.getElementById('trs-chev-' + idx);
        const sum   = document.getElementById('trs-sum-'  + idx);
        if (!body) return;
        const open = body.style.display === 'none';
        body.style.display = open ? 'block' : 'none';
        chev.style.transform = open ? 'rotate(90deg)' : 'rotate(0deg)';
        sum.style.display    = open ? 'none' : 'block';
    }

    function closeTrace(e) {
        if (e.target === document.getElementById('trace-overlay')) closeTraceBtn();
    }

    function closeTraceBtn() {
        document.getElementById('trace-overlay').style.display = 'none';
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeTraceBtn(); });
    </script>

</x-app-layout>
