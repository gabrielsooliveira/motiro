<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Representa um Convite ou Solicitação de Guilda Permanente.
 */
class GuildInvitation extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'guild_invitations';

    protected $fillable = [
        'guild_id',
        'team_id',
        'inviter_id',
        'invitee_id',
        'email',
        'type', // 'invite' ou 'request'
        'status', // 'pending', 'accepted', 'declined', 'cancelled'
        'token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function setTeamIdAttribute($value)
    {
        $this->attributes['guild_id'] = $value;
        $this->attributes['team_id'] = $value;
    }

    public function getTeamIdAttribute()
    {
        return $this->attributes['guild_id'] ?? $this->attributes['team_id'] ?? null;
    }

    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class, 'guild_id');
    }

    public function team(): BelongsTo
    {
        return $this->guild();
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    public function isInvite(): bool
    {
        return $this->type === 'invite';
    }

    public function isRequest(): bool
    {
        return $this->type === 'request';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }
}
