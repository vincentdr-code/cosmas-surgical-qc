<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PipelineController extends Controller
{
    public function pipeline()
    {
        return view('pipeline');
    }

    public function modelStats()
    {
        // Live YOLO service health check
        $yoloStatus = ['online' => false, 'model' => 'unknown', 'inference_ms' => null];
        try {
            $health = Http::timeout(3)->get('http://127.0.0.1:8001/health');
            if ($health->successful()) {
                $yoloStatus = array_merge($yoloStatus, $health->json(), ['online' => true]);
            }
        } catch (\Exception $e) {
            // Service offline — Claude-only mode
        }

        // Training stats — YOLOv8s cosmas_defect_v2
        $trainingStats = [
            'model'           => 'YOLOv8s',
            'params'          => '11.1M',
            'gflops'          => '28.7',
            'total_images'    => 10764,
            'train_images'    => 9506,
            'val_images'      => 1258,
            'epochs_trained'  => 'In progress (100 max, patience=20 early stopping)',
            'best_map50'      => 0.709,
            'best_map50_95'   => 0.540,
            'optimizer'       => 'AdamW',
            'lr0'             => '0.001',
            'weight_decay'    => '0.0005',
            'imgsz'           => 640,
            'batch'           => 16,
            'hardware'        => 'Google Colab T4 GPU',
            'datasets' => [
                [
                    'name'    => 'NEU Surface Defect (Roboflow)',
                    'images'  => 1799,
                    'license' => 'CC BY 4.0',
                    'classes' => 'crazing, inclusion, patches, pitted_surface, rolled-in_scale, scratches',
                ],
                [
                    'name'    => 'Rust Corrosion Detection (Roboflow)',
                    'images'  => 8354,
                    'license' => 'CC BY 4.0',
                    'classes' => 'rust, corrosion, moderate corrosion, severe corrosion',
                ],
                [
                    'name'    => 'NEU Steel Surface Defect (Kaggle)',
                    'images'  => 1800,
                    'license' => 'Unknown',
                    'classes' => 'crazing, inclusion, patches, pitted_surface, rolled-in_scale, scratches',
                ],
                [
                    'name'    => 'Synthetic Industrial Metal (Kaggle)',
                    'images'  => 15000,
                    'license' => 'CC BY 4.0',
                    'classes' => 'Normal, Scratch, Crack, Rust, Hole',
                ],
            ],
            'classes' => [
                ['id' => 0, 'name' => 'cracks',        'train_ann' => 1978,  'val_ann' => 320],
                ['id' => 1, 'name' => 'corrosion',     'train_ann' => 10620, 'val_ann' => 877],
                ['id' => 2, 'name' => 'misalignment',  'train_ann' => 0,     'val_ann' => 0],
                ['id' => 3, 'name' => 'scratches',     'train_ann' => 4356,  'val_ann' => 675],
                ['id' => 4, 'name' => 'porosity',      'train_ann' => 3355,  'val_ann' => 451],
                ['id' => 5, 'name' => 'none',          'train_ann' => 800,   'val_ann' => 120],
            ],
        ];

        return view('model-stats', compact('yoloStatus', 'trainingStats'));
    }
}
