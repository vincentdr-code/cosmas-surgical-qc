<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function threshold()
    {
        $threshold = (int) DB::table('settings')
            ->where('key', 'confidence_threshold')
            ->value('value') ?? 75;

        return view('settings.threshold', compact('threshold'));
    }

    public function updateThreshold(Request $request)
    {
        $request->validate([
            'confidence_threshold' => 'required|integer|min:50|max:99',
        ]);

        DB::table('settings')->updateOrInsert(
            ['key' => 'confidence_threshold'],
            ['value' => $request->confidence_threshold, 'updated_at' => now()]
        );

        return redirect()->route('settings.threshold')
            ->with('success', 'Threshold updated to ' . $request->confidence_threshold . '%. Change recorded in audit log per 21 CFR Part 11.');
    }
}
