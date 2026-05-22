<x-app-layout>
    <x-slot name="header">
        <span style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase;
              background:linear-gradient(135deg,#f2f2f2 0%,#c8c8c8 30%,#f0f0f0 50%,#909090 100%);
              -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">[ COMMAND CENTER ]</span>
        <a href="{{ route('inspections.upload') }}" style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; letter-spacing:0.1em; color:#080808; background:#C9963E; padding:8px 18px; text-decoration:none; text-transform:uppercase;">+ NEW INSPECTION</a>
    </x-slot>

    {{-- Analytics Scope Filter --}}
    <div style="display:flex; align-items:center; gap:8px; padding:12px 20px; margin-bottom:1px;
                background:var(--card); border:1px solid rgba(255,255,255,0.06);">
        <span style="font-family:'JetBrains Mono',monospace; font-size:9px; font-weight:700;
                     letter-spacing:0.14em; text-transform:uppercase; color:var(--muted); margin-right:6px;">ANALYTICS SCOPE:</span>
        @foreach(['both'=>'ALL INSPECTIONS','single'=>'SINGLE UPLOADS','batch'=>'BATCH UPLOADS'] as $mVal=>$mLbl)
        @php $mActive = ($mode === $mVal); @endphp
        <a href="{{ request()->fullUrlWithQuery(['mode'=>$mVal]) }}"
           style="font-family:'JetBrains Mono',monospace; font-size:9px; font-weight:700;
                  letter-spacing:0.1em; text-transform:uppercase; text-decoration:none; padding:5px 14px;
                  {{ $mActive ? 'color:#080808; background:var(--gold); border:1px solid var(--gold);' : 'color:var(--ch-mid); background:transparent; border:1px solid rgba(255,255,255,0.25);' }}"
           @if(!$mActive)
           onmouseover="this.style.borderColor='rgba(201,150,62,0.4)'; this.style.color='var(--gold)'"
           onmouseout="this.style.borderColor='rgba(255,255,255,0.25)'; this.style.color='var(--ch-mid)'"
           @endif>
            {{ $mLbl }}
        </a>
        @endforeach
    </div>

    {{-- KPI Strip --}}
    <div style="display:grid; grid-template-columns:repeat(6,1fr); gap:1px; background:rgba(255,255,255,0.04); margin-bottom:1px;">
        <div class="tac-card"><div class="tac-label">Total Inspections</div><div class="tac-value-chrome">{{ $total }}</div><div class="tac-sub">All time</div></div>
        <div class="tac-card"><div class="tac-label">Pass Rate</div><div class="tac-value" style="color:{{ $passRate>=80?'var(--pass)':'var(--flag)' }};">{{ $passRate }}%</div><div class="tac-sub">{{ $passed }} passed</div></div>
        <div class="tac-card"><div class="tac-label">Cost Saved</div><div class="tac-value" style="color:var(--gold);">${{ $costSaved }}</div><div class="tac-sub">vs manual @ $0.15/ea</div></div>
        <div class="tac-card"><div class="tac-label">Flagged</div><div class="tac-value" style="color:var(--flag);">{{ $flagged }}</div><div class="tac-sub">Needs review</div></div>
        <div class="tac-card"><div class="tac-label">Rejected</div><div class="tac-value" style="color:var(--fail);">{{ $failed }}</div><div class="tac-sub">Defects caught</div></div>
        <div class="tac-card"><div class="tac-label">Avg Confidence</div><div class="tac-value" style="color:var(--steel);">@if($avgConfidence!==null){{ $avgConfidence }}%@else&mdash;@endif</div><div class="tac-sub">Model certainty</div></div>
    </div>

    {{-- ROI Banner --}}
    <div style="background:var(--card); border:1px solid rgba(255,255,255,0.07); border-left:3px solid var(--gold); padding:18px 24px; margin-bottom:1px; display:flex; align-items:center; justify-content:space-between; position:relative; overflow:hidden;">
        <div style="position:absolute; inset:0; pointer-events:none; background:
            repeating-linear-gradient(0deg, rgba(255,255,255,0.01) 0px, rgba(255,255,255,0.01) 1px, transparent 1px, transparent 16px),
            repeating-linear-gradient(90deg, rgba(255,255,255,0.01) 0px, rgba(255,255,255,0.01) 1px, transparent 1px, transparent 16px);"></div>
        <div style="position:relative; z-index:1;">
            <div class="tac-label" style="margin-bottom:6px;">[ ROI VS. MANUAL INSPECTION ]</div>
            <div style="color:var(--muted); font-size:12px; line-height:1.8;">
                Manual: <span style="color:var(--text);">$0.15/inspection</span> &nbsp;&middot;&nbsp;
                AI: <span style="color:var(--text);">$0.01/inspection</span> &nbsp;&middot;&nbsp;
                <span style="color:var(--gold); font-weight:700;">$0.14 saved per unit</span>
            </div>
            <div style="color:var(--muted); font-size:12px;">
                10,000 inspections/month &rarr; <span style="color:var(--pass); font-weight:700;">$1,400/mo &middot; $16,800/yr</span>
                &nbsp;&middot;&nbsp; Industry benchmark: <span style="color:var(--gold);">374% 3-yr ROI</span> (Forrester)
            </div>
        </div>
        <div style="text-align:right; flex-shrink:0; padding-left:32px; position:relative; z-index:1;">
            <div style="font-size:36px; font-weight:700; color:var(--gold); line-height:1;">${{ $costSaved }}</div>
            <div class="tac-sub">saved so far</div>
        </div>
    </div>

    {{-- Main Grid --}}
    <div style="display:grid; grid-template-columns:1fr 380px; gap:1px; background:rgba(255,255,255,0.04); margin-bottom:1px;">

        {{-- LEFT: Recent Inspections + Defect Intelligence --}}
        <div class="tac-card" style="padding:0; overflow:hidden; display:flex; flex-direction:column;">

            {{-- Recent inspections header --}}
            <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid rgba(255,255,255,0.06); position:relative; z-index:1; flex-shrink:0;">
                <span class="sec-label">RECENT INSPECTIONS</span>
                <a href="{{ route('inspections.audit-log') }}" style="font-size:10px; color:var(--gold); text-decoration:none; letter-spacing:0.08em; font-weight:600;">VIEW ALL &rarr;</a>
            </div>

            @if($recent->count())
            <table style="flex-shrink:0;">
                <thead><tr>
                    <th style="width:60px;">ID</th>
                    <th style="width:100px;">STATUS</th>
                    <th>DEFECT / INSTRUMENT</th>
                    <th style="width:90px;">CONF.</th>
                    <th style="width:120px;">TIMESTAMP</th>
                    <th style="width:60px;"></th>
                </tr></thead>
                <tbody>
                    @foreach($recent as $insp)
                    @php $pf=strtoupper($insp->pass_fail??''); @endphp
                    <tr>
                        <td style="color:var(--muted); font-size:11px;">#{{ $insp->id }}</td>
                        <td>
                            @if($pf==='PASS') <span class="badge-pass">PASS</span>
                            @elseif($pf==='FAIL') <span class="badge-fail">FAIL</span>
                            @elseif($pf==='FLAGGED') <span class="badge-flag">FLAG</span>
                            @else <span class="badge-grey">{{ $insp->pass_fail }}</span>
                            @endif
                        </td>
                        <td style="color:var(--muted); max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px;">{{ $insp->defect_type??'&mdash;' }}</td>
                        <td style="color:var(--steel); font-size:12px;">@php $cd=is_numeric($insp->confidence)&&$insp->confidence>0?($insp->confidence<=1?round($insp->confidence*100):round($insp->confidence)):null; @endphp{{ $cd!==null?$cd.'%':'&mdash;' }}</td>
                        <td style="color:var(--muted); font-size:11px;">{{ $insp->created_at->format('M d &middot; H:i') }}</td>
                        <td><a href="{{ route('inspections.results',$insp->id) }}" style="color:var(--gold); text-decoration:none; font-size:10px; font-weight:700; letter-spacing:0.08em;">VIEW</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div style="padding:32px; text-align:center; color:var(--muted); position:relative; z-index:1; flex-shrink:0;">
                <div style="font-size:28px; opacity:0.15; margin-bottom:10px;">o</div>
                <div style="margin-bottom:8px;">No inspections yet.</div>
                <a href="{{ route('inspections.upload') }}" style="color:var(--gold); text-decoration:none; font-size:11px; font-weight:700;">UPLOAD YOUR FIRST IMAGE &rarr;</a>
            </div>
            @endif

            {{-- ── DEFECT INTELLIGENCE ──────────────────────────────────────── --}}
            <div style="border-top:1px solid rgba(255,255,255,0.06); flex:1; display:grid; grid-template-columns:1fr 1fr; gap:0;">

                {{-- Top Defect Types --}}
                <div style="padding:16px 20px; border-right:1px solid rgba(255,255,255,0.06);">
                    <div style="font-size:9px; font-weight:700; letter-spacing:0.16em; text-transform:uppercase;
                                color:var(--gold); margin-bottom:14px;">TOP DEFECT TYPES</div>

                    @if($defectBreakdown->count())
                    @php $maxCnt = $defectBreakdown->max('cnt'); @endphp
                    <div style="display:flex; flex-direction:column; gap:9px;">
                        @foreach($defectBreakdown as $row)
                        @php
                            $pct = $maxCnt > 0 ? round(($row->cnt / $maxCnt) * 100) : 0;
                            $label = strlen($row->defect_type) > 26
                                ? substr($row->defect_type, 0, 24) . '..'
                                : $row->defect_type;
                        @endphp
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
                                <span style="font-size:10px; color:var(--text); letter-spacing:0.04em;">{{ $label }}</span>
                                <span style="font-size:10px; color:var(--muted); font-weight:700;">{{ $row->cnt }}</span>
                            </div>
                            <div style="height:3px; background:rgba(255,255,255,0.06); border-radius:1px; overflow:hidden;">
                                <div style="height:100%; width:{{ $pct }}%; background:var(--gold); border-radius:1px;
                                            transition:width 0.6s ease; opacity:0.8;"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div style="font-size:11px; color:var(--muted); opacity:0.5;">No defect data yet.</div>
                    @endif
                </div>

                {{-- Risk Distribution --}}
                <div style="padding:16px 20px;">
                    <div style="font-size:9px; font-weight:700; letter-spacing:0.16em; text-transform:uppercase;
                                color:var(--steel); margin-bottom:14px;">RISK DISTRIBUTION</div>

                    @if($riskBreakdown->count())
                    @php
                        $riskTotal = $riskBreakdown->sum('cnt');
                        $riskColors = [
                            'CRITICAL' => ['bar'=>'var(--fail)',  'text'=>'var(--fail)'],
                            'HIGH'     => ['bar'=>'#E67E22',      'text'=>'#E67E22'],
                            'MEDIUM'   => ['bar'=>'var(--flag)',  'text'=>'var(--flag)'],
                            'LOW'      => ['bar'=>'var(--pass)',  'text'=>'var(--pass)'],
                        ];
                    @endphp
                    <div style="display:flex; flex-direction:column; gap:9px;">
                        @foreach($riskBreakdown as $row)
                        @php
                            $rl  = strtoupper($row->risk_level ?? '');
                            $clr = $riskColors[$rl] ?? ['bar'=>'var(--muted)','text'=>'var(--muted)'];
                            $pct = $riskTotal > 0 ? round(($row->cnt / $riskTotal) * 100) : 0;
                        @endphp
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
                                <span style="font-size:10px; font-weight:700; letter-spacing:0.08em; color:{{ $clr['text'] }};">{{ $rl }}</span>
                                <span style="font-size:10px; color:var(--muted);">{{ $row->cnt }} &nbsp;<span style="opacity:0.5;">{{ $pct }}%</span></span>
                            </div>
                            <div style="height:3px; background:rgba(255,255,255,0.06); border-radius:1px; overflow:hidden;">
                                <div style="height:100%; width:{{ $pct }}%; background:{{ $clr['bar'] }}; border-radius:1px; opacity:0.75;"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Pass/Fail/Flag pill summary --}}
                    <div style="display:flex; gap:8px; margin-top:16px; flex-wrap:wrap;">
                        <div style="display:flex; align-items:center; gap:5px; padding:4px 8px;
                                    background:rgba(74,207,130,0.08); border:1px solid rgba(74,207,130,0.2);">
                            <div style="width:6px; height:6px; border-radius:50%; background:var(--pass);"></div>
                            <span style="font-size:9px; color:var(--pass); font-weight:700; letter-spacing:0.08em;">{{ $passed }} PASS</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:5px; padding:4px 8px;
                                    background:rgba(224,85,85,0.08); border:1px solid rgba(224,85,85,0.2);">
                            <div style="width:6px; height:6px; border-radius:50%; background:var(--fail);"></div>
                            <span style="font-size:9px; color:var(--fail); font-weight:700; letter-spacing:0.08em;">{{ $failed }} FAIL</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:5px; padding:4px 8px;
                                    background:rgba(243,156,18,0.08); border:1px solid rgba(243,156,18,0.2);">
                            <div style="width:6px; height:6px; border-radius:50%; background:var(--flag);"></div>
                            <span style="font-size:9px; color:var(--flag); font-weight:700; letter-spacing:0.08em;">{{ $flagged }} FLAG</span>
                        </div>
                    </div>
                    @else
                    <div style="font-size:11px; color:var(--muted); opacity:0.5;">No risk data yet.</div>
                    @endif
                </div>

            </div>{{-- end defect intelligence --}}
        </div>

        {{-- RIGHT: 14-day trend --}}
        <div class="tac-card">
            <div class="tac-label" style="margin-bottom:16px; position:relative; z-index:1;">[ 14-DAY TREND ]</div>
            <canvas id="trendChart" height="200" style="position:relative; z-index:1;"></canvas>
            <div style="display:flex; gap:16px; margin-top:12px; justify-content:center; position:relative; z-index:1;">
                <span style="font-size:10px; color:var(--pass); display:flex; align-items:center; gap:4px;"><span style="display:inline-block;width:10px;height:3px;background:var(--pass);"></span>PASS</span>
                <span style="font-size:10px; color:var(--fail); display:flex; align-items:center; gap:4px;"><span style="display:inline-block;width:10px;height:3px;background:var(--fail);"></span>FAIL</span>
                <span style="font-size:10px; color:var(--flag); display:flex; align-items:center; gap:4px;"><span style="display:inline-block;width:10px;height:3px;background:var(--flag);"></span>FLAG</span>
            </div>
        </div>
    </div>

    {{-- Pipeline Explainer --}}
    <div class="tac-card">
        <div class="tac-label" style="margin-bottom:20px; position:relative; z-index:1;">[ AI INSPECTION PIPELINE ]</div>
        <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:1px; background:rgba(255,255,255,0.04); position:relative; z-index:1;">
            @foreach([
                ['num'=>'01','title'=>'IMAGE QUALITY',     'desc'=>'Resolution, sharpness & framing validated via pixel-variance scoring'],
                ['num'=>'02','title'=>'YOLOv8 SCAN',       'desc'=>'Two-stage CV: instrument classifier (6 classes) → defect detector, mAP50 0.764'],
                ['num'=>'03','title'=>'REGULATORY CONTEXT','desc'=>'FDA 21 CFR, ISO 7153-1, ASTM lookup — severity, recall prior, sterilization risk'],
                ['num'=>'04','title'=>'FMEA RISK SCORE',   'desc'=>'CRS = S×O×D × P(recall|defect) × trend weight — CRITICAL overrides to FAIL'],
                ['num'=>'05','title'=>'AUDIT LOG ENTRY',   'desc'=>'Immutable DHR record per 21 CFR 820.184 — PASS / FAIL / FLAGGED verdict stored'],
            ] as $step)
            <div style="background:var(--card); padding:20px; text-align:center; position:relative; overflow:hidden;">
                <div style="position:absolute; inset:0; pointer-events:none; background:
                    repeating-linear-gradient(0deg, rgba(255,255,255,0.01) 0px, rgba(255,255,255,0.01) 1px, transparent 1px, transparent 16px),
                    repeating-linear-gradient(90deg, rgba(255,255,255,0.01) 0px, rgba(255,255,255,0.01) 1px, transparent 1px, transparent 16px);"></div>
                <div style="font-size:28px; font-weight:700; margin-bottom:10px; position:relative; z-index:1;
                    background:linear-gradient(135deg,rgba(201,150,62,0.6) 0%,rgba(201,150,62,0.3) 100%);
                    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">{{ $step['num'] }}</div>
                <div style="color:var(--text); font-size:12px; font-weight:600; margin-bottom:6px; letter-spacing:0.06em; position:relative; z-index:1;">{{ $step['title'] }}</div>
                <div style="color:var(--muted); font-size:11px; line-height:1.5; position:relative; z-index:1;">{{ $step['desc'] }}</div>
            </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    (function(){
        new Chart(document.getElementById('trendChart'),{
            type:'bar',
            data:{
                labels:@json($trendLabels),
                datasets:[
                    {label:'PASS',   data:@json($trendPass),    backgroundColor:'rgba(74,207,130,0.65)', borderColor:'#4CAF82',borderWidth:1},
                    {label:'FAIL',   data:@json($trendFail),    backgroundColor:'rgba(224,85,85,0.65)',  borderColor:'#E05555',borderWidth:1},
                    {label:'FLAGGED',data:@json($trendFlagged), backgroundColor:'rgba(243,156,18,0.65)', borderColor:'#F39C12',borderWidth:1},
                ]
            },
            options:{
                responsive:true,
                plugins:{legend:{display:false},tooltip:{backgroundColor:'#121212',borderColor:'rgba(255,255,255,0.1)',borderWidth:1,titleColor:'#C9963E',bodyColor:'#E8EDF2',titleFont:{family:'JetBrains Mono',size:11},bodyFont:{family:'JetBrains Mono',size:11}}},
                scales:{
                    x:{stacked:true,grid:{display:false},ticks:{color:'#8A9BAE',font:{family:'JetBrains Mono',size:10}},border:{color:'rgba(255,255,255,0.08)'}},
                    y:{stacked:true,beginAtZero:true,ticks:{stepSize:1,color:'#8A9BAE',font:{family:'JetBrains Mono',size:10}},grid:{color:'rgba(255,255,255,0.05)'},border:{color:'rgba(255,255,255,0.08)'}}
                }
            }
        });
    })();
    </script>
    @endpush
</x-app-layout>
