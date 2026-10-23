<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StoreGuildRequest;
use App\Http\Requests\Participant\UpdateGuildRequest;
use App\Models\Guild;
use App\Models\GuildInvitation;
use App\Models\User;
use App\Services\GuildService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GuildController extends Controller
{
    public function __construct(
        protected GuildService $guildService
    ) {}

    /**
     * List all guilds
     */
    public function index(): Response
    {
        $guilds = Guild::all();

        return Inertia::render('Participant/Dashboard/Guild/Index', [
            'guilds' => $guilds,
        ]);
    }

    /**
     * Show a guild's management dashboard
     */
    public function show(string $id): Response
    {
        $guild = Guild::with([
            'members',
            'leader',
            'creator',
            'invitations.invitee',
            'invitations.inviter',
            'registrations.hackathon',
            'projects.hackathon',
        ])->findOrFail($id);

        $this->authorizeMember($guild);

        $userId = Auth::id();

        return Inertia::render('Participant/Dashboard/Guild/Show', [
            'team' => $guild,
            'guild' => $guild,
            'isLeader' => $guild->isLeader(Auth::user()),
            'isCreator' => $guild->isCreator(Auth::user()),
            'canManage' => $guild->canManage(Auth::user()),
            'hasLeader' => !empty($guild->leader_id),
            'canElectLeader' => $guild->isMember(Auth::user()),
            'currentUserId' => $userId,
        ]);
    }

    /**
     * Create a new guild
     */
    public function store(StoreGuildRequest $request): RedirectResponse
    {
        try {
            $guild = $this->guildService->createGuild(Auth::user(), $request->validated());

            $route = \Illuminate\Support\Facades\Route::has('participant.guilds.show')
                ? 'participant.guilds.show'
                : (\Illuminate\Support\Facades\Route::has('participant.guild.show') ? 'participant.guild.show' : 'guild.show');

            return redirect()->route($route, $guild->id)->with('success', 'Guild criada com sucesso!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'name' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update guild details (name, description, area of focus, logo)
     */
    public function update(UpdateGuildRequest $request, string $id): RedirectResponse
    {
        $guild = Guild::findOrFail($id);
        $this->authorizeLeaderOrCreator($guild);

        try {
            $this->guildService->updateGuild($guild, $request->validated(), Auth::user());

            return redirect()->back()->with('success', 'Guild atualizada com sucesso!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['update' => $e->getMessage()]);
        }
    }

    /**
     * Delete guild definitely
     */
    public function destroy(string $id): RedirectResponse
    {
        $guild = Guild::findOrFail($id);
        $this->authorizeLeaderOrCreator($guild);

        try {
            $this->guildService->deleteGuild($guild, Auth::user());

            $route = \Illuminate\Support\Facades\Route::has('participant.guilds.index')
                ? 'participant.guilds.index'
                : (\Illuminate\Support\Facades\Route::has('guild.participant.index') ? 'guild.participant.index' : 'participant.dashboard');
            return redirect()->route($route)->with('success', 'Guild excluída com sucesso.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['destroy' => $e->getMessage()]);
        }
    }

    /**
     * Remove member from guild
     */
    public function removeMember(string $id, string $userId): RedirectResponse
    {
        $guild = Guild::findOrFail($id);
        $this->authorizeLeaderOrCreator($guild);

        $member = User::findOrFail($userId);

        try {
            $this->guildService->removeMember($guild, $member, Auth::user());

            return redirect()->back()->with('success', "Membro {$member->name} removido da guild.");
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['member' => $e->getMessage()]);
        }
    }

    /**
     * Voluntary departure from guild
     */
    public function leave(string $id): RedirectResponse
    {
        $guild = Guild::findOrFail($id);

        try {
            $this->guildService->leaveGuild($guild, Auth::user());

            $route = \Illuminate\Support\Facades\Route::has('participant.guilds.index')
                ? 'participant.guilds.index'
                : (\Illuminate\Support\Facades\Route::has('guild.participant.index') ? 'guild.participant.index' : 'participant.dashboard');
            return redirect()->route($route)->with('success', 'Você saiu da guild com sucesso.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['leave' => $e->getMessage()]);
        }
    }

    /**
     * Elect or change guild/team leader
     */
    public function electLeader(Request $request, string $id): RedirectResponse
    {
        $guild = Guild::findOrFail($id);
        $this->authorizeMember($guild);

        $validated = $request->validate([
            'leader_id' => 'required|uuid|exists:users,id',
        ]);

        $newLeader = User::findOrFail($validated['leader_id']);

        try {
            $this->guildService->electLeader($guild, $newLeader, Auth::user());

            return redirect()->back()->with('success', "{$newLeader->name} foi definido como líder da equipe!");
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['leader' => $e->getMessage()]);
        }
    }

    /**
     * Invite a user to join the guild
     */
    public function invite(Request $request, string $id): RedirectResponse
    {
        $guild = Guild::findOrFail($id);
        $this->authorizeLeaderOrMember($guild);

        $request->validate([
            'email_or_username' => 'required|string',
        ]);

        try {
            $this->guildService->inviteMember($guild, Auth::user(), $request->input('email_or_username'));

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
        $invitation = GuildInvitation::with(['team', 'guild'])->findOrFail($invitationId);
        $guild = $invitation->guild ?? $invitation->team;
        $this->authorizeLeaderOrCreator($guild);

        try {
            $this->guildService->resendInvitation($invitation, Auth::user());

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
        $invitation = GuildInvitation::with(['team', 'guild'])->findOrFail($invitationId);
        $guild = $invitation->guild ?? $invitation->team;
        $this->authorizeLeaderOrCreator($guild);

        try {
            $this->guildService->cancelInvitation($invitation, Auth::user());

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
        $invitation = GuildInvitation::with(['team.members', 'team.leader', 'guild.members', 'guild.leader', 'inviter'])
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

        $guild = $invitation->guild ?? $invitation->team;
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
                'id' => $guild->id,
                'name' => $guild->name,
                'description' => $guild->description,
                'area_of_focus' => $guild->area_of_focus,
                'image_url' => $guild->image_url,
                'members_count' => $guild->members->count(),
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
            $guild = $this->guildService->acceptInvitationByToken($token, Auth::user());

            $route = \Illuminate\Support\Facades\Route::has('participant.guilds.show')
                ? 'participant.guilds.show'
                : (\Illuminate\Support\Facades\Route::has('team.show') ? 'team.show' : 'guild.show');
            return redirect()->route($route, $guild->id)->with('success', "Você agora faz parte da guild '{$guild->name}'!");
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
            $this->guildService->declineInvitationByToken($token, Auth::user());

            return redirect()->route('participant.dashboard')->with('success', 'Convite recusado com sucesso.');
        } catch (Exception $e) {
            return redirect()->route('guild.invitations.show', $token)->withErrors(['invitation' => $e->getMessage()]);
        }
    }

    /**
     * Solicitação de entrada em equipe por participante
     */
    public function requestToJoin(string $id): RedirectResponse
    {
        $guild = Guild::findOrFail($id);

        try {
            $this->guildService->requestToJoin($guild, Auth::user());

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
        $invitation = GuildInvitation::where('id', $invitationId)
            ->where(function ($q) {
                $q->where('invitee_id', Auth::id())
                  ->orWhere('email', Auth::user()->email);
            })
            ->where('type', 'invite')
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            $this->guildService->acceptInvitation($invitation, Auth::user());

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
        $invitation = GuildInvitation::where('id', $invitationId)
            ->where(function ($q) {
                $q->where('invitee_id', Auth::id())
                  ->orWhere('email', Auth::user()->email);
            })
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            $this->guildService->declineInvitation($invitation, Auth::user());

            return redirect()->back()->with('success', 'Convite recusado.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['team' => $e->getMessage()]);
        }
    }

    /**
     * Líder aceita solicitação de participante para entrar na equipe
     */
    public function acceptRequest(string $requestId): RedirectResponse
    {
        $req = GuildInvitation::where('id', $requestId)
            ->where('type', 'request')
            ->where('status', 'pending')
            ->firstOrFail();

        $guild = $req->guild ?? $req->team;
        $this->authorizeLeaderOrMember($guild);

        try {
            $this->guildService->acceptRequest($req, Auth::user());

            return redirect()->back()->with('success', 'Solicitação aceita! O participante agora integra sua equipe.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['team' => $e->getMessage()]);
        }
    }

    /**
     * Líder recusa solicitação de participante para entrar na equipe
     */
    public function declineRequest(string $requestId): RedirectResponse
    {
        $req = GuildInvitation::where('id', $requestId)
            ->where('type', 'request')
            ->where('status', 'pending')
            ->firstOrFail();

        $guild = $req->guild ?? $req->team;
        $this->authorizeLeaderOrMember($guild);

        try {
            $this->guildService->declineRequest($req, Auth::user());

            return redirect()->back()->with('success', 'Solicitação de entrada recusada.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['team' => $e->getMessage()]);
        }
    }

    public function revokeInvitation(string $id, string $invitationId): RedirectResponse
    {
        return $this->cancelInvite($invitationId);
    }

    public function acceptInvitation(string $id, string $invitationId): RedirectResponse
    {
        return $this->acceptInvite($invitationId);
    }

    public function rejectInvitation(string $id, string $invitationId): RedirectResponse
    {
        return $this->declineInvite($invitationId);
    }

    public function blockMember(string $id, string $userId): RedirectResponse
    {
        return $this->removeMember($id, $userId);
    }

    public function unblockMember(string $id, string $userId): RedirectResponse
    {
        return redirect()->back()->with('success', 'Membro desbloqueado com sucesso.');
    }

    public function getBlockedMembers(string $id)
    {
        return response()->json(['blocked' => []]);
    }

    private function authorizeLeaderOrCreator(Guild $guild): void
    {
        if (!$guild->canManage(Auth::user())) {
            abort(403, 'Apenas o líder ou criador da guild pode executar esta ação.');
        }
    }

    private function authorizeLeaderOrMember(Guild $guild): void
    {
        $user = Auth::user();
        if (!$guild->isMember($user) && !$guild->canManage($user)) {
            abort(403, 'Acesso não autorizado para esta guild.');
        }
    }

    private function authorizeMember(Guild $guild): void
    {
        $user = Auth::user();
        if (!$guild->isMember($user) && !$guild->canManage($user)) {
            abort(403, 'Acesso não autorizado para esta guild.');
        }
    }
}
