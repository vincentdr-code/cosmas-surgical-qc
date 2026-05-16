<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Cosmas Sentry — Quality Control Dashboard
            </h2>
            <a href="{{ route('inspections.upload') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                + New Inspection
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- ── KPI Cards ──────────────────────────────────────────────── --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 col-span-1">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Inspections</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $total }}</p>
                    <p class="mt-1 text-xs text-gray-400">All time</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 col-span-1">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pass Rate</p>
                    <p class="mt-2 text-3xl font-bold {{ $passRate >= 80 ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ $passRate }}%
                    </p>
                    <p class="mt-1 text-xs text-gray-400">{{ $passed }} passed</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 col-span-1">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Cost Saved</p>
                    <p class="mt-2 text-3xl font-bold text-blue-600">${{ $costSaved }}</p>
                    <p class="mt-1 text-xs text-gray-400">vs. manual @ $0.15/inspection</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 col-span-1">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Flagged</p>
                    <p class="mt-2 text-3xl font-bold text-yellow-600">{{ $flagged }}</p>
                    <p class="mt-1 text-xs text-gray-400">Needs review</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 col-span-1">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Rejected</p>
                    <p class="mt-2 text-3xl font-bold text-red-600">{{ $failed }}</p>
                    <p class="mt-1 text-xs text-gray-400">Defects caught</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 col-span-1">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Avg AI Confidence</p>
                    <p class="mt-2 text-3xl font-bold text-purple-600">
                        @if($avgConfidence !== null)
                            {{ $avgConfidence }}%
                        @else
                            —
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-gray-400">Model certainty, all inspections</p>
                </div>


                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 col-span-1 lg:col-span-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Inspection</p>
                    @if($lastInspection)
                        <p class="mt-2 text-lg font-bold text-gray-900">{{ $lastInspection->created_at->diffForHumans() }}</p>
                        <p class="mt-1 text-xs text-gray-400">
                            #{{ $lastInspection->id }} &mdash;
                            @php $pf = strtoupper($lastInspection->pass_fail ?? ''); @endphp
                            <span class="{{ $pf === 'PASS' ? 'text-green-600' : ($pf === 'FAIL' ? 'text-red-600' : 'text-yellow-600') }} font-semibold">
                                {{ $pf }}
                            </span>
                        </p>
                    @else
                        <p class="mt-2 text-sm text-gray-400">No inspections yet</p>
                    @endif
                </div>

            </div>

            {{-- ── ROI Banner ─────────────────────────────────────────────── --}}
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-sm p-6 text-white">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold">ROI vs. Manual Inspection</h3>
                        <p class="mt-1 text-blue-100 text-sm">
                            Manual cost: $0.15/inspection &nbsp;·&nbsp; AI cost: $0.01/inspection &nbsp;·&nbsp;
                            <strong class="text-white">$0.14 saved per inspection</strong>
                        </p>
                        <p class="mt-1 text-blue-100 text-sm">
                            At 10,000 inspections/month → <strong class="text-white">$1,400/month · $16,800/year</strong> in direct labour savings.
                            Industry benchmark: <strong class="text-white">374% 3-year ROI</strong> (Forrester Research).
                        </p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-4xl font-black">${{ $costSaved }}</p>
                        <p class="text-blue-100 text-sm">saved so far</p>
                    </div>
                </div>
            </div>

            {{-- ── Recent Inspections ─────────────────────────────────────── --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Recent Inspections</h3>
                    <a href="{{ route('inspections.audit-log') }}"
                       class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        View all →
                    </a>
                </div>

                @if($recent->count())
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Defect / Instrument</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Confidence</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recent as $inspection)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-500 font-mono">#{{ $inspection->id }}</td>
                            <td class="px-6 py-4">
                                @php $pf = strtoupper($inspection->pass_fail ?? ''); @endphp
                                @if($pf === 'PASS')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✓ PASS</span>
                                @elseif($pf === 'FAIL')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">✗ FAIL</span>
                                @elseif($pf === 'FLAGGED')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">⚑ FLAGGED</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $inspection->pass_fail }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate">
                                {{ $inspection->defect_type ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $inspection->confidence ? round($inspection->confidence) . '%' : '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $inspection->created_at->format('M d, H:i') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('inspections.results', $inspection->id) }}"
                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">View</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="p-12 text-center text-gray-400">
                    <p>No inspections yet. <a href="{{ route('inspections.upload') }}" class="text-blue-600">Upload your first image →</a></p>
                </div>
                @endif
            </div>

            {{-- ── Inspection Trend (14-day) ───────────────────────────────── --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-900">Inspection Trend — Last 14 Days</h3>
                    <span class="text-xs text-gray-400">PASS · FAIL · FLAGGED</span>
                </div>
                <canvas id="trendChart" height="80"></canvas>
            </div>

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
                            { label: 'PASS',    data: pass, backgroundColor: '#22c55e', borderRadius: 3 },
                            { label: 'FAIL',    data: fail, backgroundColor: '#ef4444', borderRadius: 3 },
                            { label: 'FLAGGED', data: flag, backgroundColor: '#f59e0b', borderRadius: 3 },
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'bottom' } },
                        scales: {
                            x: { stacked: true, grid: { display: false } },
                            y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
                        }
                    }
                });
            })();
            </script>

            {{-- ── How It Works ───────────────────────────────────────────── --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">How Cosmas Sentry Works</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mx-auto text-blue-600 font-bold text-lg">1</div>
                        <p class="mt-2 text-sm font-medium text-gray-900">Upload Image</p>
                        <p class="mt-1 text-xs text-gray-500">Photo of surgical instrument from production line</p>
                    </div>
                    <div class="text-center">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mx-auto text-purple-600 font-bold text-lg">2</div>
                        <p class="mt-2 text-sm font-medium text-gray-900">YOLOv8 Detection</p>
                        <p class="mt-1 text-xs text-gray-500">Fine-tuned computer vision identifies instrument class in ~200ms</p>
                    </div>
                    <div class="text-center">
                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mx-auto text-indigo-600 font-bold text-lg">3</div>
                        <p class="mt-2 text-sm font-medium text-gray-900">Claude AI Reasoning</p>
                        <p class="mt-1 text-xs text-gray-500">Vision model assesses defects, references ISO/FDA standards</p>
                    </div>
                    <div class="text-center">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mx-auto text-green-600 font-bold text-lg">4</div>
                        <p class="mt-2 text-sm font-medium text-gray-900">QC Decision</p>
                        <p class="mt-1 text-xs text-gray-500">PASS / FAIL / FLAGGED with regulatory note and audit trail</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
