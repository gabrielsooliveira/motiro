<?php

namespace App\Actions\User;

use App\Models\Role;
use App\Models\User;
use App\Models\UserParticipant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUserAccountAction
{
    /**
     * Cria uma nova identidade de usuário no Motirõ.
     * Opcionalmente provisiona o perfil de participante associado.
     *
     * @param array $data ['name' => ..., 'email' => ..., 'password' => ...]
     * @param bool $createParticipantProfile
     * @return User
     */
    public function execute(array $data, bool $createParticipantProfile = false): User
    {
        return DB::transaction(function () use ($data, $createParticipantProfile) {
            $user = User::create([
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'password' => Hash::needsRehash($data['password']) ? Hash::make($data['password']) : $data['password'],
                'terms_accepted_at' => $data['terms_accepted_at'] ?? now(),
                'lgpd_consent_at' => $data['lgpd_consent_at'] ?? now(),
            ]);

            if ($createParticipantProfile) {
                UserParticipant::firstOrCreate(
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
            }

            return $user;
        });
    }
}
