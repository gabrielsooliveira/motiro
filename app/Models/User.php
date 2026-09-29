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
        return $this->belongsToMany(Organization::class, 'organization_user', 'user_id', 'organization_id');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members', 'user_id', 'team_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function ledTeams()
    {
        return $this->hasMany(Team::class, 'leader_id');
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
}
