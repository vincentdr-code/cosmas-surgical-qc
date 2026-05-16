<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            QC Settings — Confidence Threshold
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">

                @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                    {{ session('success') }}
                </div>
                @endif

                <h3 class="text-lg font-semibold text-gray-900">AI Confidence Threshold</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Set the minimum AI confidence required to auto-mark an inspection as
                    <strong>PASS</strong>. Inspections below this threshold are flagged for
                    human review. This keeps the AI in an augmentation role — not a replacement.
                </p>

                <form method="POST" action="{{ route('settings.threshold.update') }}" class="mt-6 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="confidence_threshold" class="block text-sm font-medium text-gray-700">
                            PASS floor (%)
                        </label>
                        <div class="mt-1 flex items-center gap-4">
                            <input type="range" name="confidence_threshold" id="confidence_threshold"
                                   min="50" max="99" step="1"
                                   value="{{ old('confidence_threshold', $threshold) }}"
                                   class="w-full accent-blue-600"
                                   oninput="document.getElementById('thresh_display').textContent = this.value + '%'">
                            <span id="thresh_display" class="text-2xl font-bold text-blue-600 w-16 text-right">
                                {{ $threshold }}%
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">
                            Inspections with AI confidence ≥ threshold → PASS &nbsp;|&nbsp;
                            &lt; threshold → FLAGGED for human review
                        </p>
                    </div>

                    <button type="submit"
                            class="w-full py-2.5 px-4 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                        Save Threshold
                    </button>
                </form>

                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700">
                    <strong>FDA Note:</strong> All inspection decisions, including threshold changes, are
                    recorded in the audit log per 21 CFR Part 11. The AI confidence score is a recommendation
                    only — human QC sign-off is always available.
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
