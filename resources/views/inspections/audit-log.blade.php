<x-app-layout>
    <x-slot name="header">
        <span style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; color:#8A9BAE; letter-spacing:0.12em; text-transform:uppercase;">
            [ AUDIT LOG — FDA 21 CFR PART 820 ]
        </span>
        <div style="display:flex; gap:12px; align-items:center;">
            @if(Route::has('inspections.export-csv'))
            <a href="{{ route('inspections.export-csv') }}"
               style="font-family:'JetBrains Mono',monospace; font-size:10px; font-weight:700; letter-spacing:0.1em; color:#C9963E; border:1px solid rgba(201,150,62,0.35); padding:6px 14px; text-decoration:none; text-transform:uppercase;">
                EXPORT CSV
            </a>
            @endif
            <a href="{{ route('inspections.upload') }}"
               style="font-family:'JetBrains Mono',monospace; font-size:10px; font-weight:700; letter-spacing:0.1em; color:#060F1E; background:#C9963E; padding:6px 14px; text-decoration:none; text-transform:uppercase;">
                + INSPECT
            </a>
        </div>
    </x-slot>

    {{-- Stats Strip --}}
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1px; background:rgba(201,150,62,0.1); margin-bottom:1px;">
        <div class="tac-card">
            <div class="tac-label">Total Records</div>
            <div class="tac-value">{{ $inspections->total() ?? $inspections->count() }}</div>
        </div>
        <div class="tac-card">
            <div class="tac-label">Last Entry</div>
            <div class="tac-value" style="font-size:16px; color:#8A9BAE;">
                {{ $inspections->first() ? $inspections->first()->created_at->format('M d · H:i') : '—' }}
            </div>
        </div>
        <div class="tac-card">
            <div class="tac-label">Compliance</div>
            <div class="tac-value" style="color:#2ECC71; font-size:16px;">21 CFR 820</div>
        </div>
        <div class="tac-card">
            <div class="tac-label">Record Type</div>
            <div class="tac-value" style="font-size:16px; color:#C9963E;">APPEND-ONLY</div>
        </div>
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
                    <th style="width:130px;">OPERATOR</th>
                    <th style="width:150px;">TIMESTAMP</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($inspections as $insp)
                @php $pf = strtoupper($insp->pass_fail ?? ''); @endphp
                <tr>
                    <td style="color:#8A9BAE; font-size:11px;">#{{ $insp->id }}</td>
                    <td>
                        @if($pf === 'PASS')   <span class="badge-pass">✓ PASS</span>
                        @elseif($pf === 'FAIL') <span class="badge-fail">✗ FAIL</span>
                        @elseif($pf === 'FLAGGED') <span class="badge-flag">⚑ FLAG</span>
                        @else <span class="badge-grey">{{ $insp->pass_fail }}</span>
                        @endif
                    </td>
                    <td style="color:#8A9BAE; font-size:12px; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $insp->defect_type ?? '—' }}
                    </td>
                    <td style="color:#4A7C9E; font-size:12px;">
                        {{ $insp->confidence ? round($insp->confidence) . '%' : '—' }}
                    </td>
                    <td style="color:#8A9BAE; font-size:11px;">
                        {{ $insp->operator_id ?? '—' }}
                    </td>
                    <td style="color:#8A9BAE; font-size:11px;">
                        {{ $insp->created_at->format('Y-m-d H:i:s') }}
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

        {{-- Pagination --}}
        @if(method_exists($inspections, 'links'))
        <div style="padding:16px 20px; border-top:1px solid rgba(201,150,62,0.15);">
            {{ $inspections->links() }}
        </div>
        @endif

        @else
        <div style="padding:48px; text-align:center; color:#8A9BAE;">
            <div style="font-size:32px; opacity:0.2; margin-bottom:12px;">◈</div>
            <div>No inspection records yet.</div>
        </div>
        @endif
    </div>

</x-app-layout>
