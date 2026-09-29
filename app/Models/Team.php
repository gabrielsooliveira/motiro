<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasUuids;

    protected $fillable = [
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

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'links' => 'array',
            'is_looking_for_members' => 'boolean',
        ];
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function invitations()
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function registrations()
    {
        return $this->hasMany(HackathonRegistration::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
