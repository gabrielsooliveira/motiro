<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HackathonGroup extends Model
{
    use HasUuids;

    protected $table = 'hackathon_groups';

    protected $fillable = [
        'hackathon_id',
        'name',
        'slug',
        'description',
        'technologies',
        'leader_id',
        'origin_guild_id',
        'origin_team_id',
        'status',
    ];

    public function setOriginTeamIdAttribute($value)
    {
        $this->attributes['origin_guild_id'] = $value;
        $this->attributes['origin_team_id'] = $value;
    }

    public function getOriginTeamIdAttribute()
    {
        return $this->attributes['origin_guild_id'] ?? $this->attributes['origin_team_id'] ?? null;
    }

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
        ];
    }

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class, 'hackathon_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function originGuild(): BelongsTo
    {
        return $this->belongsTo(Guild::class, 'origin_guild_id');
    }

    public function originTeam(): BelongsTo
    {
        return $this->originGuild();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hackathon_group_members', 'hackathon_group_id', 'user_id')
            ->withPivot('id', 'role', 'joined_at')
            ->withTimestamps();
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(HackathonRegistration::class, 'hackathon_group_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'hackathon_group_id');
    }

    public function conflictsOfInterest(): HasMany
    {
        return $this->hasMany(ConflictOfInterest::class, 'hackathon_group_id');
    }
}
