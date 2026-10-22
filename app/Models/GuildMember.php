<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuildMember extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'guild_members';

    protected $fillable = [
        'guild_id',
        'team_id',
        'user_id',
        'role',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
