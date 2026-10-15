<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TeamInvitation extends Model
{
    use HasUuids;

    protected $fillable = [
        'team_id',
        'inviter_id',
        'invitee_id',
        'email',
        'status',
        'type',
        'token',
    ];

    public function isInvite(): bool
    {
        return $this->type === 'invite';
    }

    public function isRequest(): bool
    {
        return $this->type === 'request';
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitee()
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }
}
