<x-app-layout>
    <x-slot name="header">
        <div style="display:flex; align-items:center; justify-content:space-between; width:100%;">
            <div>
                <div style="font-size:9px; letter-spacing:0.18em; color:var(--gold); text-transform:uppercase; font-weight:600; margin-bottom:4px;">
                    [ INSPECTION REPORT ]
                </div>
                <h2 style="font-size:16px; font-weight:700; color:var(--text); letter-spacing:0.04em;">
                    Inspection #{{ $inspection->id }}
                    <span style="color:var(--muted); font-weight:400; font-size:12px; margin-left:12px;">
                        {{ $inspection->created_at->format('Y-m-d H:i:s') }} UTC
                    </span>
                </h2>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="{{ route('inspections.upload') }}"
                   style="padding:8px 18px; background:var(--gold); color:var(--bg); font-size:11px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; text-decoration:none;">
                    + NEW INSPECTION
                </a>
                <a href="{{ route('inspections.audit-log') }}"
                   style="padding:8px 18px; border:1px solid var(--gold-dim); color:var(--muted); font-size:11px; font-weight:600; letter-spacing:0.1em; text-transform:uppercase; text-decoration:none;">
                    AUDIT LOG
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $verdict    = strtoupper($inspection->pass_fail ?? 'FAIL');
        $riskLevel  = strtoupper($inspection->risk_level ?? 'UNKNOWN');
        $crs        = $inspection->composite_risk_score;
        $agentSteps = $inspection->agent_steps_decoded ?? [];
        $costMatrix = $inspection->cost_matrix_decoded ?? null;
        $yoloDets   = $inspection->yolo_detections_decoded ?? [];

        $verdictColor = match($verdict) {
            'PASS'    => 'var(--pass)',
            'FAIL'    => 'var(--fail)',
            'FLAGGED' => 'var(--flag)',
            default   => 'var(--muted)',
        };
        $riskColor = match($riskLevel) {
            'CRITICAL' => 'var(--fail)',
            'HIGH'     => '#E67E22',
            'MEDIUM'   => 'var(--flag)',
            'LOW'      => 'var(--pass)',
            default    => 'var(--muted)',
        };

        // Tool display names + icons
        $toolMeta = [
            'check_image_quality'       => ['label' => 'IMAGE QUALITY CHECK',       'icon' => '◈'],
            'run_yolo_scan'             => ['label' => 'YOLOV8S VISION SCAN',        'icon' => '◎'],
            'lookup_regulatory_context' => ['label' => 'REGULATORY CONTEXT LOOKUP',  'icon' => '⊞'],
            'calculate_risk_score'      => ['label' => 'COMPOSITE RISK SCORE (FMEA)','icon' => '∑'],
            'finalize_inspection_report'=> ['label' => 'FINAL REPORT GENERATED',     'icon' => '✓'],
        ];
    @endphp

    <div style="display:grid; grid-template-columns:1fr 320px; gap:16px; align-items:start;">

        {{-- ── LEFT COLUMN ──────────────────────────────────────────────── --}}
        <div style="display:flex; flex-direction:column; gap:12px;">

            {{-- VERDICT BANNER --}}
            <div style="background:var(--card); border:1px solid var(--gold-dim); border-top:3px solid {{ $verdictColor }}; padding:16px 22px; display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <div class="tac-label" style="margin-bottom:6px;">FINAL VERDICT</div>
                    <div style="font-size:36px; font-weight:700; color:{{ $verdictColor }}; letter-spacing:0.06em; line-height:1;">
                        {{ $verdict }}
                    </div>
                    <div style="font-size:12px; color:var(--muted); margin-top:6px;">
                        {{ $inspection->defect_type ?? 'No Defect Type' }}
                        @if($inspection->instrument_class)
                            &nbsp;·&nbsp; {{ $inspection->instrument_class }}
                        @endif
                    </div>
                </div>
                <div style="text-align:right;">
                    <div class="tac-label" style="margin-bottom:6px;">AI CONFIDENCE</div>
                    <div style="font-size:36px; font-weight:700; color:var(--steel); line-height:1;">
                        {{ $inspection->confidence ?? 0 }}%
                    </div>
                    @if($crs !== null)
                    <div style="margin-top:10px;">
                        <div class="tac-label" style="margin-bottom:4px;">RISK LEVEL</div>
                        <div style="font-size:14px; font-weight:700; color:{{ $riskColor }}; letter-spacing:0.08em;">
                            {{ $riskLevel }}
                            <span style="font-size:11px; color:var(--muted); font-weight:400;">&nbsp; CRS: {{ number_format($crs, 1) }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- AGENT REASONING CHAIN --}}
            @if(!empty($agentSteps))
            <div style="background:var(--card); border:1px solid var(--gold-dim);">
                <div style="padding:14px 20px; border-bottom:1px solid var(--gold-dim); display:flex; align-items:center; gap:10px;">
                    <span style="font-size:9px; letter-spacing:0.16em; color:var(--gold); font-weight:700; text-transform:uppercase;">[ AGENT REASONING CHAIN ]</span>
                    <span style="font-size:10px; color:var(--muted);">{{ count($agentSteps) }} tool calls · Claude claude-sonnet-4-6</span>
                </div>

                <div style="padding:16px 20px; display:flex; flex-direction:column; gap:0;">
                    @foreach($agentSteps as $idx => $step)
                    @php
                        $meta   = $toolMeta[$step['tool']] ?? ['label' => strtoupper($step['tool']), 'icon' => '·'];
                        $result = $step['result'] ?? [];
                        $isLast = $idx === count($agentSteps) - 1;
                    @endphp

                    <div style="display:flex; gap:14px;">
                        {{-- Timeline spine --}}
                        <div style="display:flex; flex-direction:column; align-items:center; flex-shrink:0;">
                            <div style="width:28px; height:28px; border:1px solid var(--gold); background:var(--navy); display:flex; align-items:center; justify-content:center; font-size:13px; color:var(--gold); flex-shrink:0;">
                                {{ $meta['icon'] }}
                            </div>
                            @if(!$isLast)
                            <div style="width:1px; flex:1; min-height:20px; background:var(--gold-dim); margin:4px 0;"></div>
                            @endif
                        </div>

                        {{-- Step content --}}
                        <div style="padding-bottom:{{ $isLast ? '0' : '16px' }}; flex:1;">
                            <div style="font-size:10px; font-weight:700; letter-spacing:0.12em; color:var(--gold); text-transform:uppercase; margin-bottom:6px;">
                                STEP {{ $idx + 1 }} — {{ $meta['label'] }}
                            </div>

                            @php
                                $tool = $step['tool'];
                            @endphp

                            {{-- TOOL-SPECIFIC RESULT RENDERING --}}
                            @if($tool === 'check_image_quality')
                                <div style="font-size:11px; color:{{ ($result['quality_status'] ?? '') === 'OK' ? 'var(--pass)' : 'var(--flag)' }}; font-weight:700; margin-bottom:4px;">
                                    STATUS: {{ $result['quality_status'] ?? 'UNKNOWN' }}
                                    &nbsp;·&nbsp; SCORE: {{ $result['quality_score'] ?? '—' }}/100
                                </div>
                                <div style="font-size:11px; color:var(--muted);">{{ $result['reason'] ?? '' }}</div>
                                @if(isset($result['width']))
                                <div style="font-size:10px; color:var(--muted); margin-top:3px; opacity:0.6;">
                                    {{ $result['width'] }}×{{ $result['height'] }}px · {{ $result['file_size_kb'] }}KB
                                </div>
                                @endif

                            @elseif($tool === 'run_yolo_scan')
                                @if(!empty($result['detections']))
                                    <div style="font-size:11px; color:var(--text); margin-bottom:6px;">
                                        {{ $result['count'] }} detection(s) · {{ isset($result['model_used']) ? basename($result['model_used']) : 'model' }} · {{ $result['inference_ms'] }}ms
                                    </div>
                                    @foreach($result['detections'] as $det)
                                    <div style="display:flex; align-items:center; gap:10px; font-size:11px; color:var(--muted); margin-bottom:3px;">
                                        <span style="color:var(--steel); font-weight:600;">{{ $det['class_name'] ?? '' }}</span>
                                        <span>{{ round(($det['confidence'] ?? 0) * 100) }}% conf</span>
                                        <span style="color:{{ ($det['severity'] ?? '') === 'high' ? 'var(--fail)' : (($det['severity'] ?? '') === 'medium' ? 'var(--flag)' : 'var(--pass)') }};">
                                            {{ strtoupper($det['severity'] ?? '') }}
                                        </span>
                                    </div>
                                    @endforeach
                                @else
                                    <div style="font-size:11px; color:var(--muted);">
                                        {{ $result['summary'] ?? 'No detections above confidence threshold.' }}
                                    </div>
                                @endif

                            @elseif($tool === 'lookup_regulatory_context')
                                <div style="font-size:11px; color:var(--steel); font-weight:600; margin-bottom:4px;">
                                    {{ $result['applicable_standard'] ?? '' }}
                                </div>
                                <div style="display:flex; gap:16px; font-size:11px; color:var(--muted); margin-bottom:4px;">
                                    <span>Severity baseline: <strong style="color:var(--text);">{{ $result['severity_baseline'] ?? '—' }}/10</strong></span>
                                    <span>Recall prior: <strong style="color:var(--text);">{{ isset($result['recall_prior']) ? round($result['recall_prior'] * 100, 0) . '%' : '—' }}</strong></span>
                                    <span>Sterilization risk: <strong style="color:{{ ($result['sterilization_risk'] ?? false) ? 'var(--fail)' : 'var(--pass)' }};">{{ ($result['sterilization_risk'] ?? false) ? 'YES' : 'NO' }}</strong></span>
                                </div>
                                @if(isset($result['clinical_consequence']))
                                <div style="font-size:10px; color:var(--muted); opacity:0.7; font-style:italic;">{{ $result['clinical_consequence'] }}</div>
                                @endif

                            @elseif($tool === 'calculate_risk_score')
                                @php $formula = $result['formula_breakdown'] ?? []; @endphp
                                <div style="background:var(--navy); padding:10px 14px; font-size:11px; color:var(--text); margin-bottom:8px; font-family:var(--font);">
                                    <span style="color:var(--gold);">{{ $formula['formula'] ?? '' }}</span>
                                </div>
                                <div style="display:flex; gap:16px; font-size:11px; color:var(--muted); flex-wrap:wrap;">
                                    <span>S: <strong style="color:var(--text);">{{ $formula['S_severity'] ?? '—' }}</strong></span>
                                    <span>O: <strong style="color:var(--text);">{{ $formula['O_occurrence'] ?? '—' }}</strong></span>
                                    <span>D: <strong style="color:var(--text);">{{ $formula['D_detection'] ?? '—' }}</strong></span>
                                    <span>RPN: <strong style="color:var(--text);">{{ $formula['RPN'] ?? '—' }}</strong></span>
                                    <span>Trend: <strong style="color:{{ ($result['historical_context']['trend'] ?? '') === 'WORSENING' ? 'var(--fail)' : (($result['historical_context']['trend'] ?? '') === 'IMPROVING' ? 'var(--pass)' : 'var(--muted)') }};">{{ $result['historical_context']['trend'] ?? '—' }}</strong></span>
                                </div>

                            @elseif($tool === 'finalize_inspection_report')
                                <div style="font-size:11px; color:var(--pass);">Report finalized and persisted to audit log.</div>
                            @else
                                <div style="font-size:11px; color:var(--muted);">{{ json_encode($result) }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- CLAUDE REASONING --}}
            @if($inspection->claude_reasoning)
            @php
                $rawReasoning = $inspection->claude_reasoning;
                // Split on pipe-prefixed STEP markers: "| STEP N —" or "| STEP N -"
                $reasonParts = [];
                $segs = preg_split('/\|\s*(?=STEP\s*\d+)/i', $rawReasoning);
                foreach ($segs as $seg) {
                    $seg = trim($seg);
                    if (!$seg) continue;
                    // Extract "STEP N" label, put everything after "—" as body
                    if (preg_match('/^(STEP\s*\d+)\s*[—\-]+\s*([\s\S]+)$/i', $seg, $m)) {
                        $reasonParts[] = ['label' => strtoupper(trim($m[1])), 'text' => trim($m[2])];
                    } else {
                        $reasonParts[] = ['label' => null, 'text' => $seg];
                    }
                }
                if (empty($reasonParts)) {
                    $reasonParts = [['label' => null, 'text' => $rawReasoning]];
                }
            @endphp
            <div style="background:var(--card); border:1px solid var(--gold-dim);">
                <div style="padding:10px 20px; border-bottom:1px solid var(--gold-dim); display:flex; align-items:center; justify-content:space-between;">
                    <span style="font-size:9px; letter-spacing:0.16em; color:var(--gold); font-weight:700; text-transform:uppercase;">[ CLAUDE REASONING ]</span>
                    <span style="font-size:9px; color:var(--muted);">{{ count($reasonParts) }} section{{ count($reasonParts) !== 1 ? 's' : '' }}</span>
                </div>
                <div style="padding:12px 16px; display:flex; flex-direction:column; gap:6px;">
                    @foreach($reasonParts as $rp)
                    <div style="background:var(--navy); border-left:2px solid var(--gold-dim); padding:8px 12px;">
                        @if($rp['label'])
                        <div style="font-size:9px; font-weight:700; letter-spacing:0.12em; color:var(--gold); text-transform:uppercase; margin-bottom:4px;">
                            {{ $rp['label'] }}
                        </div>
                        @endif
                        <div style="font-size:11px; color:var(--text); line-height:1.6;">{{ $rp['text'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- RECOMMENDED ACTION + REGULATORY NOTE --}}
            @if($inspection->recommended_action || $inspection->regulatory_note)
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                @if($inspection->recommended_action)
                <div style="background:var(--card); border:1px solid var(--gold-dim); border-left:2px solid var(--pass); padding:12px 14px;">
                    <div class="tac-label" style="margin-bottom:6px; color:var(--pass);">RECOMMENDED ACTION</div>
                    <div style="font-size:11px; color:var(--text); line-height:1.5;">{{ $inspection->recommended_action }}</div>
                </div>
                @endif
                @if($inspection->regulatory_note)
                <div style="background:var(--card); border:1px solid var(--gold-dim); border-left:2px solid var(--steel); padding:12px 14px;">
                    <div class="tac-label" style="margin-bottom:6px; color:var(--steel);">REGULATORY NOTE</div>
                    <div style="font-size:11px; color:var(--muted); line-height:1.5;">{{ $inspection->regulatory_note }}</div>
                </div>
                @endif
            </div>
            @endif

        </div>

        {{-- ── RIGHT COLUMN ─────────────────────────────────────────────── --}}
        <div style="display:flex; flex-direction:column; gap:12px;">

            {{-- INSTRUMENT IMAGE --}}
            <div style="background:var(--card); border:1px solid var(--gold-dim);">
                <div style="padding:10px 16px; border-bottom:1px solid var(--gold-dim);">
                    <span style="font-size:9px; letter-spacing:0.16em; color:var(--gold); font-weight:700; text-transform:uppercase;">[ INSPECTED IMAGE ]</span>
                </div>
                <div style="padding:12px;">
                    <img src="{{ Storage::url($inspection->image_path) }}"
                         alt="Inspected instrument"
                         style="width:100%; display:block; object-fit:contain; max-height:260px; background:var(--navy);">
                </div>
            </div>

            {{-- QC COST MATRIX (FLAGGED items only) --}}
            @if($verdict === 'FLAGGED' && $costMatrix)
            <div style="background:var(--card); border:1px solid rgba(243,156,18,0.4); border-top:2px solid var(--flag);">
                <div style="padding:10px 16px; border-bottom:1px solid rgba(243,156,18,0.2);">
                    <span style="font-size:9px; letter-spacing:0.16em; color:var(--flag); font-weight:700; text-transform:uppercase;">[ QC DECISION MATRIX ]</span>
                </div>
                <div style="padding:14px 16px; display:flex; flex-direction:column; gap:10px;">

                    <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:var(--navy); border-left:3px solid var(--fail);">
                        <div>
                            <div style="font-size:9px; letter-spacing:0.1em; color:var(--muted); text-transform:uppercase;">PASS — Expected Liability</div>
                            <div style="font-size:16px; font-weight:700; color:var(--fail); margin-top:2px;">
                                @if(is_numeric($costMatrix['pass_expected_liability']))
                                    ${{ number_format($costMatrix['pass_expected_liability']) }}
                                @else
                                    {{ $costMatrix['pass_expected_liability'] }}
                                @endif
                            </div>
                        </div>
                        <span style="font-size:18px; color:var(--fail);">✗</span>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:var(--navy); border-left:3px solid var(--flag);">
                        <div>
                            <div style="font-size:9px; letter-spacing:0.1em; color:var(--muted); text-transform:uppercase;">DISCARD — Material Cost</div>
                            <div style="font-size:16px; font-weight:700; color:var(--flag); margin-top:2px;">${{ number_format($costMatrix['discard_cost']) }}</div>
                        </div>
                        <span style="font-size:16px; color:var(--flag);">◇</span>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:var(--navy); border-left:3px solid var(--steel);">
                        <div>
                            <div style="font-size:9px; letter-spacing:0.1em; color:var(--muted); text-transform:uppercase;">REVIEW — Labor + Delay</div>
                            <div style="font-size:16px; font-weight:700; color:var(--steel); margin-top:2px;">${{ number_format($costMatrix['review_cost']) }}</div>
                        </div>
                        <span style="font-size:16px; color:var(--steel);">◈</span>
                    </div>

                    <div style="padding:10px 12px; background:rgba(201,150,62,0.08); border:1px solid var(--gold-dim); margin-top:4px;">
                        <div style="font-size:9px; letter-spacing:0.12em; color:var(--gold); text-transform:uppercase; font-weight:700; margin-bottom:4px;">RECOMMENDATION</div>
                        <div style="font-size:11px; color:var(--text);">{{ $costMatrix['recommendation'] ?? '—' }}</div>
                    </div>

                    <div style="font-size:9px; color:var(--muted); opacity:0.5; text-align:center; margin-top:2px;">
                        Bayes risk minimization · E(action) = P(harm) × cost
                    </div>
                </div>
            </div>
            @endif

            {{-- INSPECTION METADATA --}}
            <div style="background:var(--card); border:1px solid var(--gold-dim); padding:16px;">
                <div class="tac-label" style="margin-bottom:12px;">INSPECTION METADATA</div>
                <table style="width:100%; font-size:11px;">
                    <tr>
                        <td style="color:var(--muted); padding:4px 0; border:none;">Inspection ID</td>
                        <td style="color:var(--text); text-align:right; border:none;">#{{ $inspection->id }}</td>
                    </tr>
                    <tr>
                        <td style="color:var(--muted); padding:4px 0; border:none;">Inspector</td>
                        <td style="color:var(--text); text-align:right; border:none;">{{ Auth::user()->name }}</td>
                    </tr>
                    <tr>
                        <td style="color:var(--muted); padding:4px 0; border:none;">YOLO Model</td>
                        <td style="color:var(--text); text-align:right; border:none; word-break:break-all;">
                            {{ $inspection->yolo_model ? basename($inspection->yolo_model) : 'unavailable' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="color:var(--muted); padding:4px 0; border:none;">Analysis Time</td>
                        <td style="color:var(--text); text-align:right; border:none;">
                            @if($inspection->inference_ms)
                                @if($inspection->inference_ms >= 60000)
                                    {{ floor($inspection->inference_ms / 60000) }}m {{ round(($inspection->inference_ms % 60000) / 1000) }}s
                                @elseif($inspection->inference_ms >= 1000)
                                    {{ round($inspection->inference_ms / 1000, 1) }}s
                                @else
                                    {{ $inspection->inference_ms }}ms
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @if($crs !== null)
                    <tr>
                        <td style="color:var(--muted); padding:4px 0; border:none;">Composite Risk Score</td>
                        <td style="color:{{ $riskColor }}; text-align:right; font-weight:700; border:none;">{{ number_format($crs, 1) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="color:var(--muted); padding:4px 0; border:none;">Timestamp</td>
                        <td style="color:var(--text); text-align:right; border:none; font-size:10px;">{{ $inspection->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                </table>
            </div>

        </div>{{-- end right column --}}
    </div>
</x-app-layout>
