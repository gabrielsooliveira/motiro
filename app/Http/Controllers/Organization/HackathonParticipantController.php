<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Hackathon;
use App\Models\HackathonGroup;
use App\Models\HackathonRegistration;
use App\Models\Team;
use App\Models\User;
use App\Services\HackathonGroupService;
use App\Services\RegistrationService;
use App\Services\TeamService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class HackathonParticipantController extends Controller
{
    public function __construct(
        protected RegistrationService $registrationService,
        protected HackathonGroupService $hackathonGroupService,
        protected TeamService $teamService
    ) {}

    /**
     * Adiciona um participante inscrito individualmente a uma equipe / grupo
     */
    public function assignToTeam(Request $request, string $hackathonId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeAdmin($hackathon);

        $validated = $request->validate([
            'registration_id' => 'required|uuid|exists:hackathon_registrations,id',
            'team_id' => 'nullable|uuid',
            'group_id' => 'nullable|uuid',
        ]);

        $registration = HackathonRegistration::with(['user', 'hackathon'])->findOrFail($validated['registration_id']);
        if ($registration->hackathon_id !== $hackathon->id) {
            abort(403, 'A inscrição selecionada não pertence a este Hackathon.');
        }

        $targetId = $validated['group_id'] ?? $validated['team_id'] ?? null;
        if (!$targetId) {
            return redirect()->back()->withErrors(['participant_management' => 'Identificador da equipe ou grupo não fornecido.']);
        }

        $group = HackathonGroup::find($targetId);
        if ($group && $group->hackathon_id !== $hackathon->id) {
            abort(403, 'A equipe selecionada pertence a outro Hackathon.');
        }

        $team = Team::find($targetId);
        if ($team && $team->hackathon_id && $team->hackathon_id !== $hackathon->id) {
            abort(403, 'A equipe selecionada pertence a outro Hackathon.');
        }

        try {
            if ($group) {
                $this->hackathonGroupService->assignParticipantToGroup($registration, $group, Auth::user());
                return redirect()->back()->with('success', "Participante {$registration->user->name} vinculado com sucesso ao grupo {$group->name}!");
            }

            if ($team) {
                $this->registrationService->assignParticipantToTeam($registration, $team, Auth::user());
                return redirect()->back()->with('success', "Participante {$registration->user->name} vinculado com sucesso à equipe {$team->name}!");
            }

            return redirect()->back()->withErrors(['participant_management' => 'Equipe ou grupo não encontrado.']);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['participant_management' => $e->getMessage()]);
        }
    }

    /**
     * Transfere participante entre duas equipes / grupos do mesmo hackathon
     */
    public function transferTeam(Request $request, string $hackathonId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeAdmin($hackathon);

        $validated = $request->validate([
            'registration_id' => 'required|uuid|exists:hackathon_registrations,id',
            'target_team_id' => 'nullable|uuid',
            'target_group_id' => 'nullable|uuid',
        ]);

        $registration = HackathonRegistration::with(['user', 'team', 'group', 'hackathon'])->findOrFail($validated['registration_id']);
        if ($registration->hackathon_id !== $hackathon->id) {
            abort(403, 'A inscrição selecionada não pertence a este Hackathon.');
        }

        $targetId = $validated['target_group_id'] ?? $validated['target_team_id'] ?? null;
        if (!$targetId) {
            return redirect()->back()->withErrors(['participant_management' => 'Identificador do grupo ou equipe destino não informado.']);
        }

        $targetGroup = HackathonGroup::find($targetId);
        if ($targetGroup && $targetGroup->hackathon_id !== $hackathon->id) {
            abort(403, 'A equipe de destino pertence a outro Hackathon.');
        }

        $targetTeam = Team::find($targetId);
        if ($targetTeam && $targetTeam->hackathon_id && $targetTeam->hackathon_id !== $hackathon->id) {
            abort(403, 'A equipe de destino pertence a outro Hackathon.');
        }

        try {
            if ($targetGroup) {
                $this->hackathonGroupService->transferParticipant($registration, $targetGroup, Auth::user());
                return redirect()->back()->with('success', "Participante {$registration->user->name} transferido para o grupo {$targetGroup->name}!");
            }

            if ($targetTeam) {
                $this->registrationService->transferParticipant($registration, $targetTeam, Auth::user());
                return redirect()->back()->with('success', "Participante {$registration->user->name} transferido para {$targetTeam->name}!");
            }

            return redirect()->back()->withErrors(['participant_management' => 'Equipe ou grupo não encontrado.']);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['participant_management' => $e->getMessage()]);
        }
    }

    /**
     * Remove participante da equipe / grupo atual, retornando para individual
     */
    public function removeFromTeam(Request $request, string $hackathonId, string $registrationId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeAdmin($hackathon);

        $registration = HackathonRegistration::with(['user', 'team', 'group', 'hackathon'])->findOrFail($registrationId);
        if ($registration->hackathon_id !== $hackathon->id) {
            abort(403, 'A inscrição selecionada não pertence a este Hackathon.');
        }

        try {
            if ($registration->hackathon_group_id) {
                $this->hackathonGroupService->removeParticipantFromGroup($registration, Auth::user());
            } else {
                $this->registrationService->removeParticipantFromTeam($registration, Auth::user());
            }
            return redirect()->back()->with('success', "Participante {$registration->user->name} removido do grupo com sucesso!");
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['participant_management' => $e->getMessage()]);
        }
    }

    /**
     * Criação de equipe / grupo pela organização em nome dos participantes
     */
    public function createTeam(Request $request, string $hackathonId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeAdmin($hackathon);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'leader_id' => 'nullable|uuid|exists:users,id',
            'description' => 'nullable|string',
            'area_of_focus' => 'nullable|string|max:255',
            'member_registration_ids' => 'required|array|min:1',
            'member_registration_ids.*' => 'uuid|exists:hackathon_registrations,id',
        ]);

        $registrations = HackathonRegistration::where('hackathon_id', $hackathon->id)
            ->whereIn('id', $validated['member_registration_ids'])
            ->get();

        $memberUserIds = $registrations->pluck('user_id')->toArray();
        $leaderId = $validated['leader_id'] ?? null;

        if ($leaderId && !in_array($leaderId, $memberUserIds)) {
            $memberUserIds[] = $leaderId;
        }

        try {
            $team = $this->registrationService->createTeamForHackathon(
                $hackathon,
                Auth::user(),
                [
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'area_of_focus' => $validated['area_of_focus'] ?? null,
                ],
                Auth::user(),
                $memberUserIds,
                $leaderId
            );

            return redirect()->back()->with('success', "Equipe {$team->name} criada com sucesso pela organização!");
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['participant_management' => $e->getMessage()]);
        }
    }

    /**
     * Define ou altera o líder de uma equipe / grupo pela organização
     */
    public function setLeader(Request $request, string $hackathonId, string $teamId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeAdmin($hackathon);

        $validated = $request->validate([
            'leader_id' => 'nullable|uuid|exists:users,id',
        ]);

        $newLeader = !empty($validated['leader_id']) ? User::findOrFail($validated['leader_id']) : null;

        $group = HackathonGroup::find($teamId);
        if ($group && $group->hackathon_id !== $hackathon->id) {
            abort(403, 'A equipe selecionada pertence a outro Hackathon.');
        }

        $team = Team::find($teamId);
        if ($team && $team->hackathon_id && $team->hackathon_id !== $hackathon->id) {
            abort(403, 'A equipe selecionada pertence a outro Hackathon.');
        }

        try {
            if ($group) {
                $this->hackathonGroupService->electLeader($group, $newLeader, Auth::user());
            }

            if ($team) {
                $this->teamService->electLeader($team, $newLeader, Auth::user());
            }

            $name = $group?->name ?? $team?->name ?? 'da equipe';
            $msg = $newLeader
                ? "{$newLeader->name} foi definido como líder de {$name}!"
                : "A liderança de {$name} foi deixada em aberto.";

            return redirect()->back()->with('success', $msg);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['participant_management' => $e->getMessage()]);
        }
    }

    private function authorizeAdmin(Hackathon $hackathon): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if (!\Illuminate\Support\Facades\Gate::allows('manageParticipants', $hackathon)) {
            abort(403, 'Acesso não autorizado para gerenciar participantes deste Hackathon.');
        }
    }
}
