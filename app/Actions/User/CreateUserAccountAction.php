<?php

namespace App\Actions\User;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUserAccountAction
{
    /**
     * Cria uma nova conta de usuário pessoal com papel base de competidor.
     *
     * @param array $data ['name' => ..., 'email' => ..., 'password' => ...]
     * @return User
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'password' => Hash::needsRehash($data['password']) ? Hash::make($data['password']) : $data['password'],
                'terms_accepted_at' => $data['terms_accepted_at'] ?? now(),
                'lgpd_consent_at' => $data['lgpd_consent_at'] ?? now(),
            ]);

            // Todo usuário no Motirõ recebe o papel de competidor para poder participar de hackathons com a mesma conta
            $competitorRole = Role::where('name', 'competitor')->first();
            if ($competitorRole) {
                $user->roles()->syncWithoutDetaching([$competitorRole->id]);
            }

            return $user;
        });
    }
}
