<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContextService
{
    public const CONTEXT_COMPETITOR = 'competitor';
    public const CONTEXT_ORGANIZATION = 'organization';

    /**
     * Resolve a rota de destino do usuário após login considerando seus contextos ativos.
     */
    public static function resolvePostLoginRoute(User $user, Request $request): string
    {
        $hasParticipant = $user->hasParticipantProfile();
        $hasOrganization = $user->hasOrganizationProfile();

        // Usuário possui ambos os contextos: direciona para a tela de escolha
        if ($hasParticipant && $hasOrganization) {
            return route('context.select');
        }

        // Usuário é exclusivamente participante
        if ($hasParticipant) {
            self::setCompetitorContext($request);
            return route('participant.dashboard');
        }

        // Usuário é exclusivamente organizacional
        if ($hasOrganization) {
            $orgs = $user->activeOrganizations()->get();

            if ($orgs->count() === 1) {
                $singleOrg = $orgs->first();
                self::setOrganizationContext($request, $singleOrg->id);
                return route('organization.dashboard');
            }

            return route('organization.select');
        }

        // Caso base (conta sem perfis criados ainda)
        return route('home');
    }

    /**
     * Retorna a organização ativa na sessão do usuário autenticado.
     */
    public static function getActiveOrganization(?Request $request = null): ?Organization
    {
        $request = $request ?: request();
        $orgId = $request->session()->get('active_organization_id');

        if (!$orgId) {
            return null;
        }

        $user = Auth::user();
        if (!$user) {
            return null;
        }

        // Verifica se o usuário tem vínculo ativo ou é proprietário
        return Organization::where('id', $orgId)
            ->where(function ($query) use ($user, $orgId) {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('users', function ($q) use ($user, $orgId) {
                        $q->where('users.id', $user->id)
                          ->where('organization_user.is_active', true);
                    });
            })
            ->first();
    }

    /**
     * Define o contexto da sessão como Participante.
     */
    public static function setCompetitorContext(Request $request): void
    {
        $request->session()->put('current_context', self::CONTEXT_COMPETITOR);
        $request->session()->forget('active_organization_id');
    }

    /**
     * Define o contexto institucional de uma organização específica na sessão.
     */
    public static function setOrganizationContext(Request $request, string $organizationId): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $organization = Organization::where('id', $organizationId)->first();
        if (!$organization) {
            return false;
        }

        $isOwner = $organization->owner_id === $user->id;
        $isLinked = $user->organizations()
            ->where('organizations.id', $organizationId)
            ->wherePivot('is_active', true)
            ->exists();

        if (!$isOwner && !$isLinked) {
            return false;
        }

        $request->session()->put('current_context', self::CONTEXT_ORGANIZATION);
        $request->session()->put('active_organization_id', $organizationId);

        return true;
    }

    /**
     * Retorna os metadados do contexto para compartilhamento no Inertia.
     */
    public static function getContextData(?Request $request = null): array
    {
        $request = $request ?: request();
        $user = Auth::user();

        if (!$user) {
            return [
                'type' => null,
                'active_organization' => null,
                'role' => null,
                'is_org_admin' => false,
                'user_organizations' => [],
                'has_participant_profile' => false,
                'has_organization_profile' => false,
            ];
        }

        $hasParticipant = $user->hasParticipantProfile();
        $hasOrganization = $user->hasOrganizationProfile();

        $activeOrg = self::getActiveOrganization($request);
        $contextType = $request->session()->get('current_context');

        $userOrganizations = $user->activeOrganizations()
            ->get(['organizations.id', 'organizations.name', 'organizations.slug', 'organizations.logo', 'organizations.type']);

        $roleInOrg = null;
        if ($activeOrg) {
            $membership = $user->organizations()->where('organizations.id', $activeOrg->id)->first();
            $roleInOrg = $membership?->pivot?->role ?? ($activeOrg->owner_id === $user->id ? 'owner' : 'collaborator');
        }

        return [
            'type' => $contextType,
            'has_participant_profile' => $hasParticipant,
            'has_organization_profile' => $hasOrganization,
            'active_organization' => $activeOrg ? [
                'id' => $activeOrg->id,
                'name' => $activeOrg->name,
                'slug' => $activeOrg->slug,
                'type' => $activeOrg->type,
                'logo' => $activeOrg->logo,
                'is_owner' => $activeOrg->owner_id === $user->id,
            ] : null,
            'role_in_organization' => $roleInOrg,
            'is_org_admin' => in_array($roleInOrg, ['owner', 'admin']) || ($activeOrg && $activeOrg->owner_id === $user->id),
            'user_organizations' => $userOrganizations->map(fn($o) => [
                'id' => $o->id,
                'name' => $o->name,
                'slug' => $o->slug,
                'type' => $o->type,
                'logo' => $o->logo,
                'role' => $o->pivot?->role ?? ($o->owner_id === $user->id ? 'owner' : 'collaborator'),
            ])->values()->all(),
        ];
    }
}
