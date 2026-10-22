<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasUuids;

    protected $table = 'evaluations';

    protected $fillable = [
        'hackathon_id',
        'project_id',
        'user_id',
        'final_score',
        'feedback_positive',
        'feedback_improvement',
        'private_admin_notes',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class, 'hackathon_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function judge(): BelongsTo
    {
        return $this->user();
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class, 'evaluation_id');
    }
}
