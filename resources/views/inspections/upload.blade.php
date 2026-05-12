@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Surgical Instrument Inspection</h1>

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-lg p-8">
            <form action="{{ route('inspections.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-6">
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                        Upload Instrument Image
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition">
                        <input 
                            type="file" 
                            id="image" 
                            name="image" 
                            accept="image/*"
                            class="hidden"
                            required
                            onchange="updateFileName(this)"
                        >
                        <label for="image" class="cursor-pointer">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20a4 4 0 004 4h24a4 4 0 004-4V20m-8-12v12m0 0l-3-3m3 3l3-3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">
                                <span class="font-medium text-blue-600 hover:text-blue-500">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                            <p id="fileName" class="mt-2 text-sm font-medium text-gray-700"></p>
                        </label>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200"
                >
                    Analyze Instrument
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-gray-200">
                <h2 class="text-lg font-semibold mb-4">How it works</h2>
                <ol class="list-decimal list-inside space-y-2 text-gray-700">
                    <li>Upload a clear image of the surgical instrument</li>
                    <li>Our AI analyzes the image for defects</li>
                    <li>Results are saved to the audit log</li>
                    <li>Review detailed analysis and recommendations</li>
                </ol>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('inspections.audit-log') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                View Audit Log →
            </a>
        </div>
    </div>
</div>

<script>
function updateFileName(input) {
    const fileName = input.files[0]?.name || '';
    document.getElementById('fileName').textContent = fileName ? 'Selected: ' + fileName : '';
}
</script>
@endsection
