<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CreateOrganizationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Lista as organizações vinculadas ao usuário autenticado.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

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
                    'role' => $org->pivot->role,
                    'hackathons_count' => $org->hackathons_count,
                ];
            });

        return Inertia::render('Organization/Dashboard/Organization/Index', [
            'organizations' => $organizations,
        ]);
    }

    /**
     * Cria uma nova organização vinculada ao usuário autenticado.
     */
    public function store(Request $request, CreateOrganizationAction $createOrgAction): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:organizations,email'],
            'type' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable'],
        ]);

        $organization = $createOrgAction->execute($request->user(), $validated, $request);

        return redirect()->route('organization.dashboard')
            ->with('success', "Organização '{$organization->name}' criada com sucesso!");
    }
}
