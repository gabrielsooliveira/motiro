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
        'registration_type',
        'team_formation_mode',
        'team_leader_required',
        'allow_team_creation',
        'allow_team_invites',
        'allow_team_requests',
        'allow_org_team_management',
        'team_formation_deadline',
        'team_management_deadline',
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
            'team_formation_deadline' => 'datetime',
            'team_management_deadline' => 'datetime',
            'requires_team' => 'boolean',
            'team_leader_required' => 'boolean',
            'allow_team_creation' => 'boolean',
            'allow_team_invites' => 'boolean',
            'allow_team_requests' => 'boolean',
            'allow_org_team_management' => 'boolean',
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

    public function groups()
    {
        return $this->hasMany(HackathonGroup::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function allowsIndividualRegistration(): bool
    {
        return $this->registration_type !== 'team_only';
    }

    public function allowsTeamRegistration(): bool
    {
        return $this->registration_type !== 'individual';
    }

    public function requiresTeam(): bool
    {
        if ($this->registration_type) {
            return $this->registration_type === 'team_only';
        }

        return (bool) $this->requires_team;
    }

    public function allowsParticipantTeamCreation(): bool
    {
        return $this->allowsTeamRegistration()
            && in_array($this->team_formation_mode ?? 'by_participants', ['by_participants', 'both'])
            && (bool) $this->allow_team_creation;
    }

    public function allowsOrganizationTeamCreation(): bool
    {
        return $this->allowsTeamRegistration()
            && in_array($this->team_formation_mode ?? 'by_participants', ['by_organization', 'both'])
            && (bool) $this->allow_org_team_management;
    }

    public function requiresTeamLeader(): bool
    {
        return $this->team_leader_required ?? true;
    }

    public function isTeamFormationOpen(): bool
    {
        if (!$this->allowsTeamRegistration()) {
            return false;
        }

        if ($this->team_formation_deadline && now()->gt($this->team_formation_deadline)) {
            return false;
        }

        if ($this->registration_end_date && now()->gt($this->registration_end_date)) {
            return false;
        }

        return in_array($this->status, ['published', 'registration']);
    }

    public function isTeamManagementOpen(): bool
    {
        if ($this->team_management_deadline && now()->gt($this->team_management_deadline)) {
            return false;
        }

        return $this->isTeamFormationOpen();
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
