@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Inspection Results</h1>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Image -->
            <div class="col-span-1 md:col-span-2">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Instrument Image</h2>
                    @if ($inspection->image_path)
                        <img 
                            src="{{ asset('storage/' . $inspection->image_path) }}" 
                            alt="Inspection Image"
                            class="w-full rounded-lg border border-gray-200"
                        >
                    @else
                        <div class="bg-gray-100 rounded-lg h-64 flex items-center justify-center text-gray-500">
                            No image available
                        </div>
                    @endif
                </div>
            </div>

            <!-- Status Badge -->
            <div class="col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Status</h2>
                    
                    @if ($inspection->pass_fail === 'PASS')
                        <div class="bg-green-100 border-4 border-green-500 rounded-lg p-4 text-center">
                            <p class="text-4xl font-bold text-green-600">✓</p>
                            <p class="text-2xl font-bold text-green-600 mt-2">PASS</p>
                        </div>
                    @elseif ($inspection->pass_fail === 'FAIL')
                        <div class="bg-red-100 border-4 border-red-500 rounded-lg p-4 text-center">
                            <p class="text-4xl font-bold text-red-600">✗</p>
                            <p class="text-2xl font-bold text-red-600 mt-2">FAIL</p>
                        </div>
                    @else
                        <div class="bg-yellow-100 border-4 border-yellow-500 rounded-lg p-4 text-center">
                            <p class="text-4xl font-bold text-yellow-600">⏳</p>
                            <p class="text-2xl font-bold text-yellow-600 mt-2">{{ $inspection->pass_fail }}</p>
                        </div>
                    @endif

                    @if ($inspection->confidence)
                        <div class="mt-4">
                            <p class="text-sm text-gray-600 mb-2">Confidence</p>
                            <div class="w-full bg-gray-200 rounded-full h-4">
                                <div 
                                    class="bg-blue-600 h-4 rounded-full" 
                                    style="width: {{ $inspection->confidence }}%"
                                ></div>
                            </div>
                            <p class="text-sm font-medium text-gray-700 mt-1">{{ $inspection->confidence }}%</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Detailed Analysis -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Analysis Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if ($inspection->defect_type)
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Defect Type</p>
                        <p class="text-lg font-semibold text-gray-800">{{ $inspection->defect_type }}</p>
                    </div>
                @endif

                @if ($inspection->confidence)
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Confidence Level</p>
                        <p class="text-lg font-semibold text-gray-800">{{ $inspection->confidence }}%</p>
                    </div>
                @endif
            </div>

            @if ($inspection->claude_reasoning)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-2">AI Reasoning</p>
                    <div class="bg-gray-50 rounded-lg p-4 text-gray-700 whitespace-pre-wrap">
                        {{ $inspection->claude_reasoning }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Metadata -->
        <div class="bg-gray-50 rounded-lg p-6 mb-8">
            <h2 class="text-lg font-semibold mb-4">Metadata</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-gray-600">Inspection ID</p>
                    <p class="font-monospace font-medium">{{ $inspection->id }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Analyzed</p>
                    <p class="font-medium">{{ $inspection->updated_at->format('M d, Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Uploaded</p>
                    <p class="font-medium">{{ $inspection->created_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-4 justify-center">
            <a href="{{ route('inspections.upload') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition">
                New Inspection
            </a>
            <a href="{{ route('inspections.audit-log') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition">
                Audit Log
            </a>
        </div>
    </div>
</div>
@endsection
