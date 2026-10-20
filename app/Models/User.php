<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'name',
        'username',
        'bio',
        'phone',
        'email',
        'password',
        'avatar',
        'city',
        'state',
        'education_institution',
        'education_course',
        'professional_experience',
        'skills',
        'technologies',
        'github_url',
        'linkedin_url',
        'portfolio_url',
        'website_url',
        'is_profile_public',
        'lgpd_consent_at',
        'terms_accepted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->username)) {
                $user->username = static::generateUsername($user->name);
            }
        });
    }

    private static function generateUsername($name)
    {
        $base = Str::slug($name);
        $username = $base;
        $count = 1;

        while (static::where('username', $username)->exists()) {
            $username = $base . $count;
            $count++;
        }

        return $username;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'skills' => 'array',
            'technologies' => 'array',
            'is_profile_public' => 'boolean',
            'lgpd_consent_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
        ];
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_user', 'user_id', 'organization_id')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function hackathonStaffMemberships()
    {
        return $this->hasMany(HackathonMember::class);
    }

    public function staffHackathons()
    {
        return $this->belongsToMany(Hackathon::class, 'hackathon_members', 'user_id', 'hackathon_id')
            ->withPivot('role', 'permissions')
            ->withTimestamps();
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members', 'user_id', 'team_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function permanentTeams()
    {
        return $this->teams();
    }

    public function ledTeams()
    {
        return $this->hasMany(Team::class, 'leader_id');
    }

    public function hackathonGroups()
    {
        return $this->belongsToMany(HackathonGroup::class, 'hackathon_group_members', 'user_id', 'hackathon_group_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function ledHackathonGroups()
    {
        return $this->hasMany(HackathonGroup::class, 'leader_id');
    }

    public function teamInvitations()
    {
        return $this->hasMany(TeamInvitation::class, 'invitee_id');
    }

    public function registrations()
    {
        return $this->hasMany(HackathonRegistration::class, 'user_id');
    }

    public function judgeAssignments()
    {
        return $this->hasMany(JudgeAssignment::class, 'user_id');
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class, 'user_id');
    }

    public function conflictsOfInterest()
    {
        return $this->hasMany(ConflictOfInterest::class, 'user_id');
    }

    public function inAppNotifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'model_has_roles', 'user_id', 'role_id')
            ->withPivot(['organization_id', 'hackathon_id'])
            ->withTimestamps();
    }

    /**
     * Check if user has permission in a given context (organization or hackathon)
     */
    public function hasPermission(string $permissionName, ?string $organizationId = null, ?string $hackathonId = null): bool
    {
        $query = $this->roles()->whereHas('permissions', function ($q) use ($permissionName) {
            $q->where('name', $permissionName);
        });

        if ($organizationId) {
            $query->where(function ($q) use ($organizationId) {
                $q->where('model_has_roles.organization_id', $organizationId)
                  ->orWhereNull('model_has_roles.organization_id');
            });
        }

        if ($hackathonId) {
            $query->where(function ($q) use ($hackathonId) {
                $q->where('model_has_roles.hackathon_id', $hackathonId)
                  ->orWhereNull('model_has_roles.hackathon_id');
            });
        }

        return $query->exists();
    }

    public function isOrganizationAdmin(?string $organizationId = null): bool
    {
        $query = $this->organizations()->wherePivot('is_active', true);
        if ($organizationId) {
            $query->where('organizations.id', $organizationId);
        }
        return $query->wherePivot('role', 'admin')->exists();
    }

    public function isOrganizationOwner(Organization|string $organization): bool
    {
        if ($organization instanceof Organization) {
            return $organization->owner_id === $this->id;
        }

        return Organization::where('id', $organization)->where('owner_id', $this->id)->exists();
    }

    public function isOrganizationManager(string $organizationId): bool
    {
        return $this->organizations()
            ->where('organizations.id', $organizationId)
            ->wherePivot('is_active', true)
            ->wherePivot('role', 'manager')
            ->exists();
    }

    public function isOrganizationCollaborator(string $organizationId): bool
    {
        return $this->organizations()
            ->where('organizations.id', $organizationId)
            ->wherePivot('is_active', true)
            ->wherePivot('role', 'collaborator')
            ->exists();
    }

    public function getOrganizationRole(string $organizationId): ?string
    {
        $org = $this->organizations()->where('organizations.id', $organizationId)->first();
        return $org?->pivot?->role;
    }

    public function canManageHackathon(Hackathon $hackathon): bool
    {
        // 1. Organization owner always has access
        if ($hackathon->organization && $hackathon->organization->owner_id === $this->id) {
            return true;
        }

        // 2. Organization admin has access
        if ($this->isOrganizationAdmin($hackathon->organization_id)) {
            return true;
        }

        // 3. User is explicitly in hackathon_members as admin or manager
        $member = HackathonMember::where('hackathon_id', $hackathon->id)
            ->where('user_id', $this->id)
            ->first();

        if ($member && in_array($member->role, ['admin', 'manager'])) {
            return true;
        }

        // 4. RBAC check
        return $this->hasPermission('hackathon.configure', $hackathon->organization_id, $hackathon->id);
    }
}
