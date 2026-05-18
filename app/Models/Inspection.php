<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inspection extends Model
{
    protected $fillable = [
        'user_id',
        'instrument_id',
        'operator_id',
        'image_path',
        'defect_type',
        'instrument_class',
        'confidence',
        'pass_fail',
        'claude_reasoning',
        'regulatory_note',
        'recommended_action',
        'bounding_box',
        'composite_risk_score',
        'risk_level',
        'agent_steps',
        'cost_matrix',
        'yolo_detections',
        'yolo_count',
        'yolo_model',
        'inference_ms',
    ];

    protected $casts = [
        'bounding_box' => 'array',
        'confidence' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
