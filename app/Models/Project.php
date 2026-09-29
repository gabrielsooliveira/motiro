<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasUuids;

    protected $fillable = [
        'hackathon_id',
        'team_id',
        'title',
        'slug',
        'short_description',
        'problem_statement',
        'solution_description',
        'target_audience',
        'technologies',
        'category',
        'pitch',
        'video_url',
        'images',
        'pitch_deck_pdf',
        'github_repository_url',
        'demo_url',
        'website_url',
        'documentation_url',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'images' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function hackathon()
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function submissions()
    {
        return $this->hasMany(ProjectSubmission::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function judgeAssignments()
    {
        return $this->hasMany(JudgeAssignment::class);
    }
}
