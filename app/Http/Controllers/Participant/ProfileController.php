<?php

namespace App\Http\Controllers\Participant;

use App\Actions\Participant\CreateParticipantProfileAction;
use App\Http\Controllers\Controller;
use App\Services\ContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Exibe o formulário para completar ou criar o perfil de participante.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user && $user->hasParticipantProfile()) {
            return redirect()->route('participant.dashboard');
        }

        return Inertia::render('Participant/CompleteProfile', [
            'user' => [
                'name' => $user?->name,
                'email' => $user?->email,
            ],
        ]);
    }

    /**
     * Persiste o perfil de participante em user_participant e ativa o contexto.
     */
    public function store(Request $request, CreateParticipantProfileAction $action): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'education_institution' => ['nullable', 'string', 'max:255'],
            'education_course' => ['nullable', 'string', 'max:255'],
            'professional_experience' => ['nullable', 'string', 'max:1000'],
            'skills' => ['nullable', 'array'],
            'technologies' => ['nullable', 'array'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'is_profile_public' => ['boolean'],
        ]);

        $action->execute($user, $validated);

        ContextService::setCompetitorContext($request);

        return redirect()->route('participant.dashboard')
            ->with('success', 'Perfil de participante configurado com sucesso! Bem-vindo(a) à área de hackathons.');
    }
}
