<?php

namespace App\Http\Controllers;

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

        return Inertia::render('Dashboard/Team/Show', [
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'area_of_focus' => 'nullable|string|max:255',
            'technologies' => 'nullable|array',
            'is_looking_for_members' => 'boolean',
        ]);

        $user = Auth::user();

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
     * Accept team invitation
     */
    public function acceptInvite(string $invitationId): RedirectResponse
    {
        $invitation = TeamInvitation::where('id', $invitationId)
            ->where('invitee_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $team = $invitation->team;

        $team->members()->attach(Auth::id(), [
            'id' => (string) Str::uuid(),
            'role' => 'member',
            'joined_at' => now(),
        ]);

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
