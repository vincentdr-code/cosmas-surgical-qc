<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inspection extends Model
{
    protected $fillable = [
        'user_id',
        'image_path',
        'defect_type',
        'confidence',
        'pass_fail',
        'claude_reasoning',
        'bounding_box',
    
        'composite_risk_score',
        'risk_level',
        'agent_steps',
        'cost_matrix',
        'instrument_class',
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
