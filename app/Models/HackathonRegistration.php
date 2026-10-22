<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HackathonRegistration extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'hackathon_registrations';

    protected $fillable = [
        'hackathon_id',
        'user_id',
        'hackathon_group_id',
        'guild_id',
        'team_id',
        'participation_type', // 'individual' ou 'team'
        'is_eligible',
        'status',
        'rejection_reason',
        'notes',
        'custom_answers',
        'submitted_documents',
        'approved_at',
        'approved_by',
    ];

    public function setTeamIdAttribute($value)
    {
        $this->attributes['guild_id'] = $value;
        $this->attributes['team_id'] = $value;
    }

    public function getTeamIdAttribute()
    {
        return $this->attributes['guild_id'] ?? $this->attributes['team_id'] ?? null;
    }

    protected function casts(): array
    {
        return [
            'is_eligible' => 'boolean',
            'custom_answers' => 'array',
            'submitted_documents' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function isIndividual(): bool
    {
        return $this->participation_type === 'individual' || empty($this->hackathon_group_id);
    }

    public function hasGroup(): bool
    {
        return !empty($this->hackathon_group_id);
    }

    public function hasTeam(): bool
    {
        return $this->hasGroup();
    }

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class, 'hackathon_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(HackathonGroup::class, 'hackathon_group_id');
    }

    public function hackathonGroup(): BelongsTo
    {
        return $this->group();
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
