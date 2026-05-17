<x-app-layout>
    <x-slot name="header">
        <span style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; color:#8A9BAE; letter-spacing:0.12em; text-transform:uppercase;">
            [ INSPECTION #{{ $inspection->id ?? '—' }} ]
        </span>
        <a href="{{ route('dashboard') }}"
           style="font-family:'JetBrains Mono',monospace; font-size:11px; color:#8A9BAE; text-decoration:none; letter-spacing:0.08em;">
            ← DASHBOARD
        </a>
    </x-slot>

    @php
        $pf = strtoupper($inspection->pass_fail ?? '');
        $verdictColor = match($pf) {
            'PASS'    => '#2ECC71',
            'FAIL'    => '#E74C3C',
            'FLAGGED' => '#F39C12',
            default   => '#8A9BAE',
        };
        $conf = $inspection->confidence ?? null;
    @endphp

    {{-- Verdict Banner --}}
    <div style="border:1px solid {{ $verdictColor }}22; border-left:4px solid {{ $verdictColor }}; background:{{ $verdictColor }}08; padding:24px 28px; margin-bottom:1px; display:flex; align-items:center; justify-content:space-between;">
        <div>
            <div class="tac-label" style="margin-bottom:8px;">VERDICT</div>
            <div style="font-size:40px; font-weight:700; color:{{ $verdictColor }}; line-height:1; letter-spacing:0.06em;">
                @if($pf === 'PASS') ✓ PASS
                @elseif($pf === 'FAIL') ✗ FAIL
                @elseif($pf === 'FLAGGED') ⚑ FLAGGED
                @else {{ $pf ?: 'UNKNOWN' }}
                @endif
            </div>
            <div style="color:#8A9BAE; font-size:12px; margin-top:8px;">
                {{ $inspection->created_at->format('Y-m-d H:i:s') }} UTC
            </div>
        </div>
        <div style="text-align:right;">
            @if($conf !== null)
            <div class="tac-label" style="margin-bottom:6px;">CONFIDENCE</div>
            <div style="font-size:48px; font-weight:700; color:#4A7C9E; line-height:1;">{{ round($conf) }}%</div>
            @endif
        </div>
    </div>

    {{-- Detail Grid --}}
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1px; background:rgba(201,150,62,0.1); margin-bottom:1px;">

        <div class="tac-card">
            <div class="tac-label" style="margin-bottom:16px;">[ INSTRUMENT DATA ]</div>
            <table style="width:100%;">
                <tbody>
                    <tr>
                        <td style="color:#8A9BAE; width:140px; padding:8px 0 !important; border:none !important;">Instrument ID</td>
                        <td style="padding:8px 0 !important; border:none !important; color:#E8EDF2;">{{ $inspection->instrument_id ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="color:#8A9BAE; padding:8px 0 !important; border:none !important;">Operator ID</td>
                        <td style="padding:8px 0 !important; border:none !important; color:#E8EDF2;">{{ $inspection->operator_id ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="color:#8A9BAE; padding:8px 0 !important; border:none !important;">Defect Type</td>
                        <td style="padding:8px 0 !important; border:none !important; color:{{ $pf === 'FAIL' ? '#E74C3C' : '#E8EDF2' }}; font-weight:600;">
                            {{ $inspection->defect_type ?? 'None detected' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#8A9BAE; padding:8px 0 !important; border:none !important;">YOLO Class</td>
                        <td style="padding:8px 0 !important; border:none !important; color:#E8EDF2;">{{ $inspection->yolo_class ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="color:#8A9BAE; padding:8px 0 !important; border:none !important;">Image File</td>
                        <td style="padding:8px 0 !important; border:none !important; color:#8A9BAE; font-size:11px;">{{ $inspection->image_filename ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="tac-card">
            <div class="tac-label" style="margin-bottom:16px;">[ AI REASONING ]</div>
            <div style="background:#060F1E; border:1px solid rgba(201,150,62,0.12); padding:16px; font-size:12px; color:#8A9BAE; line-height:1.8; max-height:220px; overflow-y:auto; white-space:pre-wrap; word-break:break-word;">{{ $inspection->ai_reasoning ?? $inspection->notes ?? 'No reasoning logged for this inspection.' }}</div>
        </div>

    </div>

    {{-- Image (if stored) --}}
    @if(!empty($inspection->image_filename) && file_exists(storage_path('app/public/inspections/' . $inspection->image_filename)))
    <div class="tac-card" style="margin-bottom:1px; text-align:center;">
        <div class="tac-label" style="margin-bottom:16px; text-align:left;">[ INSTRUMENT IMAGE ]</div>
        <img src="{{ asset('storage/inspections/' . $inspection->image_filename) }}"
             alt="Inspection image"
             style="max-height:400px; max-width:100%; object-fit:contain; border:1px solid rgba(201,150,62,0.2);">
    </div>
    @endif

    {{-- Notes --}}
    @if(!empty($inspection->notes) && $inspection->notes !== $inspection->ai_reasoning)
    <div class="tac-card" style="margin-bottom:1px;">
        <div class="tac-label" style="margin-bottom:12px;">[ OPERATOR NOTES ]</div>
        <div style="color:#8A9BAE; font-size:12px; line-height:1.7;">{{ $inspection->notes }}</div>
    </div>
    @endif

    {{-- Actions --}}
    <div style="display:flex; gap:12px; margin-top:1px;">
        <a href="{{ route('inspections.upload') }}"
           style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; letter-spacing:0.1em; color:#060F1E; background:#C9963E; padding:10px 24px; text-decoration:none; text-transform:uppercase;">
            NEW INSPECTION →
        </a>
        <a href="{{ route('inspections.audit-log') }}"
           style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; letter-spacing:0.1em; color:#C9963E; border:1px solid rgba(201,150,62,0.35); padding:10px 24px; text-decoration:none; text-transform:uppercase;">
            AUDIT LOG
        </a>
    </div>

</x-app-layout>
