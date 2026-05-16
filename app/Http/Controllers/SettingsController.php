<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('settings.threshold', [
            'threshold' => auth()->user()->confidence_threshold ?? 70,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'confidence_threshold' => ['required', 'integer', 'min:50', 'max:99'],
        ]);

        auth()->user()->update([
            'confidence_threshold' => $request->confidence_threshold,
        ]);

        return redirect()->route('settings.threshold')
            ->with('success', 'Threshold updated. New inspections will use ' . $request->confidence_threshold . '% as the PASS floor.');
    }
}
