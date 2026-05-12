@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Inspection Audit Log</h1>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        @if ($inspections->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">ID</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Defect Type</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Confidence</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Analyzed</th>
                            <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($inspections as $inspection)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm font-monospace text-gray-600">
                                    #{{ $inspection->id }}
                                </td>
                                <td class="px-6 py-4">
                                    @if ($inspection->pass_fail === 'PASS')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                            ✓ PASS
                                        </span>
                                    @elseif ($inspection->pass_fail === 'FAIL')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                            ✗ FAIL
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                            ⏳ {{ $inspection->pass_fail }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $inspection->defect_type ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    @if ($inspection->confidence)
                                        {{ $inspection->confidence }}%
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $inspection->updated_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('inspections.results', $inspection->id) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t">
                {{ $inspections->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">No inspections yet</h3>
                <p class="mt-1 text-gray-600">Start by uploading an instrument image for analysis.</p>
                <a href="{{ route('inspections.upload') }}" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition">
                    Upload Image
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
