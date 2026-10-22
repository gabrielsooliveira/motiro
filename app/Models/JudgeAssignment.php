<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JudgeAssignment extends Model
{
    use HasUuids;

    protected $table = 'judge_assignments';

    protected $fillable = [
        'hackathon_id',
        'user_id',
        'project_id',
        'status',
    ];

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class, 'hackathon_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
