<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasUuids;

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
            'final_score' => 'float',
            'submitted_at' => 'datetime',
        ];
    }

    public function hackathon()
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function judge()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scores()
    {
        return $this->hasMany(EvaluationScore::class);
    }
}
