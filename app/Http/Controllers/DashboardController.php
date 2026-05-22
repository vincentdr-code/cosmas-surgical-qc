<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $total   = Inspection::count();
        $passed  = Inspection::whereRaw("UPPER(pass_fail) = 'PASS'")->count();
        $failed  = Inspection::whereRaw("UPPER(pass_fail) = 'FAIL'")->count();
        $flagged = Inspection::whereRaw("UPPER(pass_fail) = 'FLAGGED'")->count();

        $passRate      = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        $costSaved     = number_format($total * 0.14, 2);
        $recent        = Inspection::latest()->take(5)->get();
        $avgConfidence = Inspection::whereNotNull('confidence')
                            ->where('confidence', '>', 0)
                            ->avg('confidence');
        $avgConfidence = $avgConfidence ? round($avgConfidence, 1) : null;

        // 14-day trend data for Chart.js
        $days        = collect(range(13, 0))->map(fn($d) => Carbon::today()->subDays($d));
        $trendLabels = $days->map(fn($d) => $d->format('M j'))->values();

        $trendRows = Inspection::select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('UPPER(pass_fail) as verdict'),
                DB::raw('COUNT(*) as cnt')
            )
            ->where('created_at', '>=', Carbon::today()->subDays(13)->startOfDay())
            ->groupBy('day', 'verdict')
            ->get()
            ->groupBy('day');

        $trendPass    = [];
        $trendFail    = [];
        $trendFlagged = [];

        foreach ($days as $day) {
            $key   = $day->toDateString();
            $group = $trendRows->get($key, collect());
            $trendPass[]    = (int) ($group->firstWhere('verdict', 'PASS')?->cnt    ?? 0);
            $trendFail[]    = (int) ($group->firstWhere('verdict', 'FAIL')?->cnt    ?? 0);
            $trendFlagged[] = (int) ($group->firstWhere('verdict', 'FLAGGED')?->cnt ?? 0);
        }

        $lastInspection = Inspection::latest()->first();

        // Defect type breakdown - top 5 by count, excluding none
        $defectBreakdown = Inspection::select(
                DB::raw('LOWER(defect_type) as defect_type'),
                DB::raw('COUNT(*) as cnt')
            )
            ->whereNotNull('defect_type')
            ->whereRaw("LOWER(defect_type) != 'none'")
            ->groupBy('defect_type')
            ->orderByDesc('cnt')
            ->limit(5)
            ->get();

        // Risk level breakdown
        $riskBreakdown = Inspection::select(
                DB::raw('UPPER(risk_level) as risk_level'),
                DB::raw('COUNT(*) as cnt')
            )
            ->whereNotNull('risk_level')
            ->groupBy('risk_level')
            ->orderByDesc('cnt')
            ->get();

        $mode = request()->query('mode', 'both');

        return view('dashboard', compact(
            'total', 'passed', 'failed', 'flagged', 'passRate', 'costSaved',
            'recent', 'avgConfidence',
            'trendLabels', 'trendPass', 'trendFail', 'trendFlagged', 'lastInspection',
            'defectBreakdown', 'riskBreakdown', 'mode'
        ));
    }
}
