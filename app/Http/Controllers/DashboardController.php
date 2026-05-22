<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->input('mode', 'both');

        // Base query factory — fresh builder per call, mode-scoped
        $base = fn() => match($mode) {
            'single' => Inspection::whereNull('batch_id'),
            'batch'  => Inspection::whereNotNull('batch_id'),
            default  => Inspection::query(),
        };

        $total   = $base()->count();
        $passed  = $base()->whereRaw("UPPER(pass_fail) = 'PASS'")->count();
        $failed  = $base()->whereRaw("UPPER(pass_fail) = 'FAIL'")->count();
        $flagged = $base()->whereRaw("UPPER(pass_fail) = 'FLAGGED'")->count();

        $passRate      = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        $costSaved     = number_format($total * 0.14, 2);
        $recent        = $base()->latest()->take(5)->get();
        // Normalize confidence to 0-100 scale before averaging.
        // Legacy records store as 0-1 decimal; newer records store as 0-100.
        // Threshold: values <= 1.0 are treated as decimal fractions.
        $confidenceRows = $base()->whereNotNull('confidence')
                            ->where('confidence', '>', 0)
                            ->pluck('confidence');
        if ($confidenceRows->isNotEmpty()) {
            $normalized = $confidenceRows->map(fn($c) => $c <= 1 ? round($c * 100, 1) : round($c, 1));
            $avgConfidence = round($normalized->average(), 1);
        } else {
            $avgConfidence = null;
        }

        // ── 14-day trend data for Chart.js ──────────────────────────────────
        $days         = collect(range(13, 0))->map(fn($d) => Carbon::today()->subDays($d));
        $trendLabels  = $days->map(fn($d) => $d->format('M j'))->values();

        $trendRows = $base()
            ->select(
                DB::raw("DATE(created_at) as day"),
                DB::raw("UPPER(pass_fail) as verdict"),
                DB::raw("COUNT(*) as cnt")
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

        $lastInspection = $base()->latest()->first();

        // ── Defect intelligence ──────────────────────────────────────────────
        $defectBreakdown = $base()
            ->whereNotNull('defect_type')
            ->where('defect_type', '!=', '')
            ->where('defect_type', 'NOT LIKE', '%Unknown%')
            ->where('defect_type', 'NOT LIKE', '%Analysis Failed%')
            ->where('defect_type', 'NOT LIKE', '%analysis error%')
            ->where('defect_type', 'NOT LIKE', '%Analysis Error%')
            ->where('defect_type', 'NOT LIKE', '%No Defect%')
            ->select('defect_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('defect_type')
            ->orderByDesc('cnt')
            ->take(5)
            ->get();

        $riskBreakdown = $base()
            ->whereNotNull('risk_level')
            ->where('risk_level', '!=', '')
            ->select(DB::raw('UPPER(TRIM(risk_level)) as risk_level'), DB::raw('COUNT(*) as cnt'))
            ->groupBy(DB::raw('UPPER(TRIM(risk_level))'))
            ->orderByDesc('cnt')
            ->get();

        return view('dashboard', compact(
            'total', 'passed', 'failed', 'flagged', 'passRate', 'costSaved',
            'recent', 'avgConfidence',
            'trendLabels', 'trendPass', 'trendFail', 'trendFlagged', 'lastInspection',
            'defectBreakdown', 'riskBreakdown', 'mode'
        ));
    }
}
