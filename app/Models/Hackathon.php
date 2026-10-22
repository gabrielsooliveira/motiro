<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hackathon extends Model
{
    use HasUuids;

    protected $table = 'hackathons';

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
        'rules_file',
        'rules',
        'category',
        'tags',
        'location',
        'modality',
        'external_website',
        'contact_email',
        'contact_phone',
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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(HackathonStage::class, 'hackathon_id')->orderBy('order');
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(HackathonChallenge::class, 'hackathon_id')->orderBy('order');
    }

    public function members(): HasMany
    {
        return $this->hasMany(HackathonMember::class, 'hackathon_id');
    }

    public function staffUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hackathon_members', 'hackathon_id', 'user_id')
            ->withPivot('id', 'role', 'permissions')
            ->withTimestamps();
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(HackathonRegistration::class, 'hackathon_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(HackathonGroup::class, 'hackathon_id');
    }

    public function teams(): HasMany
    {
        return $this->groups();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'hackathon_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(EvaluationCriterion::class, 'hackathon_id')->orderBy('order');
    }

    public function evaluationCriteria(): HasMany
    {
        return $this->criteria();
    }

    public function judgeAssignments(): HasMany
    {
        return $this->hasMany(JudgeAssignment::class, 'hackathon_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'hackathon_id');
    }

    public function conflictsOfInterest(): HasMany
    {
        return $this->hasMany(ConflictOfInterest::class, 'hackathon_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'hackathon_id');
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
}
