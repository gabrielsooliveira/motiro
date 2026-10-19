<?php

namespace App\Services;

use App\Models\Hackathon;
use App\Models\HackathonRegistration;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationService
{
    /**
     * Inscrição individual de participante no Hackathon
     */
    public function registerIndividual(Hackathon $hackathon, User $user, array $answers = [], array $documents = []): HackathonRegistration
    {
        // 1. Validação de período e status do hackathon
        $this->validateRegistrationWindow($hackathon);

        // 2. Não permite inscrição individual se a modalidade for estritamente em equipe
        // e não permitir pré-cadastro
        $isTeamOnly = $hackathon->requiresTeam();

        // 3. Verifica se o usuário já possui inscrição ativa no hackathon
        $existing = HackathonRegistration::where('hackathon_id', $hackathon->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->status !== 'cancelled') {
            throw new Exception('Você já está inscrito neste Hackathon.');
        }

        $userInRegisteredTeam = DB::table('hackathon_registrations')
            ->join('teams', 'hackathon_registrations.team_id', '=', 'teams.id')
            ->join('team_members', 'teams.id', '=', 'team_members.team_id')
            ->where('hackathon_registrations.hackathon_id', $hackathon->id)
            ->whereIn('hackathon_registrations.status', ['pending', 'under_review', 'approved'])
            ->where('team_members.user_id', $user->id)
            ->exists();

        if ($userInRegisteredTeam) {
            throw new Exception('Você já está inscrito neste Hackathon através de uma equipe.');
        }

        // 4. Limite de participantes individuais
        if ($hackathon->max_participants) {
            $totalActive = HackathonRegistration::where('hackathon_id', $hackathon->id)
                ->whereIn('status', ['approved', 'pending'])
                ->count();

            if ($totalActive >= $hackathon->max_participants) {
                throw new Exception('O limite máximo de participantes para este Hackathon foi atingido.');
            }
        }

        $initialStatus = $hackathon->auto_approve_registrations ? 'approved' : 'pending';
        // Se a modalidade exige equipe, a inscrição individual é recebida, mas não é elegível para competir até integrar equipe válida
        $isEligible = !$isTeamOnly;
        $notes = $isTeamOnly ? 'Inscrição registrada. Aguardando formação ou ingresso em equipe válida.' : null;

        return DB::transaction(function () use ($hackathon, $user, $initialStatus, $isEligible, $notes, $answers, $documents) {
            $registration = HackathonRegistration::updateOrCreate(
                [
                    'hackathon_id' => $hackathon->id,
                    'user_id' => $user->id,
                ],
                [
                    'team_id' => null,
                    'participation_type' => 'individual',
                    'status' => $initialStatus,
                    'is_eligible' => $isEligible,
                    'notes' => $notes,
                    'custom_answers' => $answers,
                    'submitted_documents' => $documents,
                    'approved_at' => $initialStatus === 'approved' ? Carbon::now() : null,
                    'approved_by' => $initialStatus === 'approved' ? $user->id : null,
                ]
            );

            AuditService::log('registration.individual_created', $registration, null, $registration->toArray(), $hackathon->organization_id, $hackathon->id);

            NotificationService::notify(
                $user,
                'registration_created',
                'Inscrição Individual Realizada',
                "Sua inscrição individual no Hackathon '{$hackathon->title}' foi confirmada. Status: {$initialStatus}."
            );

            return $registration;
        });
    }

    /**
     * Inscrição de equipe no Hackathon
     */
    public function registerTeam(Hackathon $hackathon, Team $team, User $user, array $answers = [], array $documents = []): HackathonRegistration
    {
        // 1. Validação de período e status
        $this->validateRegistrationWindow($hackathon);

        // 2. Validação da modalidade
        if (!$hackathon->allowsTeamRegistration()) {
            throw new Exception('Este Hackathon aceita apenas inscrições individuais.');
        }

        // 3. Validação de membros da equipe
        $memberCount = $team->members()->count();
        if ($memberCount < $hackathon->min_team_members) {
            throw new Exception("A equipe possui {$memberCount} integrantes, mas o mínimo exigido é de {$hackathon->min_team_members}.");
        }

        if ($memberCount > $hackathon->max_team_members) {
            throw new Exception("A equipe possui {$memberCount} integrantes, excedendo o máximo permitido de {$hackathon->max_team_members}.");
        }

        // 4. Verifica se a equipe já realizou inscrição
        $existingTeamReg = HackathonRegistration::where('hackathon_id', $hackathon->id)
            ->where('team_id', $team->id)
            ->first();

        if ($existingTeamReg) {
            throw new Exception('Esta equipe já realizou inscrição neste Hackathon.');
        }

        // 5. Verifica se algum membro já está inscrito em outra equipe neste hackathon
        $memberIds = $team->members()->pluck('users.id')->toArray();
        $conflictingMember = DB::table('hackathon_registrations')
            ->join('teams', 'hackathon_registrations.team_id', '=', 'teams.id')
            ->join('team_members', 'teams.id', '=', 'team_members.team_id')
            ->join('users', 'team_members.user_id', '=', 'users.id')
            ->where('hackathon_registrations.hackathon_id', $hackathon->id)
            ->where('hackathon_registrations.team_id', '!=', $team->id)
            ->whereIn('hackathon_registrations.status', ['pending', 'under_review', 'approved'])
            ->whereIn('team_members.user_id', $memberIds)
            ->select('users.name')
            ->first();

        if ($conflictingMember) {
            throw new Exception("O participante {$conflictingMember->name} já está inscrito em outra equipe neste Hackathon.");
        }

        // 6. Limite máximo de equipes
        if ($hackathon->max_teams) {
            $approvedOrPending = HackathonRegistration::where('hackathon_id', $hackathon->id)
                ->whereNotNull('team_id')
                ->whereIn('status', ['approved', 'pending'])
                ->distinct('team_id')
                ->count('team_id');

            if ($approvedOrPending >= $hackathon->max_teams) {
                throw new Exception('O limite máximo de equipes para este Hackathon foi atingido.');
            }
        }

        $initialStatus = $hackathon->auto_approve_registrations ? 'approved' : 'pending';

        return DB::transaction(function () use ($hackathon, $team, $user, $memberIds, $initialStatus, $answers, $documents) {
            // Vincula a equipe diretamente ao hackathon
            $team->update(['hackathon_id' => $hackathon->id]);

            // Atualiza ou cria a inscrição para o líder/usuário
            $leaderRegistration = HackathonRegistration::updateOrCreate(
                [
                    'hackathon_id' => $hackathon->id,
                    'user_id' => $user->id,
                ],
                [
                    'team_id' => $team->id,
                    'participation_type' => 'team',
                    'status' => $initialStatus,
                    'is_eligible' => true,
                    'notes' => null,
                    'custom_answers' => $answers,
                    'submitted_documents' => $documents,
                    'approved_at' => $initialStatus === 'approved' ? Carbon::now() : null,
                    'approved_by' => $initialStatus === 'approved' ? $user->id : null,
                ]
            );

            // Garante inscrições coerentes para todos os demais membros da equipe
            foreach ($memberIds as $memberId) {
                if ($memberId !== $user->id) {
                    HackathonRegistration::updateOrCreate(
                        [
                            'hackathon_id' => $hackathon->id,
                            'user_id' => $memberId,
                        ],
                        [
                            'team_id' => $team->id,
                            'participation_type' => 'team',
                            'status' => $initialStatus,
                            'is_eligible' => true,
                            'notes' => null,
                            'approved_at' => $initialStatus === 'approved' ? Carbon::now() : null,
                        ]
                    );
                }
            }

            AuditService::log('registration.team_created', $leaderRegistration, null, $leaderRegistration->toArray(), $hackathon->organization_id, $hackathon->id);

            NotificationService::notify(
                $user,
                'registration_created',
                'Inscrição Realizada',
                "Sua equipe '{$team->name}' foi inscrita no Hackathon '{$hackathon->title}'. Status: {$initialStatus}."
            );

            return $leaderRegistration;
        });
    }

    /**
     * Vincula um participante inscrito individualmente a uma equipe
     */
    public function assignParticipantToTeam(HackathonRegistration $registration, Team $team, User $actingUser): HackathonRegistration
    {
        $hackathon = $registration->hackathon;

        // 1. Valida se o time pertence ao mesmo hackathon
        if ($team->hackathon_id && $team->hackathon_id !== $hackathon->id) {
            throw new Exception('A equipe selecionada pertence a outro Hackathon.');
        }

        // 2. Valida prazos de formação caso não seja administrador da instituição
        $isOrgAdmin = $actingUser->isOrganizationAdmin($hackathon->organization_id) || $hackathon->organization->owner_id === $actingUser->id;
        if (!$isOrgAdmin && !$hackathon->isTeamFormationOpen()) {
            throw new Exception('O prazo para formação e alteração de equipes deste Hackathon está encerrado.');
        }

        // 3. Valida limite de integrantes
        $currentCount = $team->members()->count();
        if ($currentCount >= $hackathon->max_team_members) {
            throw new Exception("A equipe '{$team->name}' já atingiu o limite máximo de {$hackathon->max_team_members} integrantes.");
        }

        return DB::transaction(function () use ($registration, $team, $hackathon, $actingUser) {
            $user = $registration->user;

            // Se o time ainda não tem hackathon_id registrado, define agora
            if (!$team->hackathon_id) {
                $team->update(['hackathon_id' => $hackathon->id]);
            }

            // Remove o usuário de qualquer outra equipe deste hackathon
            $oldTeams = $user->teams()->where('teams.hackathon_id', $hackathon->id)->get();
            foreach ($oldTeams as $oldTeam) {
                $oldTeam->members()->detach($user->id);
            }

            // Adiciona como membro na nova equipe
            $team->members()->syncWithoutDetaching([
                $user->id => [
                    'id' => (string) Str::uuid(),
                    'role' => 'member',
                    'joined_at' => now(),
                ]
            ]);

            // Atualiza a inscrição do participante
            $registration->update([
                'team_id' => $team->id,
                'participation_type' => 'team',
                'is_eligible' => true,
                'notes' => null,
            ]);

            AuditService::log('team.member_assigned', $team, null, [
                'user_id' => $user->id,
                'assigned_by' => $actingUser->id,
            ], $hackathon->organization_id, $hackathon->id);

            NotificationService::notify(
                $user,
                'team_assigned',
                'Você foi adicionado a uma equipe',
                "Você foi integrado à equipe '{$team->name}' no Hackathon '{$hackathon->title}'."
            );

            return $registration->fresh(['team.members', 'user']);
        });
    }

    /**
     * Transfere participante entre duas equipes do mesmo hackathon
     */
    public function transferParticipant(HackathonRegistration $registration, Team $targetTeam, User $actingUser): HackathonRegistration
    {
        $hackathon = $registration->hackathon;

        // 1. Valida se a equipe destino pertence ao mesmo hackathon
        if ($targetTeam->hackathon_id && $targetTeam->hackathon_id !== $hackathon->id) {
            throw new Exception('A equipe de destino pertence a outro Hackathon.');
        }

        // 2. Valida capacidade da equipe de destino
        if ($targetTeam->members()->count() >= $hackathon->max_team_members) {
            throw new Exception("A equipe de destino atingiu o limite máximo de {$hackathon->max_team_members} integrantes.");
        }

        // 3. Validação de prazo caso não seja admin
        $isOrgAdmin = $actingUser->isOrganizationAdmin($hackathon->organization_id) || $hackathon->organization->owner_id === $actingUser->id;
        if (!$isOrgAdmin && !$hackathon->isTeamManagementOpen()) {
            throw new Exception('O prazo para transferência de membros entre equipes está encerrado.');
        }

        return DB::transaction(function () use ($registration, $targetTeam, $hackathon, $actingUser) {
            $user = $registration->user;
            $sourceTeam = $registration->team;

            if ($sourceTeam) {
                $sourceTeam->members()->detach($user->id);
                // Se o usuário era líder, transfere liderança para outro membro se houver
                if ($sourceTeam->leader_id === $user->id) {
                    $nextLeader = $sourceTeam->members()->first();
                    if ($nextLeader) {
                        $sourceTeam->update(['leader_id' => $nextLeader->id]);
                    }
                }
            }

            if (!$targetTeam->hackathon_id) {
                $targetTeam->update(['hackathon_id' => $hackathon->id]);
            }

            $targetTeam->members()->syncWithoutDetaching([
                $user->id => [
                    'id' => (string) Str::uuid(),
                    'role' => 'member',
                    'joined_at' => now(),
                ]
            ]);

            $registration->update([
                'team_id' => $targetTeam->id,
                'participation_type' => 'team',
                'is_eligible' => true,
            ]);

            AuditService::log('team.member_transferred', $targetTeam, null, [
                'user_id' => $user->id,
                'source_team_id' => $sourceTeam?->id,
                'transferred_by' => $actingUser->id,
            ], $hackathon->organization_id, $hackathon->id);

            NotificationService::notify(
                $user,
                'team_transferred',
                'Transferência de Equipe',
                "Você foi transferido para a equipe '{$targetTeam->name}' no Hackathon '{$hackathon->title}'."
            );

            return $registration->fresh(['team.members', 'user']);
        });
    }

    /**
     * Remove participante da equipe, retornando para inscrição individual
     */
    public function removeParticipantFromTeam(HackathonRegistration $registration, User $actingUser): HackathonRegistration
    {
        $hackathon = $registration->hackathon;
        $team = $registration->team;

        if (!$team) {
            return $registration;
        }

        return DB::transaction(function () use ($registration, $team, $hackathon, $actingUser) {
            $user = $registration->user;
            $team->members()->detach($user->id);

            $isEligible = $hackathon->allowsIndividualRegistration();
            $notes = !$isEligible ? 'Participante sem equipe em evento com exigência de equipe obrigatória.' : null;

            $registration->update([
                'team_id' => null,
                'participation_type' => 'individual',
                'is_eligible' => $isEligible,
                'notes' => $notes,
            ]);

            AuditService::log('team.member_removed', $team, null, [
                'user_id' => $user->id,
                'removed_by' => $actingUser->id,
            ], $hackathon->organization_id, $hackathon->id);

            return $registration->fresh(['user']);
        });
    }

    /**
     * Criação de equipe vinculada ao Hackathon (por participante ou organização)
     */
    public function createTeamForHackathon(Hackathon $hackathon, User $creator, array $data, ?User $actingUser = null): Team
    {
        $actingUser = $actingUser ?: $creator;

        // 1. Validação de modalidade
        if (!$hackathon->allowsTeamRegistration()) {
            throw new Exception('Este Hackathon aceita apenas inscrições individuais.');
        }

        // 2. Validação de permissões de criação
        $isOrgAdmin = $actingUser->isOrganizationAdmin($hackathon->organization_id) || $hackathon->organization->owner_id === $actingUser->id;
        if (!$isOrgAdmin && !$hackathon->allow_team_creation) {
            throw new Exception('A criação de equipes por participantes não está autorizada para este Hackathon.');
        }

        // 3. Validação de prazos
        if (!$isOrgAdmin && !$hackathon->isTeamFormationOpen()) {
            throw new Exception('O prazo para formação de equipes deste Hackathon está encerrado.');
        }

        // 4. Limite máximo de equipes
        if ($hackathon->max_teams) {
            $totalTeams = Team::where('hackathon_id', $hackathon->id)->count();
            if ($totalTeams >= $hackathon->max_teams) {
                throw new Exception('O limite máximo de equipes para este Hackathon foi atingido.');
            }
        }

        return DB::transaction(function () use ($hackathon, $creator, $data, $actingUser) {
            $baseSlug = Str::slug($data['name']);
            $slug = $baseSlug ?: 'equipe';
            $count = 1;
            while (Team::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $count;
                $count++;
            }

            $team = Team::create([
                'hackathon_id' => $hackathon->id,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'area_of_focus' => $data['area_of_focus'] ?? null,
                'technologies' => $data['technologies'] ?? null,
                'leader_id' => $creator->id,
                'is_looking_for_members' => $data['is_looking_for_members'] ?? true,
            ]);

            // Adiciona o criador como líder
            $team->members()->attach($creator->id, [
                'id' => (string) Str::uuid(),
                'role' => 'leader',
                'joined_at' => now(),
            ]);

            // Se o criador já tem uma inscrição neste hackathon, associa ao time
            $existingReg = HackathonRegistration::where('hackathon_id', $hackathon->id)
                ->where('user_id', $creator->id)
                ->first();

            if ($existingReg) {
                $existingReg->update([
                    'team_id' => $team->id,
                    'participation_type' => 'team',
                    'is_eligible' => true,
                    'notes' => null,
                ]);
            }

            AuditService::log('team.created_for_hackathon', $team, null, $team->toArray(), $hackathon->organization_id, $hackathon->id);

            return $team;
        });
    }

    /**
     * Admin review for registrations (approve/reject/disqualify)
     */
    public function updateStatus(HackathonRegistration $registration, string $status, User $adminUser, ?string $reason = null): HackathonRegistration
    {
        $old = $registration->toArray();

        $registration->status = $status;
        if ($status === 'approved') {
            $registration->approved_at = Carbon::now();
            $registration->approved_by = $adminUser->id;
            $registration->rejection_reason = null;
        } elseif ($status === 'rejected' || $status === 'disqualified') {
            $registration->rejection_reason = $reason;
        }

        $registration->save();

        AuditService::log("registration.{$status}", $registration, $old, $registration->toArray(), $registration->hackathon->organization_id, $registration->hackathon_id);

        if ($registration->team) {
            NotificationService::notifyTeam(
                $registration->team,
                "registration_{$status}",
                "Status da Inscrição Atualizado",
                "A inscrição da sua equipe no Hackathon '{$registration->hackathon->title}' agora está: {$status}." . ($reason ? " Motivo: {$reason}" : "")
            );
        } else {
            NotificationService::notify(
                $registration->user,
                "registration_{$status}",
                "Status da Inscrição Atualizado",
                "Sua inscrição individual no Hackathon '{$registration->hackathon->title}' agora está: {$status}." . ($reason ? " Motivo: {$reason}" : "")
            );
        }

        return $registration;
    }

    /**
     * Cancel an existing registration
     */
    public function cancelRegistration(HackathonRegistration $registration, User $user): HackathonRegistration
    {
        if (in_array($registration->status, ['cancelled', 'disqualified'])) {
            throw new Exception('Esta inscrição já está cancelada ou desclassificada.');
        }

        $hackathon = $registration->hackathon;
        if ($hackathon->registration_end_date && Carbon::now()->gt($hackathon->registration_end_date)) {
            throw new Exception('O prazo de inscrições deste Hackathon já foi encerrado e não permite mais cancelamento.');
        }

        return DB::transaction(function () use ($registration, $user, $hackathon) {
            $registration->update([
                'status' => 'cancelled',
                'notes' => 'Inscrição cancelada a pedido do participante em ' . Carbon::now()->format('d/m/Y H:i'),
            ]);

            AuditService::log('registration.cancelled', $registration, null, $registration->toArray(), $hackathon->organization_id, $hackathon->id);

            NotificationService::notify(
                $user,
                'registration_cancelled',
                'Inscrição Cancelada',
                "Sua inscrição no Hackathon '{$hackathon->title}' foi cancelada com sucesso."
            );

            return $registration;
        });
    }

    private function validateRegistrationWindow(Hackathon $hackathon): void
    {
        $now = Carbon::now();
        if ($hackathon->registration_start_date && $now->lt($hackathon->registration_start_date)) {
            throw new Exception('O período de inscrições para este Hackathon ainda não foi iniciado.');
        }

        if ($hackathon->registration_end_date && $now->gt($hackathon->registration_end_date)) {
            throw new Exception('O período de inscrições para este Hackathon já foi encerrado.');
        }

        if (!in_array($hackathon->status, ['published', 'registration'])) {
            throw new Exception('Este Hackathon não está aberto para inscrições no momento.');
        }
    }
}
