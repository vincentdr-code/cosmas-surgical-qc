<?php

namespace App\Http\Controllers;

use App\Models\Inspection;

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
        $avgConfidence = $avgConfidence ? round($avgConfidence * ($avgConfidence <= 1 ? 100 : 1), 1) : null;

        return view('dashboard', compact(
            'total', 'passed', 'failed', 'flagged', 'passRate', 'costSaved', 'recent', 'avgConfidence'
        ));
    }
}
