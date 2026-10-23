<?php

namespace App\Actions\Participant;

use App\Models\Role;
use App\Models\User;
use App\Models\UserParticipant;
use Illuminate\Support\Facades\DB;

class CreateParticipantProfileAction
{
    /**
     * Provisiona o perfil de participante para um usuário existente.
     */
    public function execute(User $user, array $data = []): UserParticipant
    {
        return DB::transaction(function () use ($user, $data) {
            $participant = UserParticipant::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'education_institution' => $data['education_institution'] ?? null,
                    'education_course' => $data['education_course'] ?? null,
                    'professional_experience' => $data['professional_experience'] ?? null,
                    'skills' => $data['skills'] ?? null,
                    'technologies' => $data['technologies'] ?? null,
                    'github_url' => $data['github_url'] ?? null,
                    'linkedin_url' => $data['linkedin_url'] ?? null,
                    'portfolio_url' => $data['portfolio_url'] ?? null,
                    'website_url' => $data['website_url'] ?? null,
                    'is_profile_public' => $data['is_profile_public'] ?? false,
                ]
            );

            $competitorRole = Role::where('name', 'competitor')->first();
            if ($competitorRole) {
                $user->roles()->syncWithoutDetaching([$competitorRole->id]);
            }

            return $participant;
        });
    }
}
