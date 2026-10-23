<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContextController extends Controller
{
    /**
     * Exibe a tela de escolha e alternância de contexto (Participante vs Organizações).
     */
    public function select(): Response|RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('select.login');
        }

        $organizations = $user->activeOrganizations()
            ->withCount('hackathons')
            ->get(['organizations.id', 'organizations.name', 'organizations.slug', 'organizations.logo', 'organizations.type', 'organizations.description'])
            ->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'logo' => $org->logo,
                    'type' => $org->type,
                    'description' => $org->description,
                    'role' => $org->pivot?->role ?? ($org->owner_id === $user->id ? 'owner' : 'collaborator'),
                    'hackathons_count' => $org->hackathons_count,
                ];
            });

        return Inertia::render('Organization/SelectContext', [
            'organizations' => $organizations,
            'hasParticipantProfile' => $user->hasParticipantProfile(),
            'hasOrganizationProfile' => $user->hasOrganizationProfile(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Alterna o contexto ativo da sessão de forma segura.
     */
    public function switch(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('select.login');
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:competitor,organization'],
            'organization_id' => ['required_if:type,organization', 'nullable', 'uuid'],
        ]);

        if ($validated['type'] === 'competitor') {
            if (!$user->hasParticipantProfile()) {
                return redirect()->route('participant.complete_profile')
                    ->with('warning', 'Você ainda não possui um perfil de participante. Complete seus dados para participar de hackathons.');
            }

            ContextService::setCompetitorContext($request);
            return redirect()->route('participant.dashboard')->with('success', 'Contexto de participante ativado.');
        }

        // Contexto Organizacional
        $success = ContextService::setOrganizationContext($request, $validated['organization_id']);

        if (!$success) {
            return back()->withErrors(['error' => 'Você não possui permissão ativa para acessar esta organização.']);
        }

        return redirect()->route('organization.dashboard')->with('success', 'Contexto institucional ativado.');
    }
}
