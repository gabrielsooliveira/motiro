<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HackathonGroup extends Model
{
    use HasUuids;

    protected $fillable = [
        'hackathon_id',
        'name',
        'slug',
        'description',
        'area_of_focus',
        'technologies',
        'leader_id',
        'origin_team_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
        ];
    }

    public function hackathon()
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function originTeam()
    {
        return $this->belongsTo(Team::class, 'origin_team_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'hackathon_group_members', 'hackathon_group_id', 'user_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function registrations()
    {
        return $this->hasMany(HackathonRegistration::class, 'hackathon_group_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'hackathon_group_id');
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

    public function isLeader(User|string $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        return $this->leader_id === $userId;
    }
}
