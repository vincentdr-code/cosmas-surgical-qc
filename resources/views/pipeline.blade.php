<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            How It Works — The Two-Stage AI Pipeline
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Hero -->
            <div class="bg-slate-900 rounded-2xl p-8 mb-8 text-white">
                <div class="text-center">
                    <p class="text-blue-400 text-sm font-semibold uppercase tracking-widest mb-2">Architecture Overview</p>
                    <h1 class="text-3xl font-bold mb-4">AI-Powered Quality Control Pipeline</h1>
                    <p class="text-slate-300 max-w-2xl mx-auto">
                        Cosmas Damian uses a two-stage AI pipeline that mirrors how an expert QC inspector actually reasons —
                        fast computer vision pre-screening followed by contextual AI analysis with regulatory awareness.
                    </p>
                </div>

                <!-- Pipeline diagram -->
                <div class="mt-10 flex flex-col md:flex-row items-center justify-center gap-4">
                    <!-- Input -->
                    <div class="bg-slate-800 border border-slate-600 rounded-xl p-4 text-center min-w-32">
                        <div class="text-3xl mb-2">📷</div>
                        <div class="text-sm font-semibold text-white">Instrument Image</div>
                        <div class="text-xs text-slate-400 mt-1">Upload via web UI</div>
                    </div>

                    <!-- Arrow -->
                    <div class="text-slate-400 text-2xl font-bold hidden md:block">→</div>
                    <div class="text-slate-400 text-2xl font-bold md:hidden">↓</div>

                    <!-- Stage 1 -->
                    <div class="bg-blue-900 border-2 border-blue-500 rounded-xl p-4 text-center min-w-44">
                        <div class="text-xs font-bold text-blue-400 uppercase tracking-widest mb-1">Stage 1</div>
                        <div class="text-lg font-bold text-white">YOLOv8s</div>
                        <div class="text-xs text-blue-200 mt-1">Computer Vision</div>
                        <div class="mt-2 text-xs text-slate-300 bg-blue-950 rounded p-2">
                            ~200ms · 11M params<br>
                            class + confidence + bbox
                        </div>
                    </div>

                    <!-- Arrow -->
                    <div class="text-slate-400 text-2xl font-bold hidden md:block">→</div>
                    <div class="text-slate-400 text-2xl font-bold md:hidden">↓</div>

                    <!-- Stage 2 -->
                    <div class="bg-purple-900 border-2 border-purple-500 rounded-xl p-4 text-center min-w-44">
                        <div class="text-xs font-bold text-purple-400 uppercase tracking-widest mb-1">Stage 2</div>
                        <div class="text-lg font-bold text-white">Claude Vision</div>
                        <div class="text-xs text-purple-200 mt-1">Contextual Reasoning</div>
                        <div class="mt-2 text-xs text-slate-300 bg-purple-950 rounded p-2">
                            1–3s · ISO/FDA-aware<br>
                            pass/fail + reasoning
                        </div>
                    </div>

                    <!-- Arrow -->
                    <div class="text-slate-400 text-2xl font-bold hidden md:block">→</div>
                    <div class="text-slate-400 text-2xl font-bold md:hidden">↓</div>

                    <!-- Output -->
                    <div class="bg-green-900 border border-green-600 rounded-xl p-4 text-center min-w-36">
                        <div class="text-3xl mb-2">✓</div>
                        <div class="text-sm font-semibold text-white">Decision + Log</div>
                        <div class="text-xs text-green-300 mt-1">PASS / FAIL<br>Audit Trail</div>
                    </div>
                </div>
            </div>

            <!-- Stage 1 Detail -->
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs text-blue-600 font-semibold uppercase tracking-widest">Stage 1</div>
                            <h3 class="text-lg font-bold text-gray-900">YOLOv8s Computer Vision</h3>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">
                        A fine-tuned YOLOv8s model (11M parameters, COCO-pretrained) performs object detection
                        on the instrument image in approximately 200ms. It identifies defect regions and assigns
                        class labels and confidence scores.
                    </p>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Model architecture</span>
                            <span class="font-medium">YOLOv8s — 11.1M params, 28.7 GFLOPs</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Inference speed</span>
                            <span class="font-medium text-green-600">~200ms on CPU</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Training data</span>
                            <span class="font-medium">10,764 images, 4 datasets</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Transfer learning</span>
                            <span class="font-medium">COCO-pretrained → fine-tuned</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-500 font-semibold uppercase tracking-widest mb-2">Detected Classes</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['cracks', 'corrosion', 'scratches', 'porosity', 'none (conforming)'] as $class)
                                <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-medium">{{ $class }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Stage 2 Detail -->
                <div class="bg-white rounded-xl shadow p-6 border-l-4 border-purple-500">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs text-purple-600 font-semibold uppercase tracking-widest">Stage 2</div>
                            <h3 class="text-lg font-bold text-gray-900">Claude Vision Reasoning</h3>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">
                        Claude receives the instrument image directly alongside YOLOv8's structured findings.
                        It performs contextual reasoning — distinguishing cosmetic marks from structural defects,
                        referencing applicable ISO and FDA standards, and generating a plain-English decision
                        that a QC supervisor can act on immediately.
                    </p>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Model</span>
                            <span class="font-medium">claude-sonnet-4-6 (vision)</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Input</span>
                            <span class="font-medium">Image + YOLOv8 detections</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Standards referenced</span>
                            <span class="font-medium">ISO 13485, FDA 21 CFR 820</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Output</span>
                            <span class="font-medium">PASS/FAIL + reasoning + action</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-500 font-semibold uppercase tracking-widest mb-2">Output Fields</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['defect_type', 'confidence', 'pass_fail', 'reasoning', 'recommended_action', 'regulatory_note'] as $field)
                                <span class="px-2 py-1 bg-purple-50 text-purple-700 rounded text-xs font-mono">{{ $field }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Why Two Stages -->
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Why Two Stages? Neither Alone Is Sufficient.</h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="text-2xl mb-2">⚡</div>
                        <h4 class="font-semibold text-gray-800 mb-2">YOLOv8s Alone</h4>
                        <p class="text-sm text-gray-600">Fast and consistent, but cannot distinguish a cosmetic scratch from a structural crack, nor can it reference regulatory standards or explain its decision.</p>
                    </div>
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <div class="text-2xl mb-2">🧠</div>
                        <h4 class="font-semibold text-gray-800 mb-2">Claude Alone</h4>
                        <p class="text-sm text-gray-600">Rich reasoning but no structured detection output, inconsistent bounding box localization, and no fine-tuned knowledge of surgical instrument defect morphology.</p>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-2xl mb-2">✓</div>
                        <h4 class="font-semibold text-gray-800 mb-2">Together</h4>
                        <p class="text-sm text-gray-600">YOLOv8s grounds the analysis in pixel-level evidence. Claude interprets that evidence in regulatory context. Together they mirror expert QC inspector reasoning.</p>
                    </div>
                </div>
            </div>

            <!-- FDA Compliance -->
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">FDA Compliance Pathway</h3>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">21 CFR Part 820</div>
                        <p class="text-sm text-gray-700">Quality System Regulation. Every inspection generates a Device History Record with timestamp, user ID, AI decision, confidence score, and full reasoning.</p>
                    </div>
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">21 CFR Part 11</div>
                        <p class="text-sm text-gray-700">Electronic Records. The audit log is append-only, timestamped, and user-attributed. Every decision is traceable to an authenticated inspector.</p>
                    </div>
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">ISO 13485</div>
                        <p class="text-sm text-gray-700">AI augments human QC review — it does not replace it. Final disposition remains with a qualified inspector. The system recommends; humans decide.</p>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-sm text-amber-800">
                        <strong>Positioning:</strong> Cosmas Damian is designed to <em>support</em> FDA-compliant workflows, not to replace regulatory review.
                        The system makes recommendations; qualified personnel make final decisions. This architecture is consistent with FDA guidance on AI/ML-based Software as a Medical Device (SaMD).
                    </p>
                </div>
            </div>

            <!-- CTA -->
            <div class="text-center">
                <a href="{{ route('inspections.upload') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition text-lg">
                    Run an Inspection →
                </a>
                <a href="{{ route('model.stats') }}" class="inline-block ml-4 bg-white hover:bg-gray-50 text-gray-700 font-bold py-3 px-8 rounded-xl border border-gray-300 transition text-lg">
                    View Model Stats
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
