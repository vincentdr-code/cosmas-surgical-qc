<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DemoController extends Controller
{
    // Preset demo images — publicly accessible, legally usable
    private array $demos = [
        'corrosion' => [
            'url'   => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8e/Rust_on_iron.jpg/640px-Rust_on_iron.jpg',
            'label' => 'Surface Corrosion',
        ],
        'scratch' => [
            'url'   => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b4/NEU_scratches_sample.jpg/640px-NEU_scratches_sample.jpg',
            'label' => 'Linear Scratch Defect',
        ],
        'clean' => [
            'url'   => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c5/Forceps_img_0723.jpg/640px-Forceps_img_0723.jpg',
            'label' => 'Clean Surgical Forceps',
        ],
    ];

    public function run(Request $request)
    {
        $type = $request->query('type', 'corrosion');

        if (!array_key_exists($type, $this->demos)) {
            return redirect()->route('inspections.upload')->with('error', 'Unknown demo type.');
        }

        $demo = $this->demos[$type];

        // Download the demo image
        try {
            $response = Http::timeout(15)->get($demo['url']);
            if (!$response->successful()) {
                return redirect()->route('inspections.upload')->with('error', 'Could not fetch demo image.');
            }
        } catch (\Exception $e) {
            Log::error('Demo image fetch failed: ' . $e->getMessage());
            return redirect()->route('inspections.upload')->with('error', 'Demo image unavailable.');
        }

        // Save to temp storage
        $ext      = 'jpg';
        $filename = 'demo_' . $type . '_' . time() . '.' . $ext;
        $relPath  = 'inspections/' . $filename;
        Storage::disk('public')->put($relPath, $response->body());
        $fullPath = storage_path('app/public/' . $relPath);

        // Reuse InspectionController pipeline logic via instantiation
        $ic = new InspectionController();

        // Use reflection to call private methods
        $rc         = new \ReflectionClass($ic);
        $yoloMethod = $rc->getMethod('runYoloDetection');
        $yoloMethod->setAccessible(true);
        $claudeMethod = $rc->getMethod('runClaudeAnalysis');
        $claudeMethod->setAccessible(true);

        $yoloResult   = $yoloMethod->invoke($ic, $fullPath);
        $claudeResult = $claudeMethod->invoke($ic, $fullPath, $yoloResult);

        $inspection = Inspection::create([
            'user_id'            => Auth::id(),
            'image_path'         => $relPath,
            'defect_type'        => $claudeResult['defect_type']        ?? 'Unknown',
            'confidence'         => $claudeResult['confidence']          ?? 0,
            'pass_fail'          => $claudeResult['pass_fail']           ?? 'FAIL',
            'claude_reasoning'   => $claudeResult['reasoning']           ?? '',
            'regulatory_note'    => $claudeResult['regulatory_note']     ?? null,
            'recommended_action' => $claudeResult['recommended_action']  ?? null,
            'yolo_detections'    => json_encode($yoloResult['detections'] ?? []),
            'yolo_count'         => $yoloResult['count']                 ?? 0,
            'yolo_model'         => $yoloResult['model_used']            ?? 'unavailable',
            'inference_ms'       => $yoloResult['inference_ms']          ?? null,
        ]);

        return redirect()->route('inspections.results', $inspection);
    }
}
