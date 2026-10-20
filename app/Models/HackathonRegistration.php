<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HackathonRegistration extends Model
{
    use HasUuids;

    protected $fillable = [
        'hackathon_id',
        'hackathon_group_id',
        'team_id',
        'user_id',
        'participation_type',
        'status',
        'is_eligible',
        'rejection_reason',
        'notes',
        'custom_answers',
        'submitted_documents',
        'approved_at',
        'approved_by',
    ];

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
        return $this->participation_type === 'individual' || (empty($this->hackathon_group_id) && empty($this->team_id));
    }

    public function hasTeam(): bool
    {
        return !empty($this->hackathon_group_id) || !empty($this->team_id);
    }

    public function hasGroup(): bool
    {
        return !empty($this->hackathon_group_id);
    }

    public function hackathon()
    {
        return $this->belongsTo(Hackathon::class);
    }

    /**
     * Grupo de participação específico do hackathon
     */
    public function group()
    {
        return $this->belongsTo(HackathonGroup::class, 'hackathon_group_id');
    }

    public function hackathonGroup()
    {
        return $this->belongsTo(HackathonGroup::class, 'hackathon_group_id');
    }

    /**
     * Equipe permanente / legada
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
