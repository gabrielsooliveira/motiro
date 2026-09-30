<?php

namespace App\Http\Controllers;

use App\Services\ContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContextController extends Controller
{
    /**
     * Show context selection screen
     */
    public function select(Request $request): Response|RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $organizations = $user->organizations()
            ->wherePivot('is_active', true)
            ->get(['organizations.id', 'organizations.name', 'organizations.slug', 'organizations.logo', 'organizations.type']);

        return Inertia::render('Auth/SelectContext', [
            'organizations' => $organizations,
            'user' => $user,
        ]);
    }

    /**
     * Switch current active context
     */
    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:competitor,organization',
            'organization_id' => 'required_if:type,organization|nullable|uuid',
        ]);

        if ($validated['type'] === 'competitor') {
            ContextService::setCompetitorContext($request);
            return redirect()->route('dashboard.index')->with('success', 'Contexto alterado para Competidor.');
        }

        $success = ContextService::setOrganizationContext($request, $validated['organization_id']);

        if (!$success) {
            return redirect()->back()->with('error', 'Você não tem permissão para acessar esta instituição.');
        }

        return redirect()->route('organization.dashboard')->with('success', 'Contexto institucional ativado.');
    }
}
