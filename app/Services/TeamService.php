<?php

namespace App\Services;

use App\Models\Hackathon;
use App\Models\HackathonRegistration;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamService
{
    /**
     * Cria uma nova equipe aplicando regras de hackathon, liderança e unicidade.
     */
    public function createTeam(User $creator, array $data): Team
    {
        if (!empty($data['hackathon_id'])) {
            $hackathon = Hackathon::findOrFail($data['hackathon_id']);
            if (!$hackathon->allowsTeamRegistration()) {
                throw new Exception('Este Hackathon aceita apenas inscrições individuais.');
            }
            if (!$hackathon->allow_team_creation) {
                throw new Exception('A criação de equipes por participantes está desabilitada para este Hackathon.');
            }
            if (!$hackathon->isTeamFormationOpen()) {
                throw new Exception('O prazo limite para formação de equipes neste Hackathon foi encerrado.');
            }
        }

        $slug = Str::slug($data['name']);
        $count = 1;
        while (Team::where('slug', $slug)->exists()) {
            $slug = Str::slug($data['name']) . '-' . $count;
            $count++;
        }
        $data['slug'] = $slug;
        $data['leader_id'] = $creator->id;

        return DB::transaction(function () use ($creator, $data) {
            $team = Team::create($data);

            // Adiciona criador como membro líder
            $team->members()->attach($creator->id, [
                'id' => (string) Str::uuid(),
                'role' => 'leader',
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

            AuditService::log('team.create', $team, null, $team->toArray(), null, $data['hackathon_id'] ?? null);

            return $team;
        });
    }

    /**
     * Envia convite de membro para uma equipe
     */
    public function inviteMember(Team $team, User $sender, string $emailOrUserId): TeamInvitation
    {
        if ($team->hackathon && !$team->hackathon->allow_team_invites) {
            throw new Exception('Envio de convites para equipes não é permitido neste Hackathon.');
        }

        $invitee = filter_var($emailOrUserId, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $emailOrUserId)->first()
            : User::where('username', $emailOrUserId)->orWhere('id', $emailOrUserId)->first();

        if (!$invitee) {
            throw new Exception('Usuário não encontrado.');
        }

        if ($team->members()->where('users.id', $invitee->id)->exists()) {
            throw new Exception('Este usuário já faz parte da equipe.');
        }

        $existingInvite = TeamInvitation::where('team_id', $team->id)
            ->where('invitee_id', $invitee->id)
            ->where('status', 'pending')
            ->first();

        if ($existingInvite) {
            throw new Exception('Já existe um convite pendente para este participante.');
        }

        // Se a equipe possui hackathon vinculado, valida o limite máximo
        if ($team->hackathon_id) {
            $hackathon = Hackathon::find($team->hackathon_id);
            if ($hackathon && !$team->hasAvailableSlots()) {
                throw new Exception("A equipe já atingiu o limite máximo de {$hackathon->max_team_members} integrantes para este evento.");
            }
        }

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'inviter_id' => $sender->id,
            'invitee_id' => $invitee->id,
            'email' => $invitee->email,
            'token' => Str::random(40),
            'type' => 'invite',
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        NotificationService::notify(
            $invitee,
            'team_invite',
            'Convite para Equipe',
            "Você foi convidado por {$sender->name} para integrar a equipe '{$team->name}'."
        );

        AuditService::log('team.invite_sent', $team, null, $invitation->toArray(), null, $team->hackathon_id);

        return $invitation;
    }

    /**
     * Aceita um convite de participação na equipe
     */
    public function acceptInvitation(TeamInvitation $invitation, User $user): void
    {
        if ($invitation->invitee_id !== $user->id) {
            throw new Exception('Acesso não autorizado para responder a este convite.');
        }

        if ($invitation->status !== 'pending') {
            throw new Exception('Este convite já foi respondido ou expirou.');
        }

        $team = $invitation->team;

        if ($team->hackathon && !$team->hasAvailableSlots()) {
            throw new Exception('A equipe já atingiu o limite máximo de integrantes.');
        }

        DB::transaction(function () use ($invitation, $team, $user) {
            $invitation->update(['status' => 'accepted']);

            $team->members()->syncWithoutDetaching([
                $user->id => [
                    'id' => (string) Str::uuid(),
                    'role' => 'member',
                    'joined_at' => now(),
                ]
            ]);

            // Sincroniza inscrição no hackathon se a equipe estiver inscrita
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
        if ($invitation->invitee_id !== $user->id) {
            throw new Exception('Acesso não autorizado para responder a este convite.');
        }

        $invitation->update(['status' => 'declined']);
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

        if ($team->hackathon && !$team->hackathon->allow_team_requests) {
            throw new Exception('Solicitações de entrada não são permitidas para este Hackathon.');
        }

        if ($team->hackathon && !$team->hasAvailableSlots()) {
            throw new Exception('Esta equipe já atingiu o limite de integrantes.');
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
     * Aceita uma solicitação de entrada (ação do líder)
     */
    public function acceptRequest(TeamInvitation $request, User $leader): void
    {
        $team = $request->team;
        $this->authorizeLeadership($team, $leader);

        if ($request->status !== 'pending') {
            throw new Exception('Esta solicitação já foi respondida ou expirou.');
        }

        $applicant = User::findOrFail($request->inviter_id);

        if ($team->hackathon && !$team->hasAvailableSlots()) {
            throw new Exception('A equipe já atingiu o limite de integrantes.');
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
    public function declineRequest(TeamInvitation $request, User $leader): void
    {
        $team = $request->team;
        $this->authorizeLeadership($team, $leader);

        $request->update(['status' => 'declined']);
    }

    private function authorizeLeadership(Team $team, User $user): void
    {
        if ($team->leader_id !== $user->id) {
            throw new Exception('Apenas o líder da equipe pode executar esta ação.');
        }
    }
}
