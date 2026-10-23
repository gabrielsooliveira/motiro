<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Representa a Guild Permanente na plataforma Motirõ.
 * Existe independentemente de qualquer hackathon e possui ciclo de vida próprio.
 */
class Guild extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'guilds';

    protected $fillable = [
        'creator_id',
        'name',
        'slug',
        'description',
        'logo',
        'technologies',
        'area_of_focus',
        'leader_id',
        'links',
        'is_looking_for_members',
    ];

    protected $appends = [
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'links' => 'array',
            'is_looking_for_members' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function isCreator(?User $user): bool
    {
        if (!$user) return false;
        if ($this->creator_id) {
            return $this->creator_id === $user->id;
        }
        return $this->leader_id === $user->id;
    }

    public function isLeader(?User $user): bool
    {
        if (!$user) return false;
        return $this->leader_id === $user->id;
    }

    public function canManage(?User $user): bool
    {
        return $this->isCreator($user) || $this->isLeader($user);
    }

    public function isMember(?User $user): bool
    {
        if (!$user) return false;
        if ($this->relationLoaded('members')) {
            return $this->members->contains('id', $user->id);
        }
        return $this->members()->where('users.id', $user->id)->exists();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->logo;
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'guild_members', 'guild_id', 'user_id')
            ->withPivot('id', 'role', 'joined_at')
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(GuildMember::class, 'guild_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(GuildInvitation::class, 'guild_id');
    }

    public function hackathonGroups(): HasMany
    {
        return $this->hasMany(HackathonGroup::class, 'origin_guild_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(HackathonRegistration::class, 'guild_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'guild_id');
    }

    public function hasMinimumMembers(?int $min = null): bool
    {
        $min = $min ?? ($this->hackathon?->min_team_members ?? 1);
        return $this->members()->count() >= $min;
    }

    public function hasAvailableSlots(?int $max = null): bool
    {
        $max = $max ?? ($this->hackathon?->max_team_members ?? 5);
        return $this->members()->count() < $max;
    }
}
