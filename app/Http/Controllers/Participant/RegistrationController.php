<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Hackathon;
use App\Models\HackathonRegistration;
use App\Models\Team;
use App\Services\RegistrationService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    protected RegistrationService $registrationService;

    public function __construct(RegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /**
     * Submit registration (individual or team) to a hackathon
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hackathon_id' => 'required|uuid|exists:hackathons,id',
            'participation_type' => 'nullable|in:individual,team',
            'team_id' => 'nullable|uuid|exists:teams,id',
            'custom_answers' => 'nullable|array',
            'submitted_documents' => 'nullable|array',
        ]);

        $hackathon = Hackathon::findOrFail($validated['hackathon_id']);
        $user = Auth::user();
        $participationType = $validated['participation_type'] ?? (!empty($validated['team_id']) ? 'team' : 'individual');

        try {
            if ($participationType === 'team' || !empty($validated['team_id'])) {
                if (empty($validated['team_id'])) {
                    return redirect()->back()->withErrors(['team_id' => 'Selecione uma equipe para realizar a inscrição por equipe.']);
                }

                $team = Team::findOrFail($validated['team_id']);

                // Verify that the user is the leader or a member of this team
                if ($team->leader_id !== $user->id && !$team->members->contains('id', $user->id)) {
                    return redirect()->back()->withErrors(['team_id' => 'Apenas o líder ou integrantes da equipe podem inscrevê-la no Hackathon.']);
                }

                $this->registrationService->registerTeam(
                    $hackathon,
                    $team,
                    $user,
                    $validated['custom_answers'] ?? [],
                    $validated['submitted_documents'] ?? []
                );

                return redirect()->back()->with('success', 'Inscrição da equipe realizada com sucesso!');
            }

            // Inscrição individual
            $this->registrationService->registerIndividual(
                $hackathon,
                $user,
                $validated['custom_answers'] ?? [],
                $validated['submitted_documents'] ?? []
            );

            return redirect()->back()->with('success', 'Inscrição individual realizada com sucesso!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['registration' => $e->getMessage()]);
        }
    }

    /**
     * Admin review: approve, reject or disqualify registration
     */
    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        $registration = HackathonRegistration::with('hackathon.organization')->findOrFail($id);

        $user = Auth::user();
        $isOrgAdmin = $registration->hackathon->organization->owner_id === $user->id;
        $hasPermission = $user->hasPermission('team.approve', $registration->hackathon->organization_id, $registration->hackathon_id);

        if (!$isOrgAdmin && !$hasPermission) {
            abort(403, 'Acesso não autorizado para gerenciar inscrições deste Hackathon.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,under_review,disqualified',
            'reason' => 'nullable|string',
        ]);

        $this->registrationService->updateStatus(
            $registration,
            $validated['status'],
            $user,
            $validated['reason'] ?? null
        );

        return redirect()->back()->with('success', 'Status da inscrição atualizado com sucesso!');
    }
}
