<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationCriterion extends Model
{
    use HasUuids;

    protected $table = 'evaluation_criteria';

    protected $fillable = [
        'hackathon_id',
        'name',
        'description',
        'weight',
        'min_score',
        'max_score',
        'is_required',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'min_score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'is_required' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class, 'hackathon_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class, 'criteria_id');
    }
}
