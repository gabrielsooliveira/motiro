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
            'type' => 'nullable|in:individual,team',
            'team_id' => 'nullable|uuid|exists:teams,id',
            'custom_answers' => 'nullable|array',
            'submitted_documents' => 'nullable|array',
        ]);

        $hackathon = Hackathon::findOrFail($validated['hackathon_id']);
        $user = Auth::user();

        // Normalize participation_type from either field
        $participationType = $validated['participation_type'] ?? $validated['type'] ?? null;
        if (!$participationType) {
            $participationType = (!empty($validated['team_id']) && $hackathon->allowsTeamRegistration()) ? 'team' : 'individual';
        }

        try {
            if ($participationType === 'team') {
                if (!$hackathon->allowsTeamRegistration()) {
                    return redirect()->back()->withErrors([
                        'registration' => 'Este Hackathon aceita apenas inscrições individuais.',
                        'team_id' => 'Este Hackathon aceita apenas inscrições individuais.',
                    ])->with('error', 'Este Hackathon aceita apenas inscrições individuais.');
                }

                if (empty($validated['team_id'])) {
                    return redirect()->back()->withErrors([
                        'registration' => 'Selecione uma equipe para realizar a inscrição por equipe.',
                        'team_id' => 'Selecione uma equipe para realizar a inscrição por equipe.',
                    ])->with('error', 'Selecione uma equipe para realizar a inscrição por equipe.');
                }

                $team = Team::findOrFail($validated['team_id']);

                // Verify that the user is the leader or a member authorized to represent the team
                if ($team->leader_id !== $user->id && !$team->members->contains('id', $user->id)) {
                    return redirect()->back()->withErrors([
                        'registration' => 'Apenas o líder ou integrantes da equipe podem inscrevê-la no Hackathon.',
                        'team_id' => 'Apenas o líder ou integrantes da equipe podem inscrevê-la no Hackathon.',
                    ])->with('error', 'Apenas o líder ou integrantes da equipe podem inscrevê-la no Hackathon.');
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

            // Inscrição individual — garante team_id nulo
            if ($hackathon->registration_type === 'team_only' && !$hackathon->allow_team_creation) {
                return redirect()->back()->withErrors([
                    'registration' => 'Este Hackathon aceita exclusivamente inscrições por equipe.',
                ])->with('error', 'Este Hackathon aceita exclusivamente inscrições por equipe.');
            }

            $this->registrationService->registerIndividual(
                $hackathon,
                $user,
                $validated['custom_answers'] ?? [],
                $validated['submitted_documents'] ?? []
            );

            return redirect()->back()->with('success', 'Inscrição individual realizada com sucesso!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'registration' => $e->getMessage(),
                'error' => $e->getMessage(),
            ])->with('error', $e->getMessage());
        }
    }

    /**
     * Admin review: approve, reject or disqualify registration
     */
    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        $registration = HackathonRegistration::with('hackathon.organization')->findOrFail($id);
        $user = Auth::user();

        // Check granular permission via policy
        if (!\Illuminate\Support\Facades\Gate::allows('updateStatus', $registration)) {
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

    /**
     * Cancel an active or pending registration by participant
     */
    public function cancel(Request $request, string $id): RedirectResponse
    {
        $registration = HackathonRegistration::with('hackathon')->findOrFail($id);
        $user = Auth::user();

        if (!\Illuminate\Support\Facades\Gate::allows('cancel', $registration)) {
            abort(403, 'Acesso não autorizado para cancelar esta inscrição.');
        }

        try {
            $this->registrationService->cancelRegistration($registration, $user);
            return redirect()->back()->with('success', 'Inscrição cancelada com sucesso.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'registration' => $e->getMessage(),
                'error' => $e->getMessage(),
            ])->with('error', $e->getMessage());
        }
    }
}
