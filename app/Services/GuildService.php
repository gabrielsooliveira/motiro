<?php

namespace App\Services;

use App\Mail\GuildInvitationMail;
use App\Models\Guild;
use App\Models\GuildInvitation;
use App\Models\GuildMember;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GuildService
{
    /**
     * Cria uma nova guild permanente e vincula o criador como líder.
     */
    public function createGuild(User $creator, array $data): Guild
    {
        $name = trim($data['name']);
        $slug = $this->generateUniqueSlug($name);

        $logoPath = null;
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $logoPath = $data['image']->store('guilds/logos', 'public');
        } elseif (!empty($data['logo']) && is_string($data['logo'])) {
            $logoPath = $data['logo'];
        }

        $technologies = isset($data['technologies'])
            ? (is_array($data['technologies']) ? $data['technologies'] : json_decode($data['technologies'], true))
            : [];

        $links = isset($data['links'])
            ? (is_array($data['links']) ? $data['links'] : json_decode($data['links'], true))
            : [];

        $leaderId = !empty($data['leader_id']) ? $data['leader_id'] : $creator->id;

        return DB::transaction(function () use ($creator, $data, $name, $slug, $logoPath, $technologies, $links, $leaderId) {
            $guild = Guild::create([
                'creator_id' => $creator->id,
                'leader_id' => $leaderId,
                'name' => $name,
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'logo' => $logoPath,
                'area_of_focus' => $data['area_of_focus'] ?? null,
                'technologies' => $technologies,
                'links' => $links,
                'is_looking_for_members' => $data['is_looking_for_members'] ?? true,
            ]);

            // Vincula o criador como membro
            $creatorRole = ($leaderId === $creator->id) ? 'leader' : 'member';
            $guild->members()->attach($creator->id, [
                'id' => (string) Str::uuid(),
                'role' => $creatorRole,
                'joined_at' => now(),
            ]);

            // Se o líder inicial for outro usuário especificado, vincula-o também
            if ($leaderId !== $creator->id) {
                $guild->members()->syncWithoutDetaching([
                    $leaderId => [
                        'id' => (string) Str::uuid(),
                        'role' => 'leader',
                        'joined_at' => now(),
                    ]
                ]);
            }

            // Processa lista de convites iniciais por e-mail, se informada
            if (!empty($data['invite_emails'])) {
                $emails = is_array($data['invite_emails'])
                    ? $data['invite_emails']
                    : preg_split('/[,;\s]+/', $data['invite_emails']);

                foreach ($emails as $email) {
                    $cleanEmail = trim($email);
                    if (filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $this->inviteMember($guild, $creator, $cleanEmail);
                        } catch (Exception $e) {
                            Log::warning("Falha ao enviar convite inicial de guild para {$cleanEmail}: " . $e->getMessage());
                        }
                    }
                }
            }

            AuditService::log('guild.create', $guild, null, $guild->toArray());

            return $guild;
        });
    }

    /**
     * Atualiza dados de uma guild existente.
     */
    public function updateGuild(Guild $guild, array $data, User $actor): Guild
    {
        $this->authorizeLeaderOrCreator($guild, $actor);

        $updateData = [];

        if (isset($data['name']) && trim($data['name']) !== $guild->name) {
            $updateData['name'] = trim($data['name']);
            $updateData['slug'] = $this->generateUniqueSlug($updateData['name'], $guild->id);
        }

        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }

        if (array_key_exists('area_of_focus', $data)) {
            $updateData['area_of_focus'] = $data['area_of_focus'];
        }

        if (isset($data['technologies'])) {
            $updateData['technologies'] = is_array($data['technologies'])
                ? $data['technologies']
                : json_decode($data['technologies'], true);
        }

        if (isset($data['links'])) {
            $updateData['links'] = is_array($data['links'])
                ? $data['links']
                : json_decode($data['links'], true);
        }

        if (array_key_exists('is_looking_for_members', $data)) {
            $updateData['is_looking_for_members'] = (bool) $data['is_looking_for_members'];
        }

        // Remoção da imagem atual
        if (!empty($data['remove_image']) && $guild->logo) {
            Storage::disk('public')->delete($guild->logo);
            $updateData['logo'] = null;
        }

        // Upload de nova imagem
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            if ($guild->logo) {
                Storage::disk('public')->delete($guild->logo);
            }
            $updateData['logo'] = $data['image']->store('guilds/logos', 'public');
        } elseif (isset($data['logo']) && is_string($data['logo'])) {
            $updateData['logo'] = $data['logo'];
        }

        $oldValues = $guild->only(array_keys($updateData));
        $guild->update($updateData);

        AuditService::log('guild.update', $guild, $oldValues, $updateData);

        return $guild->fresh();
    }

    /**
     * Exclui uma guild (soft delete) e cancela seus convites pendentes.
     */
    public function deleteGuild(Guild $guild, User $actor): void
    {
        $this->authorizeLeaderOrCreator($guild, $actor);

        DB::transaction(function () use ($guild) {
            // Cancela convites pendentes
            $guild->invitations()->where('status', 'pending')->update(['status' => 'cancelled']);

            // Remove vínculos de membros
            $guild->members()->detach();

            // Exclui a guild
            $guild->delete();

            AuditService::log('guild.delete', $guild, ['name' => $guild->name], null);
        });
    }

    /**
     * Remove um membro da guild (ação de líder/criador).
     */
    public function removeMember(Guild $guild, User $member, User $actor): void
    {
        $this->authorizeLeaderOrCreator($guild, $actor);

        if ($member->id === $guild->leader_id) {
            throw new Exception('O líder da guild não pode ser removido. Transfira a liderança primeiro.');
        }

        DB::transaction(function () use ($guild, $member) {
            $guild->members()->detach($member->id);

            NotificationService::notify(
                $member,
                'guild_member_removed',
                'Removido da Guild',
                "Você foi removido da guild '{$guild->name}'."
            );

            AuditService::log('guild.member_removed', $guild, null, ['user_id' => $member->id]);
        });
    }

    /**
     * Saída voluntária de um membro da guild.
     */
    public function leaveGuild(Guild $guild, User $user): void
    {
        if (!$guild->isMember($user)) {
            throw new Exception('Você não faz parte desta guild.');
        }

        DB::transaction(function () use ($guild, $user) {
            // Se o usuário for o líder atual, transfere para outro membro mais antigo
            if ($guild->leader_id === $user->id) {
                $nextLeader = $guild->members()
                    ->where('users.id', '!=', $user->id)
                    ->orderBy('guild_members.joined_at')
                    ->first();

                if ($nextLeader) {
                    $guild->update(['leader_id' => $nextLeader->id]);
                    $guild->members()->updateExistingPivot($nextLeader->id, ['role' => 'leader']);

                    NotificationService::notify(
                        $nextLeader,
                        'guild_promoted_leader',
                        'Liderança da Guild',
                        "Você agora é o novo líder da guild '{$guild->name}'."
                    );
                } else {
                    $guild->update(['leader_id' => null]);
                }
            }

            $guild->members()->detach($user->id);

            AuditService::log('guild.leave', $guild, null, ['user_id' => $user->id]);
        });
    }

    /**
     * Elege ou altera o líder da guild.
     */
    public function electLeader(Guild $guild, User $newLeader, User $actor): Guild
    {
        $this->authorizeMember($guild, $actor);

        if (!$guild->isMember($newLeader)) {
            throw new Exception('O novo líder deve ser um integrante ativo da guild.');
        }

        $oldLeaderId = $guild->leader_id;

        DB::transaction(function () use ($guild, $newLeader, $actor, $oldLeaderId) {
            if ($oldLeaderId && $guild->isMember(User::find($oldLeaderId))) {
                $guild->members()->updateExistingPivot($oldLeaderId, ['role' => 'member']);
            }

            $guild->update(['leader_id' => $newLeader->id]);
            $guild->members()->updateExistingPivot($newLeader->id, ['role' => 'leader']);

            NotificationService::notify(
                $newLeader,
                'guild_leader_elected',
                'Novo Líder da Guild',
                "Você foi eleito líder da guild '{$guild->name}' por {$actor->name}."
            );

            AuditService::log('guild.leader_elected', $guild, ['old_leader_id' => $oldLeaderId], ['new_leader_id' => $newLeader->id]);
        });

        return $guild->fresh();
    }

    /**
     * Envia convite de participação na guild (por e-mail, username ou ID).
     */
    public function inviteMember(Guild $guild, User $inviter, string $emailOrUsername): GuildInvitation
    {
        $cleanTarget = trim($emailOrUsername);
        $invitee = null;
        $email = null;

        if (filter_var($cleanTarget, FILTER_VALIDATE_EMAIL)) {
            $email = strtolower($cleanTarget);
            $invitee = User::where('email', $email)->first();
        } else {
            $invitee = User::where('username', $cleanTarget)
                ->orWhere('id', $cleanTarget)
                ->first();

            if (!$invitee) {
                throw new Exception("Usuário '{$cleanTarget}' não foi encontrado.");
            }
            $email = $invitee->email;
        }

        // Validação se o convidado já integra a guild
        if ($invitee && $guild->isMember($invitee)) {
            throw new Exception('Este participante já faz parte da guild.');
        }

        // Validação de convite pendente duplicado
        $existing = GuildInvitation::where('guild_id', $guild->id)
            ->where(function ($q) use ($invitee, $email) {
                if ($invitee) {
                    $q->where('invitee_id', $invitee->id);
                }
                if ($email) {
                    $q->orWhere('email', $email);
                }
            })
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            throw new Exception('Já existe um convite pendente para este participante.');
        }

        $token = Str::random(40);

        $invitation = GuildInvitation::create([
            'guild_id' => $guild->id,
            'team_id' => $guild->id,
            'inviter_id' => $inviter->id,
            'invitee_id' => $invitee?->id,
            'email' => $email,
            'type' => 'invite',
            'status' => 'pending',
            'token' => $token,
            'expires_at' => now()->addDays(7),
        ]);

        if ($invitee) {
            NotificationService::notify(
                $invitee,
                'guild_invite',
                'Convite para Guild',
                "Você foi convidado por {$inviter->name} para integrar a guild '{$guild->name}'.",
                route('guild.invitations.show', $token)
            );
        }

        try {
            Mail::to($email)->send(new GuildInvitationMail($guild, $invitation, $inviter, $token));
        } catch (Exception $e) {
            Log::warning("Falha no disparo de e-mail de convite para {$email}: " . $e->getMessage());
        }

        AuditService::log('guild.invite_sent', $guild, null, [
            'invitation_id' => $invitation->id,
            'email' => $email,
            'invitee_id' => $invitee?->id,
        ]);

        return $invitation;
    }

    /**
     * Reenvia um convite de guild gerando novo token e estendendo a validade.
     */
    public function resendInvitation(GuildInvitation $invitation, User $actor): GuildInvitation
    {
        $guild = $invitation->guild ?? $invitation->team;
        $this->authorizeLeaderOrCreator($guild, $actor);

        if ($invitation->status !== 'pending') {
            throw new Exception('Apenas convites pendentes podem ser reenviados.');
        }

        $token = Str::random(40);
        $invitation->update([
            'token' => $token,
            'expires_at' => now()->addDays(7),
        ]);

        try {
            Mail::to($invitation->email)->send(new GuildInvitationMail($guild, $invitation, $actor, $token));
        } catch (Exception $e) {
            Log::warning("Falha ao reenviar e-mail de convite para {$invitation->email}: " . $e->getMessage());
        }

        AuditService::log('guild.invite_resent', $guild, null, ['invitation_id' => $invitation->id]);

        return $invitation;
    }

    /**
     * Cancela um convite de guild.
     */
    public function cancelInvitation(GuildInvitation $invitation, User $actor): void
    {
        $guild = $invitation->guild ?? $invitation->team;
        $this->authorizeLeaderOrCreator($guild, $actor);

        $invitation->update(['status' => 'cancelled']);

        AuditService::log('guild.invite_cancelled', $guild, null, ['invitation_id' => $invitation->id]);
    }

    /**
     * Aceita um convite de guild através de token seguro de URL.
     */
    public function acceptInvitationByToken(string $token, User $user): Guild
    {
        $invitation = GuildInvitation::with('guild')
            ->where(function ($q) use ($token) {
                $q->where('token', $token)
                  ->orWhere('token', hash('sha256', $token));
            })
            ->first();

        if (!$invitation) {
            throw new Exception('Convite não encontrado ou link inválido.');
        }

        if ($invitation->isExpired()) {
            throw new Exception('Este convite expirou.');
        }

        if ($invitation->status !== 'pending') {
            throw new Exception('Este convite já foi utilizado ou cancelado.');
        }

        // Validação de correspondência de e-mail se o convite foi endereçado especificamente
        if (!empty($invitation->email) && strtolower($invitation->email) !== strtolower($user->email)) {
            if ($invitation->invitee_id && $invitation->invitee_id !== $user->id) {
                throw new Exception('Este convite foi destinado a outro endereço de e-mail.');
            }
        }

        $guild = $invitation->guild ?? $invitation->team;

        DB::transaction(function () use ($invitation, $guild, $user) {
            $invitation->update([
                'status' => 'accepted',
                'invitee_id' => $user->id,
            ]);

            $guild->members()->syncWithoutDetaching([
                $user->id => [
                    'id' => (string) Str::uuid(),
                    'role' => 'member',
                    'joined_at' => now(),
                ]
            ]);

            // Cancela outros convites pendentes deste usuário para a mesma guild
            GuildInvitation::where('guild_id', $guild->id)
                ->where('id', '!=', $invitation->id)
                ->where(function ($q) use ($user) {
                    $q->where('invitee_id', $user->id)
                      ->orWhere('email', $user->email);
                })
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            if ($invitation->inviter) {
                NotificationService::notify(
                    $invitation->inviter,
                    'guild_invite_accepted',
                    'Convite Aceito',
                    "{$user->name} aceitou seu convite e agora faz parte da guild '{$guild->name}'."
                );
            }

            AuditService::log('guild.invite_accepted', $guild, null, ['user_id' => $user->id]);
        });

        return $guild;
    }

    /**
     * Recusa um convite de guild através de token seguro.
     */
    public function declineInvitationByToken(string $token, User $user): void
    {
        $invitation = GuildInvitation::where(function ($q) use ($token) {
            $q->where('token', $token)
              ->orWhere('token', hash('sha256', $token));
        })->first();

        if (!$invitation) {
            throw new Exception('Convite não encontrado.');
        }

        $invitation->update([
            'status' => 'declined',
            'invitee_id' => $user->id,
        ]);

        AuditService::log('guild.invite_declined', $invitation->guild ?? $invitation->team, null, ['user_id' => $user->id]);
    }

    /**
     * Solicitação espontânea de um participante para ingressar em uma guild.
     */
    public function requestToJoin(Guild $guild, User $user, ?string $message = null): GuildInvitation
    {
        if ($guild->isMember($user)) {
            throw new Exception('Você já é integrante desta guild.');
        }

        if (!$guild->is_looking_for_members) {
            throw new Exception('Esta guild não está aceitando novas solicitações no momento.');
        }

        $existing = GuildInvitation::where('guild_id', $guild->id)
            ->where('inviter_id', $user->id)
            ->where('type', 'request')
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            throw new Exception('Você já possui uma solicitação pendente para esta guild.');
        }

        $request = GuildInvitation::create([
            'guild_id' => $guild->id,
            'team_id' => $guild->id,
            'inviter_id' => $user->id,
            'invitee_id' => $guild->leader_id,
            'email' => $user->email,
            'type' => 'request',
            'status' => 'pending',
            'token' => Str::random(40),
            'expires_at' => now()->addDays(7),
        ]);

        if ($guild->leader) {
            NotificationService::notify(
                $guild->leader,
                'guild_join_request',
                'Nova Solicitação de Ingresso',
                "{$user->name} solicitou entrada na guild '{$guild->name}'."
            );
        }

        AuditService::log('guild.join_requested', $guild, null, ['user_id' => $user->id]);

        return $request;
    }

    /**
     * Aceita convite direto da guild (a partir do painel do participante).
     */
    public function acceptInvitation(GuildInvitation $invitation, User $user): Guild
    {
        $this->authorizeInvitee($invitation, $user);

        if ($invitation->status !== 'pending') {
            throw new Exception('Este convite já foi respondido ou expirou.');
        }

        $guild = $invitation->guild ?? $invitation->team;

        DB::transaction(function () use ($invitation, $guild, $user) {
            $invitation->update([
                'status' => 'accepted',
                'invitee_id' => $user->id,
            ]);

            $guild->members()->syncWithoutDetaching([
                $user->id => [
                    'id' => (string) Str::uuid(),
                    'role' => 'member',
                    'joined_at' => now(),
                ]
            ]);

            AuditService::log('guild.invite_accepted', $guild, null, ['user_id' => $user->id]);
        });

        return $guild;
    }

    /**
     * Recusa convite direto da guild.
     */
    public function declineInvitation(GuildInvitation $invitation, User $user): void
    {
        $this->authorizeInvitee($invitation, $user);

        $invitation->update([
            'status' => 'declined',
            'invitee_id' => $user->id,
        ]);

        AuditService::log('guild.invite_declined', $invitation->guild ?? $invitation->team, null, ['user_id' => $user->id]);
    }

    /**
     * Líder aceita a solicitação de entrada de um participante.
     */
    public function acceptRequest(GuildInvitation $request, User $actor): Guild
    {
        $guild = $request->guild ?? $request->team;
        $this->authorizeLeaderOrCreator($guild, $actor);

        if ($request->status !== 'pending' || $request->type !== 'request') {
            throw new Exception('Esta solicitação já foi respondida ou não está mais pendente.');
        }

        $applicant = User::findOrFail($request->inviter_id);

        DB::transaction(function () use ($request, $guild, $applicant) {
            $request->update(['status' => 'accepted']);

            $guild->members()->syncWithoutDetaching([
                $applicant->id => [
                    'id' => (string) Str::uuid(),
                    'role' => 'member',
                    'joined_at' => now(),
                ]
            ]);

            NotificationService::notify(
                $applicant,
                'guild_request_accepted',
                'Solicitação Aprovada',
                "Sua solicitação para ingressar na guild '{$guild->name}' foi aceita!"
            );

            AuditService::log('guild.request_accepted', $guild, null, ['applicant_id' => $applicant->id]);
        });

        return $guild;
    }

    /**
     * Líder recusa a solicitação de entrada de um participante.
     */
    public function declineRequest(GuildInvitation $request, User $actor): void
    {
        $guild = $request->guild ?? $request->team;
        $this->authorizeLeaderOrCreator($guild, $actor);

        $request->update(['status' => 'declined']);

        NotificationService::notify(
            $request->inviter,
            'guild_request_declined',
            'Solicitação Não Aprovada',
            "Sua solicitação para ingressar na guild '{$guild->name}' foi recusada."
        );

        AuditService::log('guild.request_declined', $guild, null, ['applicant_id' => $request->inviter_id]);
    }

    /**
     * Auxiliar de verificação de permissão de liderança/criação.
     */
    private function authorizeLeaderOrCreator(Guild $guild, User $actor): void
    {
        if (!$guild->canManage($actor)) {
            throw new Exception('Apenas o líder ou criador da guild pode executar esta ação.');
        }
    }

    /**
     * Auxiliar de verificação de pertencimento à guild.
     */
    private function authorizeMember(Guild $guild, User $actor): void
    {
        if (!$guild->isMember($actor) && !$guild->canManage($actor)) {
            throw new Exception('Acesso não autorizado para esta guild.');
        }
    }

    /**
     * Auxiliar para verificar se o usuário é o destinatário do convite.
     */
    private function authorizeInvitee(GuildInvitation $invitation, User $user): void
    {
        $matchesId = $invitation->invitee_id && $invitation->invitee_id === $user->id;
        $matchesEmail = !empty($invitation->email) && strtolower($invitation->email) === strtolower($user->email);

        if (!$matchesId && !$matchesEmail) {
            throw new Exception('Acesso não autorizado para responder a este convite.');
        }
    }

    /**
     * Gera um slug único no banco de dados.
     */
    private function generateUniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'guild-' . Str::random(6);
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            Guild::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
