<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
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
        ]);

        $user = Auth::user();

        if (!empty($validated['hackathon_id'])) {
            $hackathon = \App\Models\Hackathon::findOrFail($validated['hackathon_id']);
            if (!$hackathon->allowsTeamRegistration()) {
                return redirect()->back()->withErrors(['hackathon_id' => 'Este Hackathon aceita apenas inscrições individuais.']);
            }
            if (!$hackathon->allow_team_creation) {
                return redirect()->back()->withErrors(['hackathon_id' => 'A criação de equipes por participantes está desabilitada para este Hackathon.']);
            }
            if (!$hackathon->isTeamFormationOpen()) {
                return redirect()->back()->withErrors(['hackathon_id' => 'O prazo limite para formação de equipes neste Hackathon foi encerrado.']);
            }
        }

        $slug = Str::slug($validated['name']);
        $count = 1;
        while (Team::where('slug', $slug)->exists()) {
            $slug = Str::slug($validated['name']) . '-' . $count;
            $count++;
        }
        $validated['slug'] = $slug;
        $validated['leader_id'] = $user->id;

        $team = Team::create($validated);

        // Add creator as leader member
        $team->members()->attach($user->id, [
            'id' => (string) Str::uuid(),
            'role' => 'leader',
            'joined_at' => now(),
        ]);

        // If created for a hackathon, sync creator registration
        if (!empty($validated['hackathon_id'])) {
            \App\Models\HackathonRegistration::updateOrCreate(
                [
                    'hackathon_id' => $validated['hackathon_id'],
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

        AuditService::log('team.create', $team, null, $team->toArray());

        return redirect()->route('team.show', $team->id)->with('success', 'Equipe criada com sucesso!');
    }

    /**
     * Invite a user to join the team
     */
    public function invite(Request $request, string $id): RedirectResponse
    {
        $team = Team::findOrFail($id);
        $this->authorizeLeader($team);

        if ($team->hackathon && !$team->hackathon->allow_team_invites) {
            return redirect()->back()->withErrors(['email_or_username' => 'Envio de convites para equipes não é permitido neste Hackathon.']);
        }

        if ($team->hackathon && !$team->hasAvailableSlots()) {
            return redirect()->back()->withErrors(['email_or_username' => 'A equipe já atingiu o limite máximo de integrantes.']);
        }

        $request->validate([
            'email_or_username' => 'required|string',
        ]);

        $query = $request->input('email_or_username');
        $user = User::where('email', $query)->orWhere('username', $query)->first();

        if (!$user) {
            return redirect()->back()->withErrors(['email_or_username' => 'Usuário não encontrado.']);
        }

        if ($team->members()->where('users.id', $user->id)->exists()) {
            return redirect()->back()->withErrors(['email_or_username' => 'Este usuário já faz parte da equipe.']);
        }

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'inviter_id' => Auth::id(),
            'invitee_id' => $user->id,
            'email' => $user->email,
            'status' => 'pending',
            'type' => 'invite',
            'token' => Str::random(40),
        ]);

        NotificationService::notify(
            $user,
            'team_invite',
            'Convite para Equipe',
            "Você foi convidado para integrar a equipe '{$team->name}'.",
            route('dashboard.index')
        );

        return redirect()->back()->with('success', 'Convite enviado com sucesso!');
    }

    /**
     * Solicitacao de entrada em equipe por participante
     */
    public function requestToJoin(string $id): RedirectResponse
    {
        $team = Team::findOrFail($id);
        $user = Auth::user();

        if ($team->members()->where('users.id', $user->id)->exists()) {
            return redirect()->back()->withErrors(['team' => 'Você já é integrante desta equipe.']);
        }

        if ($team->hackathon && !$team->hackathon->allow_team_requests) {
            return redirect()->back()->withErrors(['team' => 'Solicitações de entrada não são permitidas para este Hackathon.']);
        }

        if ($team->hackathon && !$team->hasAvailableSlots()) {
            return redirect()->back()->withErrors(['team' => 'Esta equipe já atingiu o limite de integrantes.']);
        }

        $existing = TeamInvitation::where('team_id', $team->id)
            ->where('inviter_id', $user->id)
            ->where('type', 'request')
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return redirect()->back()->withErrors(['team' => 'Você já possui uma solicitação pendente para esta equipe.']);
        }

        TeamInvitation::create([
            'team_id' => $team->id,
            'inviter_id' => $user->id,
            'invitee_id' => $team->leader_id,
            'email' => $user->email,
            'status' => 'pending',
            'type' => 'request',
            'token' => Str::random(40),
        ]);

        NotificationService::notify(
            $team->leader_id,
            'team_request',
            'Solicitação para entrar na Equipe',
            "{$user->name} solicitou entrada na sua equipe '{$team->name}'."
        );

        return redirect()->back()->with('success', 'Solicitação de entrada enviada ao líder da equipe!');
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

        $team = $invitation->team;

        if ($team->hackathon && !$team->hasAvailableSlots()) {
            return redirect()->back()->withErrors(['team' => 'A equipe já atingiu o limite máximo de integrantes.']);
        }

        $team->members()->syncWithoutDetaching([
            Auth::id() => [
                'id' => (string) Str::uuid(),
                'role' => 'member',
                'joined_at' => now(),
            ]
        ]);

        if ($team->hackathon_id) {
            \App\Models\HackathonRegistration::updateOrCreate(
                [
                    'hackathon_id' => $team->hackathon_id,
                    'user_id' => Auth::id(),
                ],
                [
                    'team_id' => $team->id,
                    'participation_type' => 'team',
                    'is_eligible' => true,
                    'notes' => null,
                ]
            );
        }

        $invitation->update(['status' => 'accepted']);

        NotificationService::notify(
            $team->leader_id,
            'invite_accepted',
            'Convite Aceito',
            Auth::user()->name . " aceitou o convite para a equipe '{$team->name}'."
        );

        return redirect()->back()->with('success', 'Você agora faz parte da equipe!');
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

        $invitation->update(['status' => 'declined']);

        return redirect()->back()->with('success', 'Convite recusado.');
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
        $this->authorizeLeader($team);

        $requestingUser = User::findOrFail($req->inviter_id);

        if ($team->hackathon && !$team->hasAvailableSlots()) {
            return redirect()->back()->withErrors(['team' => 'A equipe já atingiu o limite de integrantes.']);
        }

        $team->members()->syncWithoutDetaching([
            $requestingUser->id => [
                'id' => (string) Str::uuid(),
                'role' => 'member',
                'joined_at' => now(),
            ]
        ]);

        if ($team->hackathon_id) {
            \App\Models\HackathonRegistration::updateOrCreate(
                [
                    'hackathon_id' => $team->hackathon_id,
                    'user_id' => $requestingUser->id,
                ],
                [
                    'team_id' => $team->id,
                    'participation_type' => 'team',
                    'is_eligible' => true,
                    'notes' => null,
                ]
            );
        }

        $req->update(['status' => 'accepted']);

        NotificationService::notify(
            $requestingUser,
            'request_accepted',
            'Solicitação Aceita',
            "Sua solicitação para ingressar na equipe '{$team->name}' foi aceita!"
        );

        return redirect()->back()->with('success', 'Solicitação aceita! O participante agora integra sua equipe.');
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
        $this->authorizeLeader($team);

        $req->update(['status' => 'declined']);

        return redirect()->back()->with('success', 'Solicitação de entrada recusada.');
    }

    private function authorizeLeader(Team $team): void
    {
        if ($team->leader_id !== Auth::id()) {
            abort(403, 'Apenas o líder da equipe pode executar esta ação.');
        }
    }

    private function authorizeMember(Team $team): void
    {
        if (!$team->members()->where('users.id', Auth::id())->exists()) {
            abort(403, 'Acesso não autorizado para esta equipe.');
        }
    }
}
