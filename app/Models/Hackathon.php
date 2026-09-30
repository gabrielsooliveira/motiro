<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Hackathon extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'title',
        'slug',
        'description',
        'start_date',
        'end_date',
        'status',
        'banner_url',
        'logo_url',
        'livestream_url',
        'category',
        'tags',
        'location',
        'modality',
        'max_participants',
        'max_teams',
        'min_team_members',
        'max_team_members',
        'requires_team',
        'auto_approve_registrations',
        'registration_start_date',
        'registration_end_date',
        'submission_start_date',
        'submission_end_date',
        'submissions_reopened',
        'evaluation_distribution',
        'evaluations_per_project_target',
        'scoring_algorithm',
        'results_published',
        'rules',
        'external_website',
        'contact_email',
        'contact_phone',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'registration_start_date' => 'datetime',
            'registration_end_date' => 'datetime',
            'submission_start_date' => 'datetime',
            'submission_end_date' => 'datetime',
            'requires_team' => 'boolean',
            'auto_approve_registrations' => 'boolean',
            'submissions_reopened' => 'boolean',
            'results_published' => 'boolean',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function stages()
    {
        return $this->hasMany(HackathonStage::class)->orderBy('order');
    }

    public function registrations()
    {
        return $this->hasMany(HackathonRegistration::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function evaluationCriteria()
    {
        return $this->hasMany(EvaluationCriterion::class)->orderBy('order');
    }

    public function judgeAssignments()
    {
        return $this->hasMany(JudgeAssignment::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function conflictsOfInterest()
    {
        return $this->hasMany(ConflictOfInterest::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function members()
    {
        return $this->hasMany(HackathonMember::class);
    }

    public function staffUsers()
    {
        return $this->belongsToMany(User::class, 'hackathon_members', 'hackathon_id', 'user_id')
            ->withPivot('role', 'permissions')
            ->withTimestamps();
    }

    public function challenges()
    {
        return $this->hasMany(HackathonChallenge::class)->orderBy('order');
    }
}
