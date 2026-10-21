<?php

namespace App\Services;

use App\Mail\GuildInvitationMail;
use App\Models\Hackathon;
use App\Models\HackathonRegistration;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TeamService
{
    public function __construct(
        protected RegistrationService $registrationService
    ) {}

    /**
     * Cria uma nova equipe aplicando regras de hackathon quando vinculado,
     * ou criando uma Equipe Permanente (Guilda) independente quando hackathon_id não for informado.
     */
    public function createTeam(User $creator, array $data): Team
    {
        $hackathon = null;
        if (!empty($data['hackathon_id'])) {
            $hackathon = Hackathon::findOrFail($data['hackathon_id']);
            if (!$hackathon->allowsTeamRegistration()) {
                throw new Exception('Este Hackathon aceita apenas inscrições individuais.');
            }
            if (!$hackathon->allowsParticipantTeamCreation()) {
                throw new Exception('A criação de equipes por participantes não está autorizada para este Hackathon.');
            }
            if (!$hackathon->isTeamFormationOpen()) {
                throw new Exception('O prazo limite para formação de equipes neste Hackathon foi encerrado.');
            }

            // Valida elegibilidade e exclusividade do criador
            $this->registrationService->ensureUserEligibleForHackathonTeam($hackathon, $creator->id, false);
            $this->registrationService->ensureUserNotInAnotherTeam($hackathon->id, $creator->id);
        }

        // Upload de imagem ou logo da guilda
        if (isset($data['image']) && $data['image'] instanceof UploadedFile && $data['image']->isValid()) {
            $path = $data['image']->store('guilds/logos', 'public');
            $data['logo'] = '/storage/' . $path;
        } elseif (isset($data['logo']) && $data['logo'] instanceof UploadedFile && $data['logo']->isValid()) {
            $path = $data['logo']->store('guilds/logos', 'public');
            $data['logo'] = '/storage/' . $path;
        }
        unset($data['image']);

        $slug = Str::slug($data['name']);
        $count = 1;
        while (Team::where('slug', $slug)->exists()) {
            $slug = Str::slug($data['name']) . '-' . $count;
            $count++;
        }
        $data['slug'] = $slug;
        $data['creator_id'] = $creator->id;

        // Determinação do líder inicial da equipe
        if (array_key_exists('set_as_leader', $data) && !$data['set_as_leader']) {
            $leaderId = null;
        } elseif (array_key_exists('leader_id', $data) && empty($data['leader_id'])) {
            $leaderId = null;
        } else {
            $leaderId = $creator->id;
        }
        $data['leader_id'] = $leaderId;

        return DB::transaction(function () use ($creator, $data, $leaderId, $hackathon) {
            $team = Team::create($data);

            // Adiciona criador como membro (com role leader se definido como líder, senão member)
            $team->members()->attach($creator->id, [
                'id' => (string) Str::uuid(),
                'role' => ($leaderId === $creator->id) ? 'leader' : 'member',
                'joined_at' => now(),
            ]);

            // Se criada dentro do escopo de um hackathon, sincroniza registro
            if (!empty($data['hackathon_id'])) {
                HackathonRegistration::updateOrCreate(
                    [
                        'hackathon_id' => $data['hackathon_id'],
                        'user_id' => $creator->id,
                    ],
                    [
                        'team_id' => $team->id,
                        'participation_type' => 'team',
                        'is_eligible' => true,
                        'notes' => null,
                    ]
                );
            }

            // Convites automáticos passados na criação (ex: fluxo de equipes independentes)
            if (!empty($data['invite_emails'])) {
                $emails = is_array($data['invite_emails'])
                    ? $data['invite_emails']
                    : array_filter(array_map('trim', explode(',', $data['invite_emails'])));

                foreach ($emails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $this->inviteMember($team, $creator, $email);
                        } catch (\Throwable $e) {
                            // Ignora erros de convites individuais para não abortar criação da equipe
                        }
                    }
                }
            }

            AuditService::log('team.create', $team, null, $team->toArray(), null, $data['hackathon_id'] ?? null);

            return $team;
        });
    }

    /**
     * Atualiza dados de uma guilda (nome, descrição, área de foco, imagem)
     */
    public function updateGuild(Team $team, array $data, User $actingUser): Team
    {
        if (!$team->canManage($actingUser)) {
            throw new Exception('Apenas o líder ou criador da guilda pode editar suas informações.');
        }

        // Remoção ou substituição de imagem
        $removeImage = !empty($data['remove_image']);
        $newImage = $data['image'] ?? $data['logo'] ?? null;

        if ($removeImage) {
            if ($team->logo && str_starts_with($team->logo, '/storage/')) {
                $oldPath = substr($team->logo, strlen('/storage/'));
                Storage::disk('public')->delete($oldPath);
            }
            $team->logo = null;
        } elseif ($newImage instanceof UploadedFile && $newImage->isValid()) {
            if ($team->logo && str_starts_with($team->logo, '/storage/')) {
                $oldPath = substr($team->logo, strlen('/storage/'));
                Storage::disk('public')->delete($oldPath);
            }
            $path = $newImage->store('guilds/logos', 'public');
            $team->logo = '/storage/' . $path;
        }

        if (!empty($data['name']) && $data['name'] !== $team->name) {
            $slug = Str::slug($data['name']);
            $count = 1;
            while (Team::where('slug', $slug)->where('id', '!=', $team->id)->exists()) {
                $slug = Str::slug($data['name']) . '-' . $count;
                $count++;
            }
            $team->name = $data['name'];
            $team->slug = $slug;
        }

        if (array_key_exists('description', $data)) {
            $team->description = $data['description'];
        }

        if (array_key_exists('area_of_focus', $data)) {
            $team->area_of_focus = $data['area_of_focus'];
        }

        if (array_key_exists('is_looking_for_members', $data)) {
            $team->is_looking_for_members = (bool) $data['is_looking_for_members'];
        }

        $team->save();

        AuditService::log('team.update', $team, null, $team->toArray(), null, $team->hackathon_id);

        return $team->fresh(['members', 'leader', 'creator']);
    }

    /**
     * Exclui uma guilda definitivamente
     */
    public function deleteGuild(Team $team, User $actingUser): void
    {
        // Apenas o criador (ou líder atual se criador for nulo) pode excluir a guilda
        $canDelete = $team->creator_id ? ($team->creator_id === $actingUser->id) : ($team->leader_id === $actingUser->id);
        if (!$canDelete) {
            throw new Exception('Apenas o criador da guilda tem autorização para excluí-la.');
        }

        // Deleta arquivo de imagem se existir
        if ($team->logo && str_starts_with($team->logo, '/storage/')) {
            $oldPath = substr($team->logo, strlen('/storage/'));
            Storage::disk('public')->delete($oldPath);
        }

        DB::transaction(function () use ($team) {
            $team->invitations()->delete();
            $team->members()->detach();
            $team->delete();
        });
    }

    /**
     * Remove um membro da guilda
     */
    public function removeMember(Team $team, User $memberToRemove, User $actingUser): void
    {
        if (!$team->canManage($actingUser)) {
            throw new Exception('Apenas o líder ou criador da guilda pode remover integrantes.');
        }

        if ($team->isCreator($memberToRemove)) {
            throw new Exception('O criador original da guilda não pode ser removido.');
        }

        if ($actingUser->id === $memberToRemove->id) {
            throw new Exception('Para sair da guilda utilize a opção voluntária de desligamento.');
        }

        if (!$team->isMember($memberToRemove)) {
            throw new Exception('Este usuário não integra a guilda.');
        }

        // Se o membro a ser removido for o líder, deve haver criador para reassumir ou transferir
        if ($team->isLeader($memberToRemove)) {
            $successor = $team->creator_id && $team->creator_id !== $memberToRemove->id
                ? User::find($team->creator_id)
                : $team->members()->where('users.id', '!=', $memberToRemove->id)->first();

            $team->update(['leader_id' => $successor?->id]);
            if ($successor) {
                $team->members()->updateExistingPivot($successor->id, ['role' => 'leader']);
            }
        }

        DB::transaction(function () use ($team, $memberToRemove) {
            $team->members()->detach($memberToRemove->id);

            NotificationService::notify(
                $memberToRemove,
                'team_removed',
                'Remoção de Guilda',
                "Você foi removido da guilda '{$team->name}'."
            );

            AuditService::log('team.member_removed', $team, null, [
                'user_id' => $memberToRemove->id,
            ], null, $team->hackathon_id);
        });
    }

    /**
     * Desligamento voluntário de um membro da guilda
     */
    public function leaveGuild(Team $team, User $user): void
    {
        if (!$team->isMember($user)) {
            throw new Exception('Você não faz parte desta guilda.');
        }

        $otherMembersCount = $team->members()->where('users.id', '!=', $user->id)->count();

        // Se for o líder e houver outros integrantes, deve transferir a liderança antes
        if ($team->isLeader($user) && $otherMembersCount > 0) {
            throw new Exception('Você é o líder da guilda. Transfira a liderança para outro integrante antes de sair.');
        }

        DB::transaction(function () use ($team, $user, $otherMembersCount) {
            $team->members()->detach($user->id);

            // Se a guilda ficou sem nenhum membro restante
            if ($otherMembersCount === 0) {
                $this->deleteGuild($team, $user);
                return;
            }

            AuditService::log('team.member_left', $team, null, [
                'user_id' => $user->id,
            ], null, $team->hackathon_id);
        });
    }

    /**
     * Escolha / substituição livre de líder pelos integrantes da equipe ou pela organização
     */
    public function electLeader(Team $team, ?User $newLeader, User $actingUser): Team
    {
        $isMember = $team->members()->where('users.id', $actingUser->id)->exists();
        $isOrgAdmin = false;
        if ($team->hackathon) {
            $isOrgAdmin = $actingUser->isOrganizationAdmin($team->hackathon->organization_id)
                || $team->hackathon->organization?->owner_id === $actingUser->id;
        }

        if (!$isMember && !$isOrgAdmin) {
            throw new Exception('Apenas integrantes da equipe ou administradores da organização podem definir ou alterar a liderança.');
        }

        if ($newLeader) {
            if (!$team->members()->where('users.id', $newLeader->id)->exists()) {
                throw new Exception('O líder designado deve ser um integrante da equipe.');
            }
        }

        return DB::transaction(function () use ($team, $newLeader, $actingUser) {
            $allMemberIds = $team->members()->pluck('users.id')->toArray();
            if (!empty($allMemberIds)) {
                $team->members()->updateExistingPivot($allMemberIds, ['role' => 'member']);
            }

            if ($newLeader) {
                $team->update(['leader_id' => $newLeader->id]);
                $team->members()->updateExistingPivot($newLeader->id, ['role' => 'leader']);

                NotificationService::notifyTeam(
                    $team,
                    'leader_elected',
                    'Novo Líder da Equipe',
                    "{$newLeader->name} foi definido como o novo líder da equipe '{$team->name}'."
                );
            } else {
                $team->update(['leader_id' => null]);
            }

            AuditService::log('team.leader_changed', $team, null, [
                'leader_id' => $newLeader?->id,
                'changed_by' => $actingUser->id,
            ], $team->hackathon?->organization_id, $team->hackathon_id);

            return $team->fresh(['members', 'leader']);
        });
    }

    /**
     * Envia convite de membro para uma equipe (permanente ou de hackathon)
     */
    public function inviteMember(Team $team, User $sender, string $emailOrUserId): TeamInvitation
    {
        $isMember = $team->members()->where('users.id', $sender->id)->exists();
        if (!$isMember) {
            throw new Exception('Apenas integrantes da equipe podem enviar convites.');
        }

        // Se vinculado a um Hackathon específico:
        if ($team->hackathon_id && $team->hackathon) {
            if (!$team->hackathon->allow_team_invites) {
                throw new Exception('Envio de convites para equipes não é permitido neste Hackathon.');
            }
        }

        $invitee = filter_var($emailOrUserId, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $emailOrUserId)->first()
            : User::where('username', $emailOrUserId)->orWhere('id', $emailOrUserId)->first();

        // No contexto de um Hackathon:
        if ($team->hackathon_id && $team->hackathon) {
            if (!$invitee) {
                throw new Exception('No contexto deste Hackathon, convites só podem ser enviados a participantes cadastrados com conta no Motirõ.');
            }

            $this->registrationService->ensureUserEligibleForHackathonTeam($team->hackathon, $invitee->id);
            $this->registrationService->ensureUserNotInAnotherTeam($team->hackathon_id, $invitee->id, $team->id);

            if (!$team->hasAvailableSlots()) {
                throw new Exception("A equipe já atingiu o limite máximo de {$team->hackathon->max_team_members} integrantes para este evento.");
            }
        }

        // Se o usuário foi localizado, verifica se já faz parte da equipe
        if ($invitee && $team->members()->where('users.id', $invitee->id)->exists()) {
            throw new Exception('Este usuário já faz parte da equipe.');
        }

        // Verifica convite pendente existente
        $existingInviteQuery = TeamInvitation::where('team_id', $team->id)->where('status', 'pending');
        if ($invitee) {
            $existingInviteQuery->where(function ($q) use ($invitee) {
                $q->where('invitee_id', $invitee->id)->orWhere('email', $invitee->email);
            });
        } else {
            $existingInviteQuery->where('email', $emailOrUserId);
        }

        if ($existingInviteQuery->exists()) {
            throw new Exception('Já existe um convite pendente para este participante.');
        }

        $plainToken = Str::random(40);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'inviter_id' => $sender->id,
            'invitee_id' => $invitee?->id,
            'email' => $invitee ? $invitee->email : $emailOrUserId,
            'token' => $plainToken,
            'type' => 'invite',
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $recipientEmail = $invitee ? $invitee->email : $emailOrUserId;
        if (filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($recipientEmail)->send(new GuildInvitationMail($team, $invitation, $sender, $plainToken));
            } catch (\Throwable $e) {
                Log::warning("Falha ao enviar e-mail de convite para {$recipientEmail}: " . $e->getMessage());
            }
        }

        if ($invitee) {
            NotificationService::notify(
                $invitee,
                'team_invite',
                'Convite para Equipe',
                "Você foi convidado por {$sender->name} para integrar a equipe '{$team->name}'."
            );
        }

        AuditService::log('team.invite_sent', $team, null, $invitation->toArray(), null, $team->hackathon_id);

        return $invitation;
    }

    /**
     * Reenvia um convite de participação na guilda (com rate limiting de 2 minutos)
     */
    public function resendInvitation(TeamInvitation $invitation, User $actingUser): TeamInvitation
    {
        $team = $invitation->team;
        if (!$team->canManage($actingUser)) {
            throw new Exception('Apenas o líder ou criador da guilda pode reenviar convites.');
        }

        if ($invitation->status !== 'pending') {
            throw new Exception('Apenas convites pendentes podem ser reenviados.');
        }

        // Rate limit: mínimo de 2 minutos entre reenvios
        if ($invitation->updated_at && $invitation->updated_at->diffInMinutes(now()) < 2 && $invitation->created_at->diffInMinutes(now()) < 2) {
            throw new Exception('Aguarde ao menos 2 minutos antes de reenviar este convite.');
        }

        $plainToken = Str::random(40);
        $invitation->update([
            'token' => $plainToken,
            'expires_at' => now()->addDays(7),
            'updated_at' => now(),
        ]);

        $recipientEmail = $invitation->email;
        if (filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($recipientEmail)->send(new GuildInvitationMail($team, $invitation, $actingUser, $plainToken));
            } catch (\Throwable $e) {
                Log::warning("Falha ao reenviar e-mail de convite para {$recipientEmail}: " . $e->getMessage());
            }
        }

        AuditService::log('team.invite_resent', $team, null, $invitation->toArray(), null, $team->hackathon_id);

        return $invitation;
    }

    /**
     * Cancela um convite pendente da guilda
     */
    public function cancelInvitation(TeamInvitation $invitation, User $actingUser): void
    {
        $team = $invitation->team;
        if (!$team->canManage($actingUser)) {
            throw new Exception('Apenas o líder ou criador da guilda pode cancelar convites.');
        }

        if ($invitation->status !== 'pending') {
            throw new Exception('Apenas convites pendentes podem ser cancelados.');
        }

        $invitation->update([
            'status' => 'cancelled',
        ]);

        AuditService::log('team.invite_cancelled', $team, null, $invitation->toArray(), null, $team->hackathon_id);
    }

    /**
     * Aceita convite a partir do token público enviado por e-mail
     */
    public function acceptInvitationByToken(string $token, User $user): Team
    {
        $invitation = TeamInvitation::with('team')
            ->where(function ($q) use ($token) {
                $q->where('token', $token)
                  ->orWhere('token', hash('sha256', $token));
            })
            ->first();

        if (!$invitation) {
            throw new Exception('Convite não encontrado ou token inválido.');
        }

        if ($invitation->status !== 'pending') {
            throw new Exception('Este convite já foi processado ou não está mais ativo.');
        }

        if ($invitation->isExpired()) {
            throw new Exception('Este convite expirou.');
        }

        $matchesId = $invitation->invitee_id && $invitation->invitee_id === $user->id;
        $matchesEmail = !empty($invitation->email) && strtolower($invitation->email) === strtolower($user->email);

        if (!$matchesId && !$matchesEmail) {
            throw new Exception("Este convite foi enviado para outro participante ({$invitation->email}).");
        }

        $this->acceptInvitation($invitation, $user);

        return $invitation->team;
    }

    /**
     * Recusa convite a partir do token público enviado por e-mail
     */
    public function declineInvitationByToken(string $token, User $user): void
    {
        $invitation = TeamInvitation::with('team')
            ->where(function ($q) use ($token) {
                $q->where('token', $token)
                  ->orWhere('token', hash('sha256', $token));
            })
            ->first();

        if (!$invitation) {
            throw new Exception('Convite não encontrado ou token inválido.');
        }

        if ($invitation->status !== 'pending') {
            throw new Exception('Este convite já foi respondido.');
        }

        $matchesId = $invitation->invitee_id && $invitation->invitee_id === $user->id;
        $matchesEmail = !empty($invitation->email) && strtolower($invitation->email) === strtolower($user->email);

        if (!$matchesId && !$matchesEmail) {
            throw new Exception('Acesso não autorizado para responder a este convite.');
        }

        $this->declineInvitation($invitation, $user);
    }

    /**
     * Aceita um convite de participação na equipe
     */
    public function acceptInvitation(TeamInvitation $invitation, User $user): void
    {
        $matchesId = $invitation->invitee_id && $invitation->invitee_id === $user->id;
        $matchesEmail = !empty($invitation->email) && strtolower($invitation->email) === strtolower($user->email);

        if (!$matchesId && !$matchesEmail) {
            throw new Exception('Acesso não autorizado para responder a este convite.');
        }

        if ($invitation->status !== 'pending') {
            throw new Exception('Este convite já foi respondido ou expirou.');
        }

        $team = $invitation->team;

        // Se vinculado a um Hackathon, aplica regras de elegibilidade e exclusividade
        if ($team->hackathon_id && $team->hackathon) {
            $hackathon = $team->hackathon;
            if (!$team->hasAvailableSlots()) {
                throw new Exception('A equipe já atingiu o limite máximo de integrantes.');
            }

            $this->registrationService->ensureUserEligibleForHackathonTeam($hackathon, $user->id);
            $this->registrationService->ensureUserNotInAnotherTeam($team->hackathon_id, $user->id, $team->id);
        }

        DB::transaction(function () use ($invitation, $team, $user) {
            $invitation->update([
                'status' => 'accepted',
                'invitee_id' => $user->id,
            ]);

            $team->members()->syncWithoutDetaching([
                $user->id => [
                    'id' => (string) Str::uuid(),
                    'role' => 'member',
                    'joined_at' => now(),
                ]
            ]);

            // Sincroniza inscrição no hackathon se a equipe estiver vinculada a um evento
            if ($team->hackathon_id) {
                HackathonRegistration::updateOrCreate(
                    [
                        'hackathon_id' => $team->hackathon_id,
                        'user_id' => $user->id,
                    ],
                    [
                        'team_id' => $team->id,
                        'participation_type' => 'team',
                        'is_eligible' => true,
                        'notes' => null,
                    ]
                );
            }

            $leader = $team->leader;
            if ($leader) {
                NotificationService::notify(
                    $leader,
                    'team_invite_accepted',
                    'Convite Aceito',
                    "{$user->name} aceitou o convite e agora faz parte da equipe '{$team->name}'."
                );
            }

            AuditService::log('team.invite_accepted', $team, null, ['user_id' => $user->id], null, $team->hackathon_id);
        });
    }

    /**
     * Recusa um convite de participação na equipe
     */
    public function declineInvitation(TeamInvitation $invitation, User $user): void
    {
        $matchesId = $invitation->invitee_id && $invitation->invitee_id === $user->id;
        $matchesEmail = !empty($invitation->email) && strtolower($invitation->email) === strtolower($user->email);

        if (!$matchesId && !$matchesEmail) {
            throw new Exception('Acesso não autorizado para responder a este convite.');
        }

        $invitation->update([
            'status' => 'declined',
            'invitee_id' => $user->id,
        ]);

        AuditService::log('team.invite_declined', $invitation->team, null, ['user_id' => $user->id]);
    }

    /**
     * Solicita entrada em uma equipe
     */
    public function requestToJoin(Team $team, User $user, ?string $message = null): TeamInvitation
    {
        if ($team->members()->where('users.id', $user->id)->exists()) {
            throw new Exception('Você já é integrante desta equipe.');
        }

        if ($team->hackathon_id && $team->hackathon) {
            if (!$team->hackathon->allow_team_requests) {
                throw new Exception('Solicitações de entrada não são permitidas para este Hackathon.');
            }

            if (!$team->hasAvailableSlots()) {
                throw new Exception('Esta equipe já atingiu o limite de integrantes.');
            }

            $this->registrationService->ensureUserEligibleForHackathonTeam($team->hackathon, $user->id, false);
            $this->registrationService->ensureUserNotInAnotherTeam($team->hackathon_id, $user->id, $team->id);
        }

        $existing = TeamInvitation::where('team_id', $team->id)
            ->where('inviter_id', $user->id)
            ->where('type', 'request')
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            throw new Exception('Você já possui uma solicitação pendente para esta equipe.');
        }

        $request = TeamInvitation::create([
            'team_id' => $team->id,
            'inviter_id' => $user->id,
            'invitee_id' => $team->leader_id,
            'email' => $user->email,
            'type' => 'request',
            'status' => 'pending',
            'token' => Str::random(40),
            'message' => $message,
            'expires_at' => now()->addDays(7),
        ]);

        $leader = $team->leader;
        if ($leader) {
            NotificationService::notify(
                $leader,
                'team_join_request',
                'Nova Solicitação de Entrada',
                "{$user->name} solicitou entrada na equipe '{$team->name}'."
            );
        }

        return $request;
    }

    /**
     * Aceita uma solicitação de entrada (ação do líder ou integrantes da equipe)
     */
    public function acceptRequest(TeamInvitation $request, User $actingUser): void
    {
        $team = $request->team;
        $this->authorizeLeadershipOrMembership($team, $actingUser);

        if ($request->status !== 'pending') {
            throw new Exception('Esta solicitação já foi respondida ou expirou.');
        }

        $applicant = User::findOrFail($request->inviter_id);

        if ($team->hackathon_id && $team->hackathon) {
            if (!$team->hasAvailableSlots()) {
                throw new Exception('A equipe já atingiu o limite de integrantes.');
            }

            $this->registrationService->ensureUserEligibleForHackathonTeam($team->hackathon, $applicant->id, false);
            $this->registrationService->ensureUserNotInAnotherTeam($team->hackathon_id, $applicant->id, $team->id);
        }

        DB::transaction(function () use ($request, $team, $applicant) {
            $request->update(['status' => 'accepted']);

            $team->members()->syncWithoutDetaching([
                $applicant->id => [
                    'id' => (string) Str::uuid(),
                    'role' => 'member',
                    'joined_at' => now(),
                ]
            ]);

            if ($team->hackathon_id) {
                HackathonRegistration::updateOrCreate(
                    [
                        'hackathon_id' => $team->hackathon_id,
                        'user_id' => $applicant->id,
                    ],
                    [
                        'team_id' => $team->id,
                        'participation_type' => 'team',
                        'is_eligible' => true,
                    ]
                );
            }

            NotificationService::notify(
                $applicant,
                'team_request_accepted',
                'Solicitação Aprovada',
                "Sua solicitação para ingressar na equipe '{$team->name}' foi aceita!"
            );
        });
    }

    /**
     * Recusa uma solicitação de entrada
     */
    public function declineRequest(TeamInvitation $request, User $actingUser): void
    {
        $team = $request->team;
        $this->authorizeLeadershipOrMembership($team, $actingUser);

        $request->update(['status' => 'declined']);
    }

    private function authorizeLeadershipOrMembership(Team $team, User $user): void
    {
        if ($team->leader_id) {
            if ($team->leader_id !== $user->id) {
                throw new Exception('Apenas o líder da equipe pode executar esta ação.');
            }
        } else {
            if (!$team->members()->where('users.id', $user->id)->exists()) {
                throw new Exception('Apenas integrantes da equipe podem executar esta ação.');
            }
        }
    }
}
