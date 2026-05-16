<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Model Stats — YOLOv8s Defect Detector
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Live Status Banner -->
            <div class="rounded-xl p-4 mb-6 flex items-center gap-4 {{ $yoloStatus['online'] ? 'bg-green-50 border border-green-300' : 'bg-red-50 border border-red-300' }}">
                <div class="w-3 h-3 rounded-full {{ $yoloStatus['online'] ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></div>
                <div>
                    <span class="font-semibold {{ $yoloStatus['online'] ? 'text-green-800' : 'text-red-800' }}">
                        YOLOv8 Inference Service: {{ $yoloStatus['online'] ? 'ONLINE' : 'OFFLINE' }}
                    </span>
                    @if($yoloStatus['online'])
                        <span class="text-green-600 text-sm ml-2">— {{ $yoloStatus['model'] ?? 'model loaded' }}</span>
                    @else
                        <span class="text-red-600 text-sm ml-2">— Service unavailable, Claude-only mode active</span>
                    @endif
                </div>
            </div>

            <!-- Key Metrics Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow p-5 text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ number_format($trainingStats['total_images']) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Training Images</div>
                </div>
                <div class="bg-white rounded-xl shadow p-5 text-center">
                    <div class="text-3xl font-bold text-purple-600">{{ $trainingStats['best_map50'] * 100 }}%</div>
                    <div class="text-sm text-gray-500 mt-1">Best mAP50</div>
                </div>
                <div class="bg-white rounded-xl shadow p-5 text-center">
                    <div class="text-3xl font-bold text-green-600">{{ $trainingStats['params'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">Parameters</div>
                </div>
                <div class="bg-white rounded-xl shadow p-5 text-center">
                    <div class="text-3xl font-bold text-orange-600">~200ms</div>
                    <div class="text-sm text-gray-500 mt-1">Inference Speed</div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <!-- Training Configuration -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Training Configuration</h3>
                    <div class="space-y-3">
                        @foreach([
                            ['Model', $trainingStats['model'] . ' (' . $trainingStats['params'] . ' params, ' . $trainingStats['gflops'] . ' GFLOPs)'],
                            ['Optimizer', $trainingStats['optimizer']],
                            ['Learning Rate', $trainingStats['lr0'] . ' (cosine decay → 1e-5)'],
                            ['Weight Decay', $trainingStats['weight_decay'] . ' (L2 regularization)'],
                            ['Batch Size', $trainingStats['batch']],
                            ['Image Size', $trainingStats['imgsz'] . '×' . $trainingStats['imgsz']],
                            ['Early Stopping', 'patience=20 epochs'],
                            ['Warmup', '5 epochs'],
                            ['Hardware', $trainingStats['hardware']],
                            ['Training Status', $trainingStats['epochs_trained']],
                        ] as [$label, $value])
                            <div class="flex justify-between items-start text-sm py-2 border-b border-gray-50 last:border-0">
                                <span class="text-gray-500 shrink-0 mr-4">{{ $label }}</span>
                                <span class="font-medium text-gray-800 text-right">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Validation Performance</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">mAP50 (primary metric)</span>
                                <span class="font-bold text-blue-600">{{ ($trainingStats['best_map50'] * 100) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-blue-600 h-3 rounded-full" style="width: {{ $trainingStats['best_map50'] * 100 }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">mAP50-95 (strict)</span>
                                <span class="font-bold text-purple-600">{{ ($trainingStats['best_map50_95'] * 100) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-purple-600 h-3 rounded-full" style="width: {{ $trainingStats['best_map50_95'] * 100 }}%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 p-3 bg-blue-50 rounded-lg">
                        <p class="text-xs text-blue-800">
                            <strong>Training in progress.</strong> Model is still training on Google Colab T4 GPU with patience=20 early stopping.
                            Stats shown are best checkpoint so far. Final model will be deployed when training completes.
                        </p>
                    </div>

                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Anti-Overfitting Measures</h4>
                        <div class="space-y-1">
                            @foreach(['AdamW optimizer (decoupled weight decay)', 'patience=20 early stopping', 'Mosaic augmentation (90 epochs)', 'close_mosaic=10 (real images last 10 epochs)', 'copy_paste=0.1 (rare class augmentation)', 'mixup=0.05 (soft label blending)'] as $measure)
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <span class="text-green-500">✓</span> {{ $measure }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Distribution -->
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Class Distribution</h3>
                <p class="text-sm text-gray-500 mb-5">Annotation counts per class across training set. Corrosion is subsampled from 8,354-image dataset to prevent class dominance.</p>
                @php $maxAnn = max(array_column($trainingStats['classes'], 'train_ann')) ?: 1; @endphp
                <div class="space-y-3">
                    @foreach($trainingStats['classes'] as $class)
                        <div class="flex items-center gap-3">
                            <div class="w-24 text-sm font-medium text-gray-700 shrink-0">{{ $class['name'] }}</div>
                            <div class="flex-1 bg-gray-100 rounded-full h-6 relative">
                                @if($class['train_ann'] > 0)
                                    <div class="bg-blue-500 h-6 rounded-full flex items-center pl-2"
                                         style="width: {{ max(3, $class['train_ann'] / $maxAnn * 100) }}%">
                                        <span class="text-white text-xs font-bold whitespace-nowrap">{{ number_format($class['train_ann']) }}</span>
                                    </div>
                                @else
                                    <div class="flex items-center h-6 pl-2">
                                        <span class="text-xs text-amber-600 font-semibold">0 — v2 roadmap</span>
                                    </div>
                                @endif
                            </div>
                            <div class="w-20 text-xs text-gray-500 shrink-0 text-right">val: {{ number_format($class['val_ann']) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Dataset Sources -->
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Training Dataset Sources</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 text-gray-500 font-semibold">#</th>
                                <th class="text-left py-2 text-gray-500 font-semibold">Dataset</th>
                                <th class="text-left py-2 text-gray-500 font-semibold">Images</th>
                                <th class="text-left py-2 text-gray-500 font-semibold">License</th>
                                <th class="text-left py-2 text-gray-500 font-semibold">Source Classes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trainingStats['datasets'] as $i => $ds)
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="py-3 text-gray-400">{{ $i + 1 }}</td>
                                    <td class="py-3 font-medium text-gray-800">{{ $ds['name'] }}</td>
                                    <td class="py-3 text-gray-600">{{ number_format($ds['images']) }}</td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 bg-green-50 text-green-700 rounded text-xs">{{ $ds['license'] }}</span>
                                    </td>
                                    <td class="py-3 text-gray-500 text-xs">{{ $ds['classes'] }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-50 font-semibold">
                                <td class="py-3" colspan="2">Total</td>
                                <td class="py-3 text-blue-600">{{ number_format($trainingStats['total_images']) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CTA -->
            <div class="text-center">
                <a href="{{ route('pipeline') }}" class="inline-block bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 px-8 rounded-xl transition">
                    ← How It Works
                </a>
                <a href="{{ route('inspections.upload') }}" class="inline-block ml-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition">
                    Run an Inspection →
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
