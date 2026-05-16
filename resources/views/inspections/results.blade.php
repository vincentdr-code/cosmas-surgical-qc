<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Inspection #{{ $inspection->id }} — Results
            </h2>
            <span class="text-sm text-gray-500">{{ $inspection->created_at->format('M d, Y H:i:s') }} UTC</span>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- PASS / FAIL hero banner -->
            @php
                $pf = $inspection->pass_fail;
                $bannerClass = match($pf) {
                    'PASS'    => 'bg-green-600',
                    'FAIL'    => 'bg-red-600',
                    default   => 'bg-yellow-500',
                };
                $icon = match($pf) {
                    'PASS'  => '✓',
                    'FAIL'  => '✗',
                    default => '⚠',
                };
            @endphp
            <div class="{{ $bannerClass }} rounded-2xl p-6 mb-6 text-white flex items-center justify-between">
                <div>
                    <div class="text-5xl font-black">{{ $icon }} {{ $pf }}</div>
                    <div class="text-lg mt-1 opacity-90">{{ $inspection->defect_type }}</div>
                </div>
                <div class="text-right">
                    <div class="text-sm opacity-75 mb-1">AI Confidence</div>
                    <div class="text-4xl font-bold">{{ $inspection->confidence }}%</div>
                    <div class="mt-2 w-40 bg-white bg-opacity-30 rounded-full h-2">
                        <div class="bg-white h-2 rounded-full" style="width: {{ $inspection->confidence }}%"></div>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-6 mb-6">
                <!-- Instrument Image -->
                <div class="md:col-span-2 bg-white rounded-xl shadow p-5">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-widest mb-3">Instrument Image</h3>
                    @if($inspection->image_path)
                        <img src="{{ asset('storage/' . $inspection->image_path) }}"
                             alt="Inspection Image"
                             class="w-full rounded-lg border border-gray-200 object-contain max-h-80">
                    @else
                        <div class="bg-gray-100 rounded-lg h-48 flex items-center justify-center text-gray-400">No image</div>
                    @endif
                </div>

                <!-- Quick Facts -->
                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-widest mb-3">Inspection Summary</h3>
                    <div class="space-y-3">
                        <div>
                            <div class="text-xs text-gray-400">Inspection ID</div>
                            <div class="font-mono font-semibold text-gray-800">#{{ $inspection->id }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400">Defect Type</div>
                            <div class="font-semibold text-gray-800">{{ $inspection->defect_type }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400">YOLO Model</div>
                            <div class="font-semibold text-gray-800 text-sm">{{ $inspection->yolo_model ?? 'N/A' }}</div>
                        </div>
                        @if($inspection->inference_ms)
                        <div>
                            <div class="text-xs text-gray-400">YOLO Inference</div>
                            <div class="font-semibold text-green-700">{{ $inspection->inference_ms }}ms</div>
                        </div>
                        @endif
                        <div>
                            <div class="text-xs text-gray-400">Detections Found</div>
                            <div class="font-semibold text-gray-800">{{ $inspection->yolo_count ?? 0 }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400">Inspector</div>
                            <div class="font-semibold text-gray-800">{{ $inspection->user->name ?? 'Unknown' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400">Timestamp</div>
                            <div class="font-semibold text-gray-800 text-sm">{{ $inspection->created_at->format('Y-m-d H:i:s') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- YOLOv8 Detections -->
            @php $yoloDetections = json_decode($inspection->yolo_detections ?? '[]', true); @endphp
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">Stage 1 — YOLOv8s Computer Vision</h3>
                        <p class="text-xs text-gray-500">Fine-tuned on 10,764 surgical instrument defect images</p>
                    </div>
                    @if($inspection->inference_ms)
                        <span class="ml-auto px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">{{ $inspection->inference_ms }}ms</span>
                    @endif
                </div>

                @if(!empty($yoloDetections))
                    <div class="space-y-2">
                        @foreach($yoloDetections as $i => $det)
                            @php
                                $conf = round(($det['confidence'] ?? 0) * 100);
                                $sev  = $det['severity'] ?? 'unknown';
                                $sevColor = match(strtolower($sev)) {
                                    'high'   => 'bg-red-100 text-red-700 border-red-200',
                                    'medium' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    default  => 'bg-gray-100 text-gray-600 border-gray-200',
                                };
                            @endphp
                            <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <span class="text-sm font-bold text-gray-400 w-4">{{ $i + 1 }}</span>
                                <span class="font-semibold text-gray-800 flex-1">{{ ucfirst($det['class_name'] ?? 'Unknown') }}</span>
                                <div class="flex items-center gap-2 w-32">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $conf }}%"></div>
                                    </div>
                                    <span class="text-sm font-bold text-blue-700 w-10 text-right">{{ $conf }}%</span>
                                </div>
                                <span class="px-2 py-1 rounded border text-xs font-semibold {{ $sevColor }}">{{ strtoupper($sev) }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif($inspection->yolo_model === 'unavailable')
                    <div class="text-gray-400 text-sm italic p-3 bg-gray-50 rounded-lg">
                        YOLOv8 service was unavailable during this inspection — Claude-only analysis mode.
                    </div>
                @else
                    <div class="text-gray-500 text-sm p-3 bg-green-50 rounded-lg border border-green-100">
                        ✓ YOLOv8 detected no objects of concern above 25% confidence threshold.
                    </div>
                @endif
            </div>

            <!-- Claude Reasoning -->
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">Stage 2 — Claude AI Reasoning</h3>
                        <p class="text-xs text-gray-500">claude-sonnet-4-6 · ISO 13485 / FDA 21 CFR Part 820 aware</p>
                    </div>
                </div>

                @if($inspection->claude_reasoning)
                    <div class="bg-purple-50 border border-purple-100 rounded-lg p-4 text-gray-700 text-sm leading-relaxed mb-4">
                        {{ $inspection->claude_reasoning }}
                    </div>
                @endif

                <div class="grid md:grid-cols-2 gap-4">
                    @if($inspection->recommended_action ?? false)
                        <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg">
                            <div class="text-xs text-blue-600 font-semibold uppercase tracking-widest mb-1">Recommended Action</div>
                            <div class="text-sm font-medium text-blue-900">{{ $inspection->recommended_action }}</div>
                        </div>
                    @endif

                    @if($inspection->regulatory_note ?? false)
                        <div class="p-3 bg-amber-50 border border-amber-100 rounded-lg">
                            <div class="text-xs text-amber-600 font-semibold uppercase tracking-widest mb-1">Regulatory Note</div>
                            <div class="text-sm font-medium text-amber-900">{{ $inspection->regulatory_note }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Device History Record (FDA 21 CFR Part 11) -->
            <div class="bg-slate-800 rounded-xl p-5 mb-6 text-white">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Device History Record — FDA 21 CFR Part 11</span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm font-mono">
                    <div>
                        <div class="text-slate-500 text-xs">record_id</div>
                        <div class="text-slate-200">{{ $inspection->id }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">user_id</div>
                        <div class="text-slate-200">{{ $inspection->user_id }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">decision</div>
                        <div class="{{ $inspection->pass_fail === 'PASS' ? 'text-green-400' : 'text-red-400' }}">{{ $inspection->pass_fail }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">confidence</div>
                        <div class="text-slate-200">{{ $inspection->confidence }}%</div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">yolo_model</div>
                        <div class="text-slate-200 text-xs">{{ $inspection->yolo_model ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">yolo_detections</div>
                        <div class="text-slate-200">{{ $inspection->yolo_count ?? 0 }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">created_at</div>
                        <div class="text-slate-200 text-xs">{{ $inspection->created_at->toIso8601String() }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">record_type</div>
                        <div class="text-slate-200">AI_INSPECTION</div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-4 justify-center">
                <a href="{{ route('inspections.upload') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition">
                    + New Inspection
                </a>
                <a href="{{ route('inspections.audit-log') }}"
                   class="bg-slate-700 hover:bg-slate-800 text-white font-bold py-3 px-6 rounded-xl transition">
                    Audit Log
                </a>
                <a href="{{ route('dashboard') }}"
                   class="bg-white hover:bg-gray-50 text-gray-700 font-bold py-3 px-6 rounded-xl border border-gray-300 transition">
                    Dashboard
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
