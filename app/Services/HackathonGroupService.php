<?php

namespace App\Services;

use App\Models\Hackathon;
use App\Models\HackathonGroup;
use App\Models\HackathonRegistration;
use App\Models\Team;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HackathonGroupService
{
    public function __construct(
        protected RegistrationService $registrationService
    ) {}

    /**
     * Cria um novo Grupo de Participação vinculado exclusivamente a um Hackathon
     */
    public function createGroup(
        Hackathon $hackathon,
        array $data,
        ?User $leader = null,
        array $memberUserIds = [],
        ?User $actingUser = null,
        ?Team $originTeam = null
    ): HackathonGroup {
        $actingUser = $actingUser ?: $leader;
        $isOrgAdmin = $actingUser && (
            $actingUser->isOrganizationAdmin($hackathon->organization_id)
            || $hackathon->organization?->owner_id === $actingUser->id
        );

        // 1. Validação de modalidade
        if (!$hackathon->allowsTeamRegistration()) {
            throw new Exception('Este Hackathon aceita apenas inscrições individuais.');
        }

        // 2. Validação de permissões de formação
        if (!$isOrgAdmin) {
            if (!$hackathon->allowsParticipantTeamCreation()) {
                throw new Exception('A criação de grupos por participantes não está autorizada para este Hackathon.');
            }
            if (!$hackathon->isTeamFormationOpen()) {
                throw new Exception('O prazo para formação de grupos deste Hackathon está encerrado.');
            }
        }

        // 3. Limite máximo de grupos no hackathon
        if ($hackathon->max_teams) {
            $totalGroups = HackathonGroup::where('hackathon_id', $hackathon->id)->count();
            if ($totalGroups >= $hackathon->max_teams) {
                throw new Exception('O limite máximo de equipes/grupos para este Hackathon foi atingido.');
            }
        }

        // 4. Montagem e validação dos integrantes
        $targetMemberIds = [];
        if (!empty($memberUserIds)) {
            $targetMemberIds = array_values(array_unique($memberUserIds));
        } elseif ($actingUser) {
            $targetMemberIds = [$actingUser->id];
        }

        if (empty($targetMemberIds)) {
            throw new Exception('O grupo de participação deve possuir pelo menos um integrante.');
        }

        // Validação de limites mín/máx de integrantes
        if ($hackathon->min_team_members && count($targetMemberIds) < $hackathon->min_team_members) {
            throw new Exception("O grupo possui " . count($targetMemberIds) . " integrantes, mas o mínimo exigido é de {$hackathon->min_team_members}.");
        }
        if ($hackathon->max_team_members && count($targetMemberIds) > $hackathon->max_team_members) {
            throw new Exception("O grupo possui " . count($targetMemberIds) . " integrantes, excedendo o máximo permitido de {$hackathon->max_team_members}.");
        }

        // 5. Validação de elegibilidade e exclusividade por participante
        foreach ($targetMemberIds as $mId) {
            $this->ensureUserEligibleForHackathonGroup($hackathon, $mId);
            $this->ensureUserNotInAnotherGroup($hackathon->id, $mId);
        }

        // 6. Resolução do líder
        $leaderId = $leader?->id;
        if (!empty($data['leader_id'])) {
            $leaderId = $data['leader_id'];
        }

        if ($leaderId && !in_array($leaderId, $targetMemberIds)) {
            throw new Exception('O líder designado deve ser um dos integrantes do grupo.');
        }

        if ($hackathon->requiresTeamLeader() && empty($leaderId)) {
            throw new Exception('Este Hackathon exige a definição de um líder para o grupo.');
        }

        return DB::transaction(function () use ($hackathon, $data, $targetMemberIds, $leaderId, $originTeam) {
            $baseSlug = Str::slug($data['name']);
            $slug = $baseSlug ?: 'grupo';
            $count = 1;
            while (HackathonGroup::where('hackathon_id', $hackathon->id)->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $count;
                $count++;
            }

            $group = HackathonGroup::create([
                'hackathon_id' => $hackathon->id,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'area_of_focus' => $data['area_of_focus'] ?? null,
                'technologies' => $data['technologies'] ?? null,
                'leader_id' => $leaderId,
                'origin_team_id' => $originTeam?->id,
                'status' => 'active',
            ]);

            foreach ($targetMemberIds as $mId) {
                $role = ($leaderId && $mId === $leaderId) ? 'leader' : 'member';
                $group->members()->attach($mId, [
                    'id' => (string) Str::uuid(),
                    'role' => $role,
                    'joined_at' => now(),
                ]);

                // Atualiza ou cria a inscrição do participante vinculada ao grupo
                $existingReg = HackathonRegistration::where('hackathon_id', $hackathon->id)
                    ->where('user_id', $mId)
                    ->first();

                if ($existingReg) {
                    $existingReg->update([
                        'hackathon_group_id' => $group->id,
                        'participation_type' => 'team',
                        'is_eligible' => true,
                        'notes' => null,
                    ]);
                } else {
                    HackathonRegistration::create([
                        'hackathon_id' => $hackathon->id,
                        'user_id' => $mId,
                        'hackathon_group_id' => $group->id,
                        'participation_type' => 'team',
                        'status' => $hackathon->auto_approve_registrations ? 'approved' : 'pending',
                        'is_eligible' => true,
                    ]);
                }
            }

            AuditService::log('hackathon_group.created', $group, null, $group->toArray(), $hackathon->organization_id, $hackathon->id);

            return $group->fresh(['members', 'leader']);
        });
    }

    /**
     * Permite formar um grupo de participação a partir de uma equipe permanente (guilda)
     * Somente membros com inscrição válida neste hackathon são incluídos!
     */
    public function formGroupFromGuild(
        Hackathon $hackathon,
        Team $guild,
        User $actingUser,
        ?string $groupName = null,
        ?User $leader = null
    ): HackathonGroup {
        // Validação: usuário deve ser membro da guilda
        if (!$guild->members()->where('users.id', $actingUser->id)->exists()) {
            throw new Exception('Você não é integrante desta equipe permanente.');
        }

        // Obtém membros da guilda que possuem inscrição válida e elegível no hackathon
        $guildMemberIds = $guild->members()->pluck('users.id')->toArray();
        $eligibleMemberIds = [];

        foreach ($guildMemberIds as $mId) {
            try {
                $this->ensureUserEligibleForHackathonGroup($hackathon, $mId);
                $this->ensureUserNotInAnotherGroup($hackathon->id, $mId);
                $eligibleMemberIds[] = $mId;
            } catch (\Throwable $e) {
                // Membro da guilda sem inscrição ou inelegível para o hackathon é excluído
            }
        }

        if (empty($eligibleMemberIds)) {
            throw new Exception("Nenhum integrante da equipe '{$guild->name}' possui inscrição válida para este Hackathon.");
        }

        $name = $groupName ?: $guild->name;
        $leaderToAssign = ($leader && in_array($leader->id, $eligibleMemberIds)) ? $leader : (in_array($actingUser->id, $eligibleMemberIds) ? $actingUser : null);

        return $this->createGroup(
            $hackathon,
            [
                'name' => $name,
                'description' => $guild->description,
                'area_of_focus' => $guild->area_of_focus,
                'technologies' => $guild->technologies,
            ],
            $leaderToAssign,
            $eligibleMemberIds,
            $actingUser,
            $guild
        );
    }

    /**
     * Vincula um participante com inscrição válida a um grupo de participação
     */
    public function assignParticipantToGroup(
        HackathonRegistration $registration,
        HackathonGroup $group,
        User $actingUser
    ): HackathonRegistration {
        $hackathon = $registration->hackathon;

        if ($group->hackathon_id !== $hackathon->id) {
            throw new Exception('O grupo de participação pertence a outro Hackathon.');
        }

        $this->ensureUserEligibleForHackathonGroup($hackathon, $registration->user_id);
        $this->ensureUserNotInAnotherGroup($hackathon->id, $registration->user_id, $group->id);

        $isOrgAdmin = $actingUser->isOrganizationAdmin($hackathon->organization_id)
            || $hackathon->organization?->owner_id === $actingUser->id;

        if (!$isOrgAdmin && !$hackathon->isTeamFormationOpen()) {
            throw new Exception('O prazo para formação e alteração de grupos deste Hackathon está encerrado.');
        }

        if (!$group->hasAvailableSlots()) {
            throw new Exception("O grupo '{$group->name}' já atingiu o limite máximo de {$hackathon->max_team_members} integrantes.");
        }

        return DB::transaction(function () use ($registration, $group, $hackathon, $actingUser) {
            $user = $registration->user;

            // Remove de qualquer outro grupo deste hackathon
            $oldGroupIds = DB::table('hackathon_group_members')
                ->join('hackathon_groups', 'hackathon_group_members.hackathon_group_id', '=', 'hackathon_groups.id')
                ->where('hackathon_groups.hackathon_id', $hackathon->id)
                ->where('hackathon_group_members.user_id', $user->id)
                ->pluck('hackathon_groups.id')
                ->toArray();

            if (!empty($oldGroupIds)) {
                DB::table('hackathon_group_members')
                    ->whereIn('hackathon_group_id', $oldGroupIds)
                    ->where('user_id', $user->id)
                    ->delete();
            }

            // Adiciona ao novo grupo
            $group->members()->syncWithoutDetaching([
                $user->id => [
                    'id' => (string) Str::uuid(),
                    'role' => 'member',
                    'joined_at' => now(),
                ]
            ]);

            // Atualiza inscrição
            $registration->update([
                'hackathon_group_id' => $group->id,
                'participation_type' => 'team',
                'is_eligible' => true,
                'notes' => null,
            ]);

            AuditService::log('hackathon_group.member_assigned', $group, null, [
                'user_id' => $user->id,
                'assigned_by' => $actingUser->id,
            ], $hackathon->organization_id, $hackathon->id);

            NotificationService::notify(
                $user,
                'group_assigned',
                'Você foi adicionado a um grupo',
                "Você foi integrado ao grupo '{$group->name}' no Hackathon '{$hackathon->title}'."
            );

            return $registration->fresh(['group.members', 'user']);
        });
    }

    /**
     * Transfere participante entre grupos do mesmo hackathon
     */
    public function transferParticipant(
        HackathonRegistration $registration,
        HackathonGroup $targetGroup,
        User $actingUser
    ): HackathonRegistration {
        $hackathon = $registration->hackathon;

        if ($targetGroup->hackathon_id !== $hackathon->id) {
            throw new Exception('O grupo de destino pertence a outro Hackathon.');
        }

        $this->ensureUserEligibleForHackathonGroup($hackathon, $registration->user_id);
        $this->ensureUserNotInAnotherGroup($hackathon->id, $registration->user_id, array_filter([$targetGroup->id, $registration->hackathon_group_id]));

        if (!$targetGroup->hasAvailableSlots()) {
            throw new Exception("O grupo de destino atingiu o limite máximo de {$hackathon->max_team_members} integrantes.");
        }

        $isOrgAdmin = $actingUser->isOrganizationAdmin($hackathon->organization_id)
            || $hackathon->organization?->owner_id === $actingUser->id;

        if (!$isOrgAdmin && !$hackathon->isTeamManagementOpen()) {
            throw new Exception('O prazo para transferência de membros entre grupos está encerrado.');
        }

        return DB::transaction(function () use ($registration, $targetGroup, $hackathon, $actingUser) {
            $user = $registration->user;
            $sourceGroup = $registration->group;

            if ($sourceGroup) {
                $sourceGroup->members()->detach($user->id);
                if ($sourceGroup->leader_id === $user->id) {
                    $nextLeader = $sourceGroup->members()->first();
                    $sourceGroup->update(['leader_id' => $nextLeader?->id]);
                    if ($nextLeader) {
                        $sourceGroup->members()->updateExistingPivot($nextLeader->id, ['role' => 'leader']);
                    }
                }
            }

            $targetGroup->members()->syncWithoutDetaching([
                $user->id => [
                    'id' => (string) Str::uuid(),
                    'role' => 'member',
                    'joined_at' => now(),
                ]
            ]);

            $registration->update([
                'hackathon_group_id' => $targetGroup->id,
                'participation_type' => 'team',
                'is_eligible' => true,
            ]);

            AuditService::log('hackathon_group.member_transferred', $targetGroup, null, [
                'user_id' => $user->id,
                'source_group_id' => $sourceGroup?->id,
                'transferred_by' => $actingUser->id,
            ], $hackathon->organization_id, $hackathon->id);

            return $registration->fresh(['group.members', 'user']);
        });
    }

    /**
     * Remove participante do grupo de participação, retornando para individual
     * NÃO altera nenhuma equipe permanente da qual o participante faça parte!
     */
    public function removeParticipantFromGroup(
        HackathonRegistration $registration,
        User $actingUser
    ): HackathonRegistration {
        $hackathon = $registration->hackathon;
        $group = $registration->group;

        if (!$group) {
            return $registration;
        }

        return DB::transaction(function () use ($registration, $group, $hackathon, $actingUser) {
            $user = $registration->user;
            $group->members()->detach($user->id);

            if ($group->leader_id === $user->id) {
                $group->update(['leader_id' => null]);
            }

            $isEligible = $hackathon->allowsIndividualRegistration();
            $notes = !$isEligible ? 'Participante sem grupo em evento com exigência de grupo obrigatório.' : null;

            $registration->update([
                'hackathon_group_id' => null,
                'team_id' => null,
                'participation_type' => 'individual',
                'is_eligible' => $isEligible,
                'notes' => $notes,
            ]);

            AuditService::log('hackathon_group.member_removed', $group, null, [
                'user_id' => $user->id,
                'removed_by' => $actingUser->id,
            ], $hackathon->organization_id, $hackathon->id);

            return $registration->fresh(['user']);
        });
    }

    /**
     * Escolhe ou altera o líder do grupo de participação
     */
    public function electLeader(HackathonGroup $group, ?User $newLeader, User $actingUser): HackathonGroup
    {
        $hackathon = $group->hackathon;
        $isMember = $group->members()->where('users.id', $actingUser->id)->exists();
        $isOrgAdmin = $actingUser->isOrganizationAdmin($hackathon->organization_id)
            || $hackathon->organization?->owner_id === $actingUser->id;

        if (!$isMember && !$isOrgAdmin) {
            throw new Exception('Apenas integrantes do grupo ou administradores da organização podem definir a liderança.');
        }

        if ($newLeader) {
            if (!$group->members()->where('users.id', $newLeader->id)->exists()) {
                throw new Exception('O líder designado deve ser um integrante deste grupo de participação.');
            }
        } elseif ($hackathon->requiresTeamLeader()) {
            throw new Exception('Este Hackathon exige a definição de um líder para o grupo.');
        }

        return DB::transaction(function () use ($group, $newLeader, $actingUser, $hackathon) {
            $allMemberIds = $group->members()->pluck('users.id')->toArray();
            if (!empty($allMemberIds)) {
                $group->members()->updateExistingPivot($allMemberIds, ['role' => 'member']);
            }

            if ($newLeader) {
                $group->update(['leader_id' => $newLeader->id]);
                $group->members()->updateExistingPivot($newLeader->id, ['role' => 'leader']);
            } else {
                $group->update(['leader_id' => null]);
            }

            AuditService::log('hackathon_group.leader_changed', $group, null, [
                'leader_id' => $newLeader?->id,
                'changed_by' => $actingUser->id,
            ], $hackathon->organization_id, $hackathon->id);

            return $group->fresh(['members', 'leader']);
        });
    }

    /**
     * Valida exclusividade: o participante não pode pertencer a mais de um grupo no mesmo hackathon
     */
    public function ensureUserNotInAnotherGroup(string $hackathonId, string $userId, array|string|null $excludeGroupIds = null): void
    {
        $excludeGroupIds = !empty($excludeGroupIds) ? (array) $excludeGroupIds : [];

        // Verifica na tabela de membros de grupo do hackathon
        $existingGroupMember = DB::table('hackathon_group_members')
            ->join('hackathon_groups', 'hackathon_group_members.hackathon_group_id', '=', 'hackathon_groups.id')
            ->join('users', 'hackathon_group_members.user_id', '=', 'users.id')
            ->where('hackathon_groups.hackathon_id', $hackathonId)
            ->where('hackathon_group_members.user_id', $userId)
            ->when(!empty($excludeGroupIds), function ($query) use ($excludeGroupIds) {
                return $query->whereNotIn('hackathon_groups.id', $excludeGroupIds);
            })
            ->select('users.name', 'hackathon_groups.name as group_name')
            ->first();

        if ($existingGroupMember) {
            throw new Exception("O participante {$existingGroupMember->name} já pertence ao grupo '{$existingGroupMember->group_name}' neste Hackathon.");
        }

        // Verifica na tabela de inscrições
        $existingRegistration = DB::table('hackathon_registrations')
            ->join('users', 'hackathon_registrations.user_id', '=', 'users.id')
            ->join('hackathon_groups', 'hackathon_registrations.hackathon_group_id', '=', 'hackathon_groups.id')
            ->where('hackathon_registrations.hackathon_id', $hackathonId)
            ->where('hackathon_registrations.user_id', $userId)
            ->whereNotNull('hackathon_registrations.hackathon_group_id')
            ->whereNotIn('hackathon_registrations.status', ['cancelled', 'rejected', 'disqualified'])
            ->when(!empty($excludeGroupIds), function ($query) use ($excludeGroupIds) {
                return $query->whereNotIn('hackathon_registrations.hackathon_group_id', $excludeGroupIds);
            })
            ->select('users.name', 'hackathon_groups.name as group_name')
            ->first();

        if ($existingRegistration) {
            throw new Exception("O participante {$existingRegistration->name} já possui inscrição ativa no grupo '{$existingRegistration->group_name}' neste Hackathon.");
        }
    }

    /**
     * Valida se o usuário possui inscrição válida e elegível no Hackathon
     */
    public function ensureUserEligibleForHackathonGroup(Hackathon $hackathon, string $userId, bool $requireExisting = true): ?HackathonRegistration
    {
        $registration = HackathonRegistration::with('user')->where('hackathon_id', $hackathon->id)
            ->where('user_id', $userId)
            ->first();

        if (!$registration) {
            if ($requireExisting) {
                $user = User::find($userId);
                $userName = $user ? $user->name : 'O participante';
                throw new Exception("{$userName} não possui inscrição registrada para o Hackathon '{$hackathon->title}'.");
            }
            return null;
        }

        if (in_array($registration->status, ['cancelled', 'rejected', 'disqualified'])) {
            $userName = $registration->user?->name ?? 'O participante';
            throw new Exception("A inscrição de {$userName} está com status '{$registration->status}' e não é elegível para ingressar em grupos.");
        }

        if (!$hackathon->auto_approve_registrations && $registration->status === 'pending') {
            $userName = $registration->user?->name ?? 'O participante';
            throw new Exception("A inscrição de {$userName} ainda está pendente de aprovação pela organização.");
        }

        return $registration;
    }
}
