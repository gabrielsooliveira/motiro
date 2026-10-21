<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StoreGuildRequest;
use App\Http\Requests\Participant\UpdateGuildRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TeamService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(
        protected TeamService $teamService
    ) {}

    /**
     * Show a team's management dashboard
     */
    public function show(string $id): Response
    {
        $team = Team::with([
            'members',
            'leader',
            'creator',
            'invitations.invitee',
            'invitations.inviter',
            'registrations.hackathon',
            'projects.hackathon',
        ])->findOrFail($id);

        $this->authorizeMember($team);

        $userId = Auth::id();

        $isGuild = request()->routeIs('participant.guilds.*') || request()->routeIs('guild.*') || empty($team->hackathon_id);
        $component = $isGuild ? 'Participant/Dashboard/Guild/Show' : 'Organization/Dashboard/Team/Show';

        return Inertia::render($component, [
            'team' => $team,
            'guild' => $team,
            'isLeader' => $team->isLeader(Auth::user()),
            'isCreator' => $team->isCreator(Auth::user()),
            'canManage' => $team->canManage(Auth::user()),
            'hasLeader' => !empty($team->leader_id),
            'canElectLeader' => $team->isMember(Auth::user()),
            'currentUserId' => $userId,
        ]);
    }

    /**
     * Create a new team / guild
     */
    public function store(StoreGuildRequest $request): RedirectResponse
    {
        try {
            $team = $this->teamService->createTeam(Auth::user(), $request->validated());

            return redirect()->route('team.show', $team->id)->with('success', 'Guilda criada com sucesso!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'hackathon_id' => $e->getMessage(),
                'name' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update guild details (name, description, area of focus, logo)
     */
    public function update(UpdateGuildRequest $request, string $id): RedirectResponse
    {
        $team = Team::findOrFail($id);
        $this->authorizeLeaderOrCreator($team);

        try {
            $this->teamService->updateGuild($team, $request->validated(), Auth::user());

            return redirect()->back()->with('success', 'Guilda atualizada com sucesso!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['update' => $e->getMessage()]);
        }
    }

    /**
     * Delete guild definitely
     */
    public function destroy(string $id): RedirectResponse
    {
        $team = Team::findOrFail($id);
        $this->authorizeLeaderOrCreator($team);

        try {
            $this->teamService->deleteGuild($team, Auth::user());

            return redirect()->route('participant.teams.index')->with('success', 'Guilda excluída com sucesso.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['destroy' => $e->getMessage()]);
        }
    }

    /**
     * Remove member from guild
     */
    public function removeMember(string $id, string $userId): RedirectResponse
    {
        $team = Team::findOrFail($id);
        $this->authorizeLeaderOrCreator($team);

        $member = User::findOrFail($userId);

        try {
            $this->teamService->removeMember($team, $member, Auth::user());

            return redirect()->back()->with('success', "Membro {$member->name} removido da guilda.");
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['member' => $e->getMessage()]);
        }
    }

    /**
     * Voluntary departure from guild
     */
    public function leave(string $id): RedirectResponse
    {
        $team = Team::findOrFail($id);

        try {
            $this->teamService->leaveGuild($team, Auth::user());

            return redirect()->route('participant.teams.index')->with('success', 'Você saiu da guilda com sucesso.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['leave' => $e->getMessage()]);
        }
    }

    /**
     * Elect or change team leader
     */
    public function electLeader(Request $request, string $id): RedirectResponse
    {
        $team = Team::findOrFail($id);
        $this->authorizeMember($team);

        $validated = $request->validate([
            'leader_id' => 'required|uuid|exists:users,id',
        ]);

        $newLeader = User::findOrFail($validated['leader_id']);

        try {
            $this->teamService->electLeader($team, $newLeader, Auth::user());

            return redirect()->back()->with('success', "{$newLeader->name} foi definido como líder da equipe!");
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['leader' => $e->getMessage()]);
        }
    }

    /**
     * Invite a user to join the team
     */
    public function invite(Request $request, string $id): RedirectResponse
    {
        $team = Team::findOrFail($id);
        $this->authorizeLeaderOrMember($team);

        $request->validate([
            'email_or_username' => 'required|string',
        ]);

        try {
            $this->teamService->inviteMember($team, Auth::user(), $request->input('email_or_username'));

            return redirect()->back()->with('success', 'Convite enviado com sucesso por e-mail!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['email_or_username' => $e->getMessage()]);
        }
    }

    /**
     * Resend an invitation
     */
    public function resendInvite(string $invitationId): RedirectResponse
    {
        $invitation = TeamInvitation::with('team')->findOrFail($invitationId);
        $this->authorizeLeaderOrCreator($invitation->team);

        try {
            $this->teamService->resendInvitation($invitation, Auth::user());

            return redirect()->back()->with('success', 'Convite reenviado com sucesso!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['invite' => $e->getMessage()]);
        }
    }

    /**
     * Cancel an invitation
     */
    public function cancelInvite(string $invitationId): RedirectResponse
    {
        $invitation = TeamInvitation::with('team')->findOrFail($invitationId);
        $this->authorizeLeaderOrCreator($invitation->team);

        try {
            $this->teamService->cancelInvitation($invitation, Auth::user());

            return redirect()->back()->with('success', 'Convite cancelado com sucesso.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['invite' => $e->getMessage()]);
        }
    }

    /**
     * Show public/authenticated invitation response page
     */
    public function showInvitation(Request $request, string $token): Response
    {
        $invitation = TeamInvitation::with(['team.members', 'team.leader', 'inviter'])
            ->where(function ($q) use ($token) {
                $q->where('token', $token)
                  ->orWhere('token', hash('sha256', $token));
            })
            ->first();

        $currentUser = Auth::user();

        if (!$invitation) {
            return Inertia::render('Site/GuildInvitation', [
                'invitation' => null,
                'error' => 'Convite não encontrado ou link inválido.',
                'status' => 'not_found',
            ]);
        }

        $isExpired = $invitation->isExpired();
        $isPending = $invitation->status === 'pending' && !$isExpired;

        $userEmailMatches = false;
        if ($currentUser) {
            $userEmailMatches = ($invitation->invitee_id && $invitation->invitee_id === $currentUser->id)
                || (!empty($invitation->email) && strtolower($invitation->email) === strtolower($currentUser->email));
        }

        return Inertia::render('Site/GuildInvitation', [
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'status' => $invitation->status,
                'token' => $token,
                'expires_at' => $invitation->expires_at?->toISOString(),
                'created_at' => $invitation->created_at?->toISOString(),
            ],
            'guild' => [
                'id' => $invitation->team->id,
                'name' => $invitation->team->name,
                'description' => $invitation->team->description,
                'area_of_focus' => $invitation->team->area_of_focus,
                'image_url' => $invitation->team->image_url,
                'members_count' => $invitation->team->members->count(),
            ],
            'inviter' => $invitation->inviter ? [
                'name' => $invitation->inviter->name,
                'username' => $invitation->inviter->username,
            ] : null,
            'isExpired' => $isExpired,
            'isPending' => $isPending,
            'userEmailMatches' => $userEmailMatches,
            'currentUser' => $currentUser ? [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
            ] : null,
        ]);
    }

    /**
     * Accept invitation via token
     */
    public function acceptInvitationByToken(string $token): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('participant.login', ['redirect' => route('guild.invitations.show', $token)])
                ->with('info', 'Faça login ou cadastre-se para aceitar o convite.');
        }

        try {
            $team = $this->teamService->acceptInvitationByToken($token, Auth::user());

            return redirect()->route('team.show', $team->id)->with('success', "Você agora faz parte da guilda '{$team->name}'!");
        } catch (Exception $e) {
            return redirect()->route('guild.invitations.show', $token)->withErrors(['invitation' => $e->getMessage()]);
        }
    }

    /**
     * Decline invitation via token
     */
    public function declineInvitationByToken(string $token): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('participant.login', ['redirect' => route('guild.invitations.show', $token)])
                ->with('info', 'Faça login para responder ao convite.');
        }

        try {
            $this->teamService->declineInvitationByToken($token, Auth::user());

            return redirect()->route('participant.dashboard')->with('success', 'Convite recusado com sucesso.');
        } catch (Exception $e) {
            return redirect()->route('guild.invitations.show', $token)->withErrors(['invitation' => $e->getMessage()]);
        }
    }

    /**
     * Solicitacao de entrada em equipe por participante
     */
    public function requestToJoin(string $id): RedirectResponse
    {
        $team = Team::findOrFail($id);

        try {
            $this->teamService->requestToJoin($team, Auth::user());

            return redirect()->back()->with('success', 'Solicitação de entrada enviada ao líder da equipe!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['team' => $e->getMessage()]);
        }
    }

    /**
     * Accept team invitation (from dashboard)
     */
    public function acceptInvite(string $invitationId): RedirectResponse
    {
        $invitation = TeamInvitation::where('id', $invitationId)
            ->where(function ($q) {
                $q->where('invitee_id', Auth::id())
                  ->orWhere('email', Auth::user()->email);
            })
            ->where('type', 'invite')
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            $this->teamService->acceptInvitation($invitation, Auth::user());

            return redirect()->back()->with('success', 'Você agora faz parte da equipe!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['team' => $e->getMessage()]);
        }
    }

    /**
     * Decline team invitation (from dashboard)
     */
    public function declineInvite(string $invitationId): RedirectResponse
    {
        $invitation = TeamInvitation::where('id', $invitationId)
            ->where(function ($q) {
                $q->where('invitee_id', Auth::id())
                  ->orWhere('email', Auth::user()->email);
            })
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            $this->teamService->declineInvitation($invitation, Auth::user());

            return redirect()->back()->with('success', 'Convite recusado.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['team' => $e->getMessage()]);
        }
    }

    /**
     * Lider aceita solicitacao de participante para entrar na equipe
     */
    public function acceptRequest(string $requestId): RedirectResponse
    {
        $req = TeamInvitation::where('id', $requestId)
            ->where('type', 'request')
            ->where('status', 'pending')
            ->firstOrFail();

        $team = $req->team;
        $this->authorizeLeaderOrMember($team);

        try {
            $this->teamService->acceptRequest($req, Auth::user());

            return redirect()->back()->with('success', 'Solicitação aceita! O participante agora integra sua equipe.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['team' => $e->getMessage()]);
        }
    }

    /**
     * Lider recusa solicitacao de participante para entrar na equipe
     */
    public function declineRequest(string $requestId): RedirectResponse
    {
        $req = TeamInvitation::where('id', $requestId)
            ->where('type', 'request')
            ->where('status', 'pending')
            ->firstOrFail();

        $team = $req->team;
        $this->authorizeLeaderOrMember($team);

        try {
            $this->teamService->declineRequest($req, Auth::user());

            return redirect()->back()->with('success', 'Solicitação de entrada recusada.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['team' => $e->getMessage()]);
        }
    }

    private function authorizeLeaderOrCreator(Team $team): void
    {
        if (!$team->canManage(Auth::user())) {
            abort(403, 'Apenas o líder ou criador da guilda pode executar esta ação.');
        }
    }

    private function authorizeLeaderOrMember(Team $team): void
    {
        $user = Auth::user();
        if (!$team->isMember($user) && !$team->canManage($user)) {
            abort(403, 'Acesso não autorizado para esta guilda.');
        }
    }

    private function authorizeMember(Team $team): void
    {
        $user = Auth::user();
        if (!$team->isMember($user) && !$team->canManage($user)) {
            abort(403, 'Acesso não autorizado para esta guilda.');
        }
    }
}
