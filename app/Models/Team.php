<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Representa uma Equipe Permanente (Guilda).
 * Existe independentemente de qualquer hackathon e possui ciclo de vida próprio.
 */
class Team extends Model
{
    use HasUuids;

    protected $fillable = [
        'hackathon_id',
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

    /**
     * Criador ou responsável original da guilda.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Hackathon de origem (mantido para compatibilidade, equipes permanentes possuem hackathon_id null).
     */
    public function hackathon()
    {
        return $this->belongsTo(Hackathon::class);
    }

    /**
     * Líder atual da guilda permanente.
     */
    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * Helper para verificar se um usuário é o criador da guilda.
     */
    public function isCreator(?User $user): bool
    {
        if (!$user) return false;
        if ($this->creator_id) {
            return $this->creator_id === $user->id;
        }
        return $this->leader_id === $user->id;
    }

    /**
     * Helper para verificar se um usuário é o líder ativo da guilda.
     */
    public function isLeader(?User $user): bool
    {
        if (!$user) return false;
        return $this->leader_id === $user->id;
    }

    /**
     * Helper para verificar se um usuário possui papel administrativo (criador ou líder).
     */
    public function canManage(?User $user): bool
    {
        return $this->isCreator($user) || $this->isLeader($user);
    }

    /**
     * Helper para verificar se um usuário é membro da guilda.
     */
    public function isMember(?User $user): bool
    {
        if (!$user) return false;
        if ($this->relationLoaded('members')) {
            return $this->members->contains('id', $user->id);
        }
        return $this->members()->where('users.id', $user->id)->exists();
    }

    /**
     * URL da imagem ou logo da guilda.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->logo;
    }

    /**
     * Membros da guilda permanente.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Convites enviados ou solicitações recebidas para a guilda.
     */
    public function invitations()
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Grupos de participação em hackathons derivados desta guilda.
     */
    public function hackathonGroups()
    {
        return $this->hasMany(HackathonGroup::class, 'origin_team_id');
    }

    public function registrations()
    {
        return $this->hasMany(HackathonRegistration::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
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
