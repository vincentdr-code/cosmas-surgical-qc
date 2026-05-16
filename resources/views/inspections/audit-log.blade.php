@extends('layouts.app')
@section('content')
<div class="container mx-auto px-4 py-8">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <h1 class="text-3xl font-bold">Inspection Audit Log</h1>
        <div class="flex items-center gap-3">
            {{-- Status filter --}}
            <form method="GET" action="{{ route('inspections.audit-log') }}" class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Statuses</option>
                    <option value="PASS"    {{ request('status') === 'PASS'    ? 'selected' : '' }}>PASS</option>
                    <option value="FAIL"    {{ request('status') === 'FAIL'    ? 'selected' : '' }}>FAIL</option>
                    <option value="FLAGGED" {{ request('status') === 'FLAGGED' ? 'selected' : '' }}>FLAGGED</option>
                </select>
            </form>
            {{-- CSV Export --}}
            <a href="{{ route('inspections.export-csv') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </a>
        </div>
    </div>

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
                                <td class="px-6 py-4 text-sm font-mono text-gray-600">#{{ $inspection->id }}</td>
                                <td class="px-6 py-4">
                                    @php $pf = strtoupper($inspection->pass_fail ?? ''); @endphp
                                    @if ($pf === 'PASS')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">✓ PASS</span>
                                    @elseif ($pf === 'FAIL')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">✗ FAIL</span>
                                    @elseif ($pf === 'FLAGGED')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">⚑ FLAGGED</span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">{{ $inspection->pass_fail }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $inspection->defect_type ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $inspection->confidence ? round($inspection->confidence) . '%' : '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $inspection->updated_at->format('M d, Y H:i') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('inspections.results', $inspection->id) }}"
                                       class="text-blue-600 hover:text-blue-800 font-medium text-sm">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t">{{ $inspections->links() }}</div>
        @else
            <div class="p-12 text-center">
                <h3 class="mt-2 text-lg font-medium text-gray-900">No inspections yet</h3>
                <p class="mt-1 text-gray-600">Start by uploading an instrument image for analysis.</p>
                <a href="{{ route('inspections.upload') }}"
                   class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition">
                    Upload Image
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
