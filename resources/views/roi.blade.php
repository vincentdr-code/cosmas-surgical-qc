<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ROI Calculator — AI vs. Manual Inspection
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Adjust Your Parameters</h3>
                <form method="GET" action="{{ route('roi') }}" class="flex flex-wrap gap-6 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Inspections / Month</label>
                        <input type="number" name="inspections_per_month" value="{{ $inspectionsPerMonth }}" min="100" max="1000000" step="100"
                               class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-40 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Manual Cost / Inspection ($)</label>
                        <input type="number" name="manual_cost_per" value="{{ $manualCostPer }}" min="0.05" max="10" step="0.01"
                               class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-40 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                        Recalculate
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Monthly Savings</p>
                    <p class="mt-2 text-3xl font-bold text-green-600">${{ number_format($savingsMonthly, 0) }}</p>
                    <p class="mt-1 text-xs text-gray-400">AI vs. manual labour</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Annual Savings</p>
                    <p class="mt-2 text-3xl font-bold text-green-600">${{ number_format($savingsAnnual, 0) }}</p>
                    <p class="mt-1 text-xs text-gray-400">12-month projection</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">3-Year ROI</p>
                    <p class="mt-2 text-3xl font-bold text-blue-600">{{ $roi3yr }}%</p>
                    <p class="mt-1 text-xs text-gray-400">vs. ${{ number_format($implementationCost, 0) }} implementation</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Payback Period</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $paybackMonths ? $paybackMonths . ' mo' : '—' }}</p>
                    <p class="mt-1 text-xs text-gray-400">Break-even on deployment</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-6">Monthly Cost Breakdown</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-6 bg-red-50 rounded-xl border border-red-100">
                        <p class="text-xs font-semibold text-red-500 uppercase tracking-wide">Manual Inspection</p>
                        <p class="mt-3 text-4xl font-black text-red-600">${{ number_format($manualMonthly, 0) }}</p>
                        <p class="mt-2 text-sm text-red-400">{{ number_format($inspectionsPerMonth) }} × ${{ $manualCostPer }}/inspection</p>
                        <p class="mt-1 text-xs text-red-400">~45 sec/inspection · human error risk</p>
                    </div>
                    <div class="text-center p-6 bg-green-50 rounded-xl border border-green-100 flex flex-col items-center justify-center">
                        <p class="text-xs font-semibold text-green-600 uppercase tracking-wide">You Save</p>
                        <p class="mt-3 text-5xl font-black text-green-600">${{ number_format($savingsMonthly, 0) }}</p>
                        <p class="mt-2 text-sm text-green-500">per month</p>
                        <p class="mt-1 text-xs text-green-400">{{ $hoursSaved }} labour hours freed</p>
                    </div>
                    <div class="text-center p-6 bg-blue-50 rounded-xl border border-blue-100">
                        <p class="text-xs font-semibold text-blue-500 uppercase tracking-wide">Cosmas AI</p>
                        <p class="mt-3 text-4xl font-black text-blue-600">${{ number_format($aiMonthly, 0) }}</p>
                        <p class="mt-2 text-sm text-blue-400">{{ number_format($inspectionsPerMonth) }} × ${{ $aiCostPer }}/inspection</p>
                        <p class="mt-1 text-xs text-blue-400">~200ms/inspection · audit trail included</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-indigo-600 to-blue-700 rounded-xl shadow-sm p-6 text-white">
                <h3 class="text-lg font-bold mb-4">Live System Performance</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-indigo-200 text-xs font-semibold uppercase tracking-wide">Inspections Run</p>
                        <p class="mt-1 text-3xl font-black">{{ number_format($totalInspections) }}</p>
                    </div>
                    <div>
                        <p class="text-indigo-200 text-xs font-semibold uppercase tracking-wide">Actual Saved</p>
                        <p class="mt-1 text-3xl font-black">${{ number_format($actualSaved, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-indigo-200 text-xs font-semibold uppercase tracking-wide">Pass Rate</p>
                        <p class="mt-1 text-3xl font-black">{{ $passRate }}%</p>
                    </div>
                    <div>
                        <p class="text-indigo-200 text-xs font-semibold uppercase tracking-wide">Defects Caught</p>
                        <p class="mt-1 text-3xl font-black">{{ $defectsCaught }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Model Assumptions</h3>
                <div class="text-sm text-gray-500 space-y-1">
                    <p>· Manual inspection cost based on US medical device industry average: $18–25/hour labour rate, 45 seconds per instrument.</p>
                    <p>· AI cost ($0.01/inspection) includes YOLOv8 inference (~$0.001) + Claude API vision analysis (~$0.009) per inspection.</p>
                    <p>· Implementation cost (${{ number_format($implementationCost, 0) }}) is a conservative estimate for mid-size manufacturer integration.</p>
                    <p>· 3-year ROI methodology aligned with Forrester Research TEI framework. Excludes intangible benefits (reduced liability, improved patient safety).</p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
