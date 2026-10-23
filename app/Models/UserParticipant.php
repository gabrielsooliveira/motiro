<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserParticipant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'user_participant';

    protected $fillable = [
        'user_id',
        'education_institution',
        'education_course',
        'professional_experience',
        'skills',
        'technologies',
        'github_url',
        'linkedin_url',
        'portfolio_url',
        'website_url',
        'is_profile_public',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'technologies' => 'array',
            'is_profile_public' => 'boolean',
        ];
    }

    /**
     * Identidade única da conta à qual este perfil de participante pertence.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Inscrições em hackathons associadas a este participante.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(HackathonRegistration::class, 'user_id', 'user_id');
    }
}
