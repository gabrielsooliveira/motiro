<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamInvitation;
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
            'invitations.invitee',
            'registrations.hackathon',
            'projects.hackathon',
        ])->findOrFail($id);

        $this->authorizeMember($team);

        return Inertia::render('Organization/Dashboard/Team/Show', [
            'team' => $team,
            'isLeader' => $team->leader_id === Auth::id(),
            'hasLeader' => !empty($team->leader_id),
            'canElectLeader' => $team->members()->where('users.id', Auth::id())->exists(),
        ]);
    }

    /**
     * Create a new team
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hackathon_id' => 'nullable|uuid|exists:hackathons,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'area_of_focus' => 'nullable|string|max:255',
            'technologies' => 'nullable|array',
            'is_looking_for_members' => 'boolean',
            'set_as_leader' => 'nullable|boolean',
            'leader_id' => 'nullable|uuid|exists:users,id',
            'invite_emails' => 'nullable',
        ]);

        try {
            $team = $this->teamService->createTeam(Auth::user(), $validated);

            return redirect()->route('team.show', $team->id)->with('success', 'Equipe criada com sucesso!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['hackathon_id' => $e->getMessage()]);
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

            return redirect()->back()->with('success', 'Convite enviado com sucesso!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['email_or_username' => $e->getMessage()]);
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
     * Accept team invitation
     */
    public function acceptInvite(string $invitationId): RedirectResponse
    {
        $invitation = TeamInvitation::where('id', $invitationId)
            ->where('invitee_id', Auth::id())
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
     * Decline team invitation
     */
    public function declineInvite(string $invitationId): RedirectResponse
    {
        $invitation = TeamInvitation::where('id', $invitationId)
            ->where('invitee_id', Auth::id())
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

    private function authorizeLeader(Team $team): void
    {
        if ($team->leader_id !== Auth::id()) {
            abort(403, 'Apenas o líder da equipe pode executar esta ação.');
        }
    }

    private function authorizeLeaderOrMember(Team $team): void
    {
        $userId = Auth::id();
        if ($team->leader_id) {
            if ($team->leader_id !== $userId && !$team->members()->where('users.id', $userId)->exists()) {
                abort(403, 'Acesso não autorizado para esta equipe.');
            }
        } else {
            if (!$team->members()->where('users.id', $userId)->exists()) {
                abort(403, 'Acesso não autorizado para esta equipe.');
            }
        }
    }

    private function authorizeMember(Team $team): void
    {
        if (!$team->members()->where('users.id', Auth::id())->exists()) {
            abort(403, 'Acesso não autorizado para esta equipe.');
        }
    }
}
