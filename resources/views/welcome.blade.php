<x-app-layout>
    <x-slot name="header">
        <div style="display:flex; align-items:center; justify-content:space-between; width:100%;">
            <div>
                <div style="font-size:9px; letter-spacing:0.18em; color:var(--gold); text-transform:uppercase; font-weight:600; margin-bottom:4px;">
                    [ COMMAND CENTER ]
                </div>
                <h2 style="font-size:16px; font-weight:700; color:var(--text); letter-spacing:0.04em;">
                    COSMAS · DAMIAN
                    <span style="color:var(--muted); font-weight:400; font-size:11px; margin-left:14px; letter-spacing:0.06em;">
                        AI Surgical Instrument Quality Control
                    </span>
                </h2>
            </div>
            <a href="{{ route('inspections.upload') }}"
               style="padding:10px 24px; background:var(--gold); color:var(--bg); font-size:11px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; text-decoration:none;">
                + NEW INSPECTION
            </a>
        </div>
    </x-slot>

    @php
        $stats = \App\Models\Inspection::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN pass_fail = 'PASS' THEN 1 ELSE 0 END) as passed,
            SUM(CASE WHEN pass_fail = 'FAIL' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN pass_fail = 'FLAGGED' THEN 1 ELSE 0 END) as flagged,
            ROUND(AVG(CAST(confidence AS FLOAT)), 1) as avg_conf
        ")->first();
        $recent = \App\Models\Inspection::latest()->limit(4)->get();
    @endphp

    {{-- ── QUICK ACTIONS ────────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px;">

        <a href="{{ route('inspections.upload') }}" style="text-decoration:none;">
            <div style="background:var(--card); border:1px solid var(--gold); padding:24px 22px; display:flex; align-items:center; gap:16px; transition:background 0.15s; cursor:pointer;"
                 onmouseover="this.style.background='rgba(201,150,62,0.08)'" onmouseout="this.style.background='var(--card)'">
                <div style="font-size:28px; color:var(--gold); flex-shrink:0;">◎</div>
                <div>
                    <div style="font-size:12px; font-weight:700; letter-spacing:0.1em; color:var(--gold); text-transform:uppercase; margin-bottom:4px;">INSPECT</div>
                    <div style="font-size:11px; color:var(--muted); line-height:1.4;">Upload a surgical instrument image and launch the AI inspection pipeline.</div>
                </div>
            </div>
        </a>

        <a href="{{ route('dashboard') }}" style="text-decoration:none;">
            <div style="background:var(--card); border:1px solid var(--gold-dim); padding:24px 22px; display:flex; align-items:center; gap:16px; cursor:pointer;"
                 onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor='var(--gold-dim)'">
                <div style="font-size:28px; color:var(--steel); flex-shrink:0;">▣</div>
                <div>
                    <div style="font-size:12px; font-weight:700; letter-spacing:0.1em; color:var(--text); text-transform:uppercase; margin-bottom:4px;">DASHBOARD</div>
                    <div style="font-size:11px; color:var(--muted); line-height:1.4;">KPI metrics, 14-day trend chart, ROI calculator, defect breakdown.</div>
                </div>
            </div>
        </a>

        <a href="{{ route('inspections.audit-log') }}" style="text-decoration:none;">
            <div style="background:var(--card); border:1px solid var(--gold-dim); padding:24px 22px; display:flex; align-items:center; gap:16px; cursor:pointer;"
                 onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor='var(--gold-dim)'">
                <div style="font-size:28px; color:var(--steel); flex-shrink:0;">≡</div>
                <div>
                    <div style="font-size:12px; font-weight:700; letter-spacing:0.1em; color:var(--text); text-transform:uppercase; margin-bottom:4px;">AUDIT LOG</div>
                    <div style="font-size:11px; color:var(--muted); line-height:1.4;">FDA 21 CFR Part 820 append-only record. Every inspection, immutably logged.</div>
                </div>
            </div>
        </a>

    </div>

    <div style="display:grid; grid-template-columns:1fr 300px; gap:16px; align-items:start;">

        {{-- ── LEFT ─────────────────────────────────────────────────── --}}
        <div style="display:flex; flex-direction:column; gap:16px;">

            {{-- PIPELINE ──────────────────────────────────────────── --}}
            <div style="background:var(--card); border:1px solid var(--gold-dim);">
                <div style="padding:12px 20px; border-bottom:1px solid var(--gold-dim);">
                    <span style="font-size:9px; letter-spacing:0.16em; color:var(--gold); font-weight:700; text-transform:uppercase;">[ 5-STEP INSPECTION PIPELINE ]</span>
                </div>
                <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:0;">
                    @php
                        $steps = [
                            ['num'=>'01','icon'=>'◈','name'=>"IMAGE\nQUALITY",'desc'=>'Resolution, focus & framing validation'],
                            ['num'=>'02','icon'=>'◎','name'=>"YOLOV8S\nSCAN",'desc'=>'Fine-tuned defect detection at 640px'],
                            ['num'=>'03','icon'=>'⊞','name'=>"REGULATORY\nCONTEXT",'desc'=>'FDA 21 CFR, ISO 7153-1, ASTM lookup'],
                            ['num'=>'04','icon'=>'∑','name'=>"FMEA\nRISK SCORE",'desc'=>'CRS = S×O×D × P(recall) × trend'],
                            ['num'=>'05','icon'=>'✓','name'=>"FINAL\nREPORT",'desc'=>'Verdict, reasoning, audit log entry'],
                        ];
                    @endphp
                    @foreach($steps as $i => $step)
                    <div style="padding:18px 14px; border-right:{{ $i < 4 ? '1px solid var(--gold-dim)' : 'none' }}; position:relative;">
                        <div style="font-size:9px; letter-spacing:0.14em; color:var(--muted); margin-bottom:8px;">{{ $step['num'] }}</div>
                        <div style="font-size:18px; color:var(--gold); margin-bottom:8px;">{{ $step['icon'] }}</div>
                        <div style="font-size:10px; font-weight:700; letter-spacing:0.08em; color:var(--text); text-transform:uppercase; margin-bottom:6px; white-space:pre-line;">{{ $step['name'] }}</div>
                        <div style="font-size:10px; color:var(--muted); line-height:1.5;">{{ $step['desc'] }}</div>
                        @if($i < 4)
                        <div style="position:absolute; right:-8px; top:50%; transform:translateY(-50%); font-size:12px; color:var(--gold-dim); z-index:1;">›</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- RECENT INSPECTIONS ─────────────────────────────────── --}}
            <div style="background:var(--card); border:1px solid var(--gold-dim);">
                <div style="padding:12px 20px; border-bottom:1px solid var(--gold-dim); display:flex; align-items:center; justify-content:space-between;">
                    <span style="font-size:9px; letter-spacing:0.16em; color:var(--gold); font-weight:700; text-transform:uppercase;">[ RECENT INSPECTIONS ]</span>
                    <a href="{{ route('inspections.audit-log') }}"
                       style="font-size:9px; letter-spacing:0.1em; color:var(--muted); text-decoration:none; text-transform:uppercase;">VIEW ALL →</a>
                </div>
                @forelse($recent as $ins)
                @php
                    $pf = strtoupper($ins->pass_fail ?? '');
                    $vc = $pf === 'PASS' ? 'var(--pass)' : ($pf === 'FAIL' ? 'var(--fail)' : 'var(--flag)');
                @endphp
                <div style="display:flex; align-items:center; gap:16px; padding:12px 20px; border-bottom:1px solid rgba(201,150,62,0.08);">
                    <span style="font-size:10px; color:var(--muted); width:32px; flex-shrink:0;">#{{ $ins->id }}</span>
                    <span style="font-size:10px; font-weight:700; color:{{ $vc }}; width:52px; flex-shrink:0; letter-spacing:0.08em;">{{ $pf }}</span>
                    <span style="font-size:11px; color:var(--text); flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $ins->defect_type ?? 'No Defect' }}
                    </span>
                    <span style="font-size:10px; color:var(--muted); flex-shrink:0;">{{ $ins->confidence ?? 0 }}%</span>
                    <span style="font-size:10px; color:var(--muted); flex-shrink:0;">{{ $ins->created_at->format('M d H:i') }}</span>
                    <a href="{{ route('inspections.results', $ins->id) }}"
                       style="font-size:9px; letter-spacing:0.1em; color:var(--gold); text-decoration:none; flex-shrink:0;">VIEW</a>
                </div>
                @empty
                <div style="padding:24px 20px; font-size:11px; color:var(--muted);">
                    No inspections yet. <a href="{{ route('inspections.upload') }}" style="color:var(--gold);">Start your first →</a>
                </div>
                @endforelse
            </div>

        </div>

        {{-- ── RIGHT ─────────────────────────────────────────────────── --}}
        <div style="display:flex; flex-direction:column; gap:12px;">

            {{-- SYSTEM STATUS ──────────────────────────────────────── --}}
            <div style="background:var(--card); border:1px solid var(--gold-dim); padding:16px;">
                <div class="tac-label" style="margin-bottom:12px;">SYSTEM STATUS</div>
                @php
                    $statusItems = [
                        ['label'=>'AI MODEL',    'status'=>'READY',      'color'=>'var(--pass)'],
                        ['label'=>'YOLO SERVICE','status'=>'ONLINE',     'color'=>'var(--pass)'],
                        ['label'=>'AUDIT LOG',   'status'=>'LOGGING',    'color'=>'var(--pass)'],
                        ['label'=>'FDA COMPLIANCE','status'=>'21 CFR 820','color'=>'var(--steel)'],
                    ];
                @endphp
                @foreach($statusItems as $item)
                <div style="display:flex; align-items:center; justify-content:space-between; padding:6px 0; border-bottom:1px solid rgba(201,150,62,0.06);">
                    <span style="font-size:10px; color:var(--muted); letter-spacing:0.08em;">{{ $item['label'] }}</span>
                    <span style="font-size:10px; font-weight:700; color:{{ $item['color'] }}; letter-spacing:0.08em;">● {{ $item['status'] }}</span>
                </div>
                @endforeach
            </div>

            {{-- LIVE STATS ─────────────────────────────────────────── --}}
            <div style="background:var(--card); border:1px solid var(--gold-dim); padding:16px;">
                <div class="tac-label" style="margin-bottom:12px;">SESSION STATS</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div style="background:var(--navy); padding:12px 10px; text-align:center;">
                        <div style="font-size:22px; font-weight:700; color:var(--text);">{{ $stats->total ?? 0 }}</div>
                        <div style="font-size:9px; letter-spacing:0.1em; color:var(--muted); text-transform:uppercase; margin-top:2px;">Total</div>
                    </div>
                    <div style="background:var(--navy); padding:12px 10px; text-align:center;">
                        <div style="font-size:22px; font-weight:700; color:var(--pass);">{{ $stats->passed ?? 0 }}</div>
                        <div style="font-size:9px; letter-spacing:0.1em; color:var(--muted); text-transform:uppercase; margin-top:2px;">Passed</div>
                    </div>
                    <div style="background:var(--navy); padding:12px 10px; text-align:center;">
                        <div style="font-size:22px; font-weight:700; color:var(--fail);">{{ $stats->failed ?? 0 }}</div>
                        <div style="font-size:9px; letter-spacing:0.1em; color:var(--muted); text-transform:uppercase; margin-top:2px;">Failed</div>
                    </div>
                    <div style="background:var(--navy); padding:12px 10px; text-align:center;">
                        <div style="font-size:22px; font-weight:700; color:var(--steel);">{{ $stats->avg_conf ?? '—' }}{{ $stats->avg_conf ? '%' : '' }}</div>
                        <div style="font-size:9px; letter-spacing:0.1em; color:var(--muted); text-transform:uppercase; margin-top:2px;">Avg Conf</div>
                    </div>
                </div>
            </div>

            {{-- ROI SNAPSHOT ───────────────────────────────────────── --}}
            <div style="background:var(--card); border:1px solid var(--gold-dim); padding:16px;">
                <div class="tac-label" style="margin-bottom:10px;">ROI SNAPSHOT</div>
                <div style="font-size:11px; color:var(--muted); line-height:1.7; margin-bottom:10px;">
                    <div>Manual: <span style="color:var(--text);">$0.15/inspection</span></div>
                    <div>AI cost: <span style="color:var(--text);">$0.01/inspection</span></div>
                    <div>Savings: <span style="color:var(--pass); font-weight:700;">$0.14/unit</span></div>
                </div>
                <div style="background:var(--navy); padding:10px 12px; margin-bottom:10px;">
                    <div style="font-size:9px; color:var(--muted); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:4px;">At 10,000/mo scale</div>
                    <div style="font-size:16px; font-weight:700; color:var(--gold);">$16,800 / yr</div>
                </div>
                <a href="{{ route('dashboard') }}"
                   style="display:block; text-align:center; padding:8px; border:1px solid var(--gold-dim); font-size:10px; letter-spacing:0.12em; color:var(--muted); text-decoration:none; text-transform:uppercase;">
                    FULL DASHBOARD →
                </a>
            </div>

        </div>

    </div>

</x-app-layout>
