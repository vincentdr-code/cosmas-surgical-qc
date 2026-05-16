<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            New Inspection — Upload Instrument Image
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Pipeline reminder -->
            <div class="bg-slate-800 rounded-xl p-4 mb-6 text-white flex items-center gap-6 text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-blue-600 rounded flex items-center justify-center text-xs font-bold">1</div>
                    <span class="text-slate-300">YOLOv8s Vision <span class="text-blue-400">~200ms</span></span>
                </div>
                <span class="text-slate-600">→</span>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-purple-600 rounded flex items-center justify-center text-xs font-bold">2</div>
                    <span class="text-slate-300">Claude Reasoning <span class="text-purple-400">1-3s</span></span>
                </div>
                <span class="text-slate-600">→</span>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-green-600 rounded flex items-center justify-center text-xs font-bold">✓</div>
                    <span class="text-slate-300">PASS / FAIL + Audit Log</span>
                </div>
            </div>

            <!-- Upload form -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <form id="uploadForm" method="POST" action="{{ route('inspections.store') }}" enctype="multipart/form-data">
                    @csrf

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                            {{ $errors->first('image') }}
                        </div>
                    @endif

                    <!-- Drop zone -->
                    <div id="dropZone"
                         class="border-2 border-dashed border-gray-300 rounded-xl p-10 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all duration-200"
                         onclick="document.getElementById('imageInput').click()">
                        <div id="dropContent">
                            <svg class="w-14 h-14 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-lg font-medium text-gray-600 mb-1">Drop image here or click to browse</p>
                            <p class="text-sm text-gray-400">JPEG, PNG — max 10MB</p>
                        </div>
                        <!-- Preview -->
                        <div id="previewContainer" class="hidden">
                            <img id="previewImage" src="" alt="Preview" class="max-h-64 mx-auto rounded-lg shadow">
                            <p id="previewName" class="text-sm text-gray-500 mt-3"></p>
                        </div>
                    </div>

                    <input type="file" id="imageInput" name="image" accept="image/jpeg,image/png,image/jpg" class="hidden">

                    <!-- Submit -->
                    <button type="submit" id="submitBtn"
                            class="mt-6 w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-4 px-6 rounded-xl transition text-lg flex items-center justify-center gap-3">
                        <span id="submitText">Select an image to begin</span>
                        <svg id="submitSpinner" class="hidden w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </button>
                    <p class="text-center text-xs text-gray-400 mt-2">Analysis typically takes 5–10 seconds</p>
                </form>
            </div>

            <!-- Demo images -->
            <div class="mt-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-sm text-gray-500 font-medium">Or try a demo image</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    @foreach([
                        ['type' => 'corrosion', 'label' => 'Surface Corrosion', 'desc' => 'Rust / oxidation on metal body', 'color' => 'red',    'icon' => '🔴', 'expect' => 'Expect: FAIL'],
                        ['type' => 'scratch',   'label' => 'Linear Scratch',    'desc' => 'Surface scratch defect',         'color' => 'yellow', 'icon' => '🟡', 'expect' => 'Expect: FAIL'],
                        ['type' => 'clean',     'label' => 'Clean Instrument',  'desc' => 'Surgical forceps — conforming',  'color' => 'green',  'icon' => '✅', 'expect' => 'Expect: PASS'],
                    ] as $demo)
                        <a href="{{ route('demo', ['type' => $demo['type']]) }}"
                           class="block bg-white rounded-xl shadow p-4 hover:shadow-lg hover:border-blue-400 border-2 border-transparent transition group">
                            <div class="h-24 rounded-lg mb-3 flex flex-col items-center justify-center
                                @if($demo['color'] === 'red') bg-red-50
                                @elseif($demo['color'] === 'yellow') bg-yellow-50
                                @else bg-green-50 @endif">
                                <span class="text-3xl mb-1">{{ $demo['icon'] }}</span>
                                <span class="text-xs font-semibold
                                    @if($demo['color'] === 'red') text-red-600
                                    @elseif($demo['color'] === 'yellow') text-yellow-600
                                    @else text-green-600 @endif">
                                    {{ $demo['expect'] }}
                                </span>
                            </div>
                            <p class="text-sm font-bold text-gray-800 group-hover:text-blue-600">{{ $demo['label'] }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $demo['desc'] }}</p>
                            <p class="text-xs text-blue-500 mt-2 font-medium group-hover:underline">→ Run AI inspection</p>
                        </a>
                    @endforeach
                </div>
                <p class="text-center text-xs text-gray-400 mt-3">One click runs the full two-stage AI pipeline — no upload needed</p>
            </div>

            <!-- What gets analyzed -->
            <div class="mt-6 bg-white rounded-xl shadow p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">What the AI checks for</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach(['Surface corrosion / rust', 'Cracks and fractures', 'Linear scratches', 'Porosity / voids', 'Dimensional anomalies', 'Conforming (no defect)'] as $item)
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="text-blue-500">◈</span> {{ $item }}
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    <script>
        const input = document.getElementById('imageInput');
        const dropZone = document.getElementById('dropZone');
        const dropContent = document.getElementById('dropContent');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        const previewName = document.getElementById('previewName');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitSpinner = document.getElementById('submitSpinner');

        submitBtn.disabled = true;

        function showPreview(file) {
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                previewImage.src = e.target.result;
                previewName.textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
                dropContent.classList.add('hidden');
                previewContainer.classList.remove('hidden');
                submitBtn.disabled = false;
                submitText.textContent = 'Run AI Inspection →';
                dropZone.classList.add('border-blue-400', 'bg-blue-50');
            };
            reader.readAsDataURL(file);
        }

        input.addEventListener('change', () => showPreview(input.files[0]));

        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-blue-500', 'bg-blue-50'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-blue-500', 'bg-blue-50'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                input.files = e.dataTransfer.files;
                showPreview(file);
            }
        });

        document.getElementById('uploadForm').addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitText.textContent = 'Analyzing with AI...';
            submitSpinner.classList.remove('hidden');
        });
    </script>
</x-app-layout>
