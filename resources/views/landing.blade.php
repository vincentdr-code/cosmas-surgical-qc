<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cosmas Sentry | AI Defect Detection for Surgical Instruments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-hero { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #1e40af 100%); }
        .stat-card { backdrop-filter: blur(10px); background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); }
        @keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:0.4} }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gray-950 text-white">

    <!-- Nav -->
    <nav class="border-b border-white/10 px-6 py-4">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <span class="font-bold text-lg tracking-tight">Cosmas Sentry</span>
                <span class="hidden sm:inline text-xs text-blue-400 bg-blue-400/10 px-2 py-0.5 rounded-full">AI Quality Control</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-sm text-gray-300 hover:text-white transition">Sign in</a>
                <a href="{{ route('register') }}" class="text-sm bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg font-medium transition">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="gradient-hero px-6 py-24">
        <div class="max-w-5xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 bg-blue-500/20 border border-blue-500/30 rounded-full px-4 py-1.5 text-sm text-blue-300 mb-8">
                <span class="w-2 h-2 bg-green-400 rounded-full pulse-dot"></span>
                Live on AWS · YOLOv8s + Claude AI · FDA 21 CFR Part 11 audit trail
            </div>

            <h1 class="text-4xl sm:text-6xl font-black leading-tight mb-6">
                AI-Powered Defect Detection<br>
                <span class="text-blue-400">for Surgical Instruments</span>
            </h1>

            <p class="text-lg sm:text-xl text-gray-300 max-w-3xl mx-auto mb-10 leading-relaxed">
                Cosmas Sentry replaces manual visual inspection with a two-stage AI pipeline —
                YOLOv8s computer vision pre-screening followed by Claude reasoning —
                reducing QC costs by <strong class="text-white">93%</strong> while improving defect catch rates.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                <a href="{{ route('register') }}"
                   class="w-full sm:w-auto bg-blue-600 hover:bg-blue-500 text-white font-bold py-4 px-8 rounded-xl text-lg transition">
                    Try it Free →
                </a>
                <a href="{{ route('login') }}"
                   class="w-full sm:w-auto bg-white/10 hover:bg-white/20 border border-white/20 text-white font-medium py-4 px-8 rounded-xl text-lg transition">
                    Sign In
                </a>
            </div>

            <!-- Stats row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-3xl mx-auto">
                @foreach([
                    ['value' => '10,764', 'label' => 'Training Images', 'sub' => '4 datasets, 6 defect classes'],
                    ['value' => '76.4%',  'label' => 'mAP50 Accuracy',  'sub' => 'YOLOv8s fine-tuned'],
                    ['value' => '~200ms', 'label' => 'Detection Speed', 'sub' => 'GPU inference time'],
                    ['value' => '93%',    'label' => 'Cost Reduction',  'sub' => 'vs. manual inspection'],
                ] as $stat)
                <div class="stat-card rounded-xl p-4 text-center">
                    <div class="text-2xl sm:text-3xl font-black text-white">{{ $stat['value'] }}</div>
                    <div class="text-sm font-semibold text-blue-300 mt-1">{{ $stat['label'] }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ $stat['sub'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Problem / Solution -->
    <section class="bg-gray-900 px-6 py-20">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-3xl font-bold text-center mb-4">The Problem with Manual QC</h2>
            <p class="text-gray-400 text-center max-w-2xl mx-auto mb-12">
                Surgical instrument defects are a patient safety issue. Manual inspection is slow, expensive, and inconsistent.
            </p>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach([
                    ['icon' => '💸', 'title' => 'High Cost',        'desc' => 'Manual inspection costs $0.15–$0.25 per instrument. At 10,000 units/month, that\'s $1,500–$2,500 in direct labor.'],
                    ['icon' => '👁️', 'title' => 'Human Error',       'desc' => 'Visual fatigue causes inspectors to miss up to 15% of defects. Corrosion and micro-cracks are especially hard to spot.'],
                    ['icon' => '📋', 'title' => 'Audit Gaps',        'desc' => 'FDA 21 CFR Part 11 requires full electronic records. Paper-based QC creates compliance risk and recall exposure.'],
                ] as $item)
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="text-3xl mb-3">{{ $item['icon'] }}</div>
                    <h3 class="font-bold text-white text-lg mb-2">{{ $item['title'] }}</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">{{ $item['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="bg-gray-950 px-6 py-20">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-3xl font-bold text-center mb-4">Two-Stage AI Pipeline</h2>
            <p class="text-gray-400 text-center max-w-2xl mx-auto mb-12">
                We combine computer vision speed with large language model reasoning for results no single model can match.
            </p>
            <div class="flex flex-col md:flex-row items-center gap-4">
                <div class="flex-1 bg-blue-900/30 border border-blue-700/40 rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-sm">1</div>
                        <h3 class="font-bold text-blue-300">YOLOv8s Vision</h3>
                        <span class="ml-auto text-xs text-blue-400 bg-blue-400/10 px-2 py-1 rounded">~200ms</span>
                    </div>
                    <p class="text-gray-300 text-sm leading-relaxed">Fine-tuned on 10,764 surgical instrument defect images across 6 classes. Identifies corrosion, cracks, scratches, porosity, and misalignment at GPU speed.</p>
                    <div class="mt-4 grid grid-cols-3 gap-2">
                        @foreach(['Cracks', 'Corrosion', 'Scratches', 'Porosity', 'Misalignment', 'No Defect'] as $cls)
                        <span class="text-xs bg-blue-800/40 text-blue-200 px-2 py-1 rounded text-center">{{ $cls }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="text-3xl text-gray-600 font-bold hidden md:block">→</div>

                <div class="flex-1 bg-purple-900/30 border border-purple-700/40 rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center font-bold text-sm">2</div>
                        <h3 class="font-bold text-purple-300">Claude AI Reasoning</h3>
                        <span class="ml-auto text-xs text-purple-400 bg-purple-400/10 px-2 py-1 rounded">1–3s</span>
                    </div>
                    <p class="text-gray-300 text-sm leading-relaxed">Claude receives the image plus YOLO's detections and produces a calibrated PASS/FAIL verdict with confidence score, regulatory note (ISO 7153-1, FDA 21 CFR), and recommended action.</p>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        @foreach(['PASS / FAIL verdict', 'Confidence score', 'Regulatory note', 'Audit trail'] as $f)
                        <span class="text-xs bg-purple-800/40 text-purple-200 px-2 py-1 rounded">{{ $f }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Compliance -->
    <section class="bg-gray-900 px-6 py-20">
        <div class="max-w-5xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4">Built for FDA-Regulated Environments</h2>
            <p class="text-gray-400 max-w-2xl mx-auto mb-10">Every inspection creates an immutable electronic record. Cosmas Sentry is designed to support — not replace — human quality control judgment.</p>
            <div class="grid sm:grid-cols-3 gap-6">
                @foreach([
                    ['title' => 'FDA 21 CFR Part 11', 'desc' => 'Electronic records with user ID, timestamp, and AI decision logged on every inspection.'],
                    ['title' => 'ISO 13485 Aware',    'desc' => 'Reasoning references ISO 13485 QMS requirements and instrument standards.'],
                    ['title' => 'Human-in-the-Loop',  'desc' => 'AI augments — never replaces — the qualified person. All FAIL results flag for supervisor review.'],
                ] as $c)
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 text-left">
                    <div class="text-sm font-bold text-green-400 mb-2">✓ {{ $c['title'] }}</div>
                    <p class="text-gray-400 text-sm">{{ $c['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="gradient-hero px-6 py-20 text-center">
        <h2 class="text-4xl font-black mb-4">Ready to see it in action?</h2>
        <p class="text-gray-300 text-lg mb-8">Create a free account and run your first AI inspection in under 60 seconds.</p>
        <a href="{{ route('register') }}"
           class="inline-block bg-white text-blue-700 font-black py-4 px-10 rounded-xl text-lg hover:bg-blue-50 transition">
            Start Inspecting →
        </a>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-950 border-t border-white/10 px-6 py-8 text-center text-gray-500 text-sm">
        <p>Cosmas Sentry · AI-Powered QC for Surgical Instrument Manufacturing (SIC 3841)</p>
        <p class="mt-1">Built with YOLOv8s + Claude API · Deployed on AWS · CUA AI Vibe Coding Competition 2026</p>
    </footer>

</body>
</html>
