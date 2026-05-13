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
