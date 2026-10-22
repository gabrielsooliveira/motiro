<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationScore extends Model
{
    use HasUuids;

    protected $table = 'evaluation_scores';

    protected $fillable = [
        'evaluation_id',
        'criteria_id',
        'score',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(EvaluationCriterion::class, 'criteria_id');
    }
}
