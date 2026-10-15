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
     * Show context selection screen with reliable organization data
     */
    public function select(Request $request): Response|RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('organization.login');
        }

        $organizations = $user->organizations()
            ->wherePivot('is_active', true)
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
                    'role' => $org->pivot->role,
                    'hackathons_count' => $org->hackathons_count,
                ];
            });

        return Inertia::render('Organization/Auth/SelectContext', [
            'organizations' => $organizations,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Switch current active context safely
     */
    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:competitor,organization',
            'organization_id' => 'required_if:type,organization|nullable|uuid',
        ]);

        if ($validated['type'] === 'competitor') {
            ContextService::setCompetitorContext($request);
            return redirect()->route('participant.dashboard')->with('success', 'Contexto alterado para Participante.');
        }

        // Backend validates the link before setting context
        $success = ContextService::setOrganizationContext($request, $validated['organization_id']);

        if (!$success) {
            return redirect()->back()->with('error', 'Acesso não autorizado para a organização selecionada.');
        }

        return redirect()->route('organization.dashboard')->with('success', 'Contexto institucional ativado.');
    }
}
