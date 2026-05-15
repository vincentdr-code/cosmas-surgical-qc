<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Request;

class RoiController extends Controller
{
    public function index(Request $request)
    {
        $inspectionsPerMonth = (int) $request->input('inspections_per_month', 10000);
        $manualCostPer       = (float) $request->input('manual_cost_per', 0.15);
        $aiCostPer           = 0.01;

        $inspectionsPerMonth = max(100, min(1000000, $inspectionsPerMonth));
        $manualCostPer       = max(0.05, min(10.0, $manualCostPer));

        $manualMonthly   = $inspectionsPerMonth * $manualCostPer;
        $aiMonthly       = $inspectionsPerMonth * $aiCostPer;
        $savingsMonthly  = $manualMonthly - $aiMonthly;
        $savingsAnnual   = $savingsMonthly * 12;

        $implementationCost = 45000;
        $threeYearSavings   = $savingsAnnual * 3;
        $roi3yr             = round((($threeYearSavings - $implementationCost) / $implementationCost) * 100, 1);
        $paybackMonths      = $savingsMonthly > 0 ? ceil($implementationCost / $savingsMonthly) : null;

        $manualSecondsPerMonth = $inspectionsPerMonth * 45;
        $aiSecondsPerMonth     = $inspectionsPerMonth * 0.2;
        $hoursSaved            = round(($manualSecondsPerMonth - $aiSecondsPerMonth) / 3600, 1);

        $totalInspections = Inspection::count();
        $actualSaved      = $totalInspections * ($manualCostPer - $aiCostPer);
        $passRate         = $totalInspections > 0
            ? round((Inspection::whereRaw("UPPER(pass_fail) = 'PASS'")->count() / $totalInspections) * 100, 1)
            : 0;
        $defectsCaught    = Inspection::whereRaw("UPPER(pass_fail) = 'FAIL'")->count();

        return view('roi', compact(
            'inspectionsPerMonth', 'manualCostPer', 'aiCostPer',
            'manualMonthly', 'aiMonthly', 'savingsMonthly', 'savingsAnnual',
            'implementationCost', 'threeYearSavings', 'roi3yr', 'paybackMonths',
            'hoursSaved', 'totalInspections', 'actualSaved', 'passRate', 'defectsCaught'
        ));
    }
}
