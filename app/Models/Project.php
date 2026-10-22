<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'hackathon_id',
        'hackathon_group_id',
        'team_id',
        'user_id',
        'title',
        'slug',
        'description',
        'short_description',
        'problem_statement',
        'solution_description',
        'target_audience',
        'technologies',
        'category',
        'pitch',
        'pitch_deck_pdf',
        'video_url',
        'images',
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

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class, 'hackathon_id');
    }

    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class, 'guild_id');
    }

    public function team(): BelongsTo
    {
        return $this->guild();
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(HackathonGroup::class, 'hackathon_group_id');
    }

    public function hackathonGroup(): BelongsTo
    {
        return $this->group();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isIndividual(): bool
    {
        return empty($this->team_id) && empty($this->hackathon_group_id) && !empty($this->user_id);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ProjectSubmission::class, 'project_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'project_id');
    }

    public function judgeAssignments(): HasMany
    {
        return $this->hasMany(JudgeAssignment::class, 'project_id');
    }
}
