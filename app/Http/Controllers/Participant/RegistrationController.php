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
     * Submit team registration to a hackathon
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hackathon_id' => 'required|uuid|exists:hackathons,id',
            'team_id' => 'required|uuid|exists:teams,id',
            'custom_answers' => 'nullable|array',
        ]);

        $hackathon = Hackathon::findOrFail($validated['hackathon_id']);
        $team = Team::findOrFail($validated['team_id']);
        $user = Auth::user();

        // Verify that the user is the leader of this team
        if ($team->leader_id !== $user->id) {
            return redirect()->back()->withErrors(['team_id' => 'Apenas o líder da equipe pode inscrevê-la no Hackathon.']);
        }

        try {
            $this->registrationService->registerTeam(
                $hackathon,
                $team,
                $user,
                $validated['custom_answers'] ?? []
            );

            return redirect()->back()->with('success', 'Inscrição realizada com sucesso!');
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
