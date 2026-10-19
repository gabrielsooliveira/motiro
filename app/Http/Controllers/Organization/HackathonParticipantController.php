<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Hackathon;
use App\Models\HackathonRegistration;
use App\Models\Team;
use App\Models\User;
use App\Services\RegistrationService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HackathonParticipantController extends Controller
{
    protected RegistrationService $registrationService;

    public function __construct(RegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /**
     * Adiciona um participante inscrito individualmente a uma equipe
     */
    public function assignToTeam(Request $request, string $hackathonId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeAdmin($hackathon);

        $validated = $request->validate([
            'registration_id' => 'required|uuid|exists:hackathon_registrations,id',
            'team_id' => 'required|uuid|exists:teams,id',
        ]);

        $registration = HackathonRegistration::with(['user', 'hackathon'])->findOrFail($validated['registration_id']);
        if ($registration->hackathon_id !== $hackathon->id) {
            abort(403, 'A inscrição selecionada não pertence a este Hackathon.');
        }

        $team = Team::findOrFail($validated['team_id']);
        if ($team->hackathon_id && $team->hackathon_id !== $hackathon->id) {
            abort(403, 'A equipe selecionada pertence a outro Hackathon.');
        }

        try {
            $this->registrationService->assignParticipantToTeam($registration, $team, Auth::user());
            return redirect()->back()->with('success', "Participante {$registration->user->name} vinculado com sucesso à equipe {$team->name}!");
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['participant_management' => $e->getMessage()]);
        }
    }

    /**
     * Transfere participante entre duas equipes do mesmo hackathon
     */
    public function transferTeam(Request $request, string $hackathonId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeAdmin($hackathon);

        $validated = $request->validate([
            'registration_id' => 'required|uuid|exists:hackathon_registrations,id',
            'target_team_id' => 'required|uuid|exists:teams,id',
        ]);

        $registration = HackathonRegistration::with(['user', 'team', 'hackathon'])->findOrFail($validated['registration_id']);
        if ($registration->hackathon_id !== $hackathon->id) {
            abort(403, 'A inscrição selecionada não pertence a este Hackathon.');
        }

        $targetTeam = Team::findOrFail($validated['target_team_id']);
        if ($targetTeam->hackathon_id && $targetTeam->hackathon_id !== $hackathon->id) {
            abort(403, 'A equipe de destino pertence a outro Hackathon.');
        }

        try {
            $this->registrationService->transferParticipant($registration, $targetTeam, Auth::user());
            return redirect()->back()->with('success', "Participante {$registration->user->name} transferido para {$targetTeam->name}!");
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['participant_management' => $e->getMessage()]);
        }
    }

    /**
     * Remove participante da equipe atual, retornando para individual
     */
    public function removeFromTeam(Request $request, string $hackathonId, string $registrationId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeAdmin($hackathon);

        $registration = HackathonRegistration::with(['user', 'team', 'hackathon'])->findOrFail($registrationId);
        if ($registration->hackathon_id !== $hackathon->id) {
            abort(403, 'A inscrição selecionada não pertence a este Hackathon.');
        }

        try {
            $this->registrationService->removeParticipantFromTeam($registration, Auth::user());
            return redirect()->back()->with('success', "Participante {$registration->user->name} removido da equipe com sucesso!");
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['participant_management' => $e->getMessage()]);
        }
    }

    /**
     * Criação de equipe pela organização em nome dos participantes
     */
    public function createTeam(Request $request, string $hackathonId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeAdmin($hackathon);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'leader_id' => 'required|uuid|exists:users,id',
            'description' => 'nullable|string',
            'area_of_focus' => 'nullable|string|max:255',
            'member_registration_ids' => 'nullable|array',
            'member_registration_ids.*' => 'uuid|exists:hackathon_registrations,id',
        ]);

        $leader = User::findOrFail($validated['leader_id']);

        try {
            $team = $this->registrationService->createTeamForHackathon(
                $hackathon,
                $leader,
                [
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'area_of_focus' => $validated['area_of_focus'] ?? null,
                ],
                Auth::user()
            );

            // Aloca membros adicionais caso selecionados
            if (!empty($validated['member_registration_ids'])) {
                foreach ($validated['member_registration_ids'] as $regId) {
                    $reg = HackathonRegistration::find($regId);
                    if ($reg && $reg->hackathon_id === $hackathon->id && $reg->user_id !== $leader->id) {
                        $this->registrationService->assignParticipantToTeam($reg, $team, Auth::user());
                    }
                }
            }

            return redirect()->back()->with('success', "Equipe {$team->name} criada com sucesso pela organização!");
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
