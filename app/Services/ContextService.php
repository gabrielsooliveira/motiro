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
     * Resolve the target route after login based on user's organizations
     */
    public static function resolvePostLoginRoute(User $user, Request $request): string
    {
        $orgs = $user->organizations()->wherePivot('is_active', true)->get();

        if ($orgs->count() === 0) {
            // Pure competitor
            self::setCompetitorContext($request);
            return route('dashboard.index');
        }

        if ($orgs->count() === 1) {
            // Single organization
            $singleOrg = $orgs->first();
            self::setOrganizationContext($request, $singleOrg->id);
            return route('organization.dashboard');
        }

        // Multiple organizations: direct to context selector
        return route('context.select');
    }

    /**
     * Get the active organization for the current session, or null
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

        return $user->organizations()
            ->where('organizations.id', $orgId)
            ->wherePivot('is_active', true)
            ->first();
    }

    /**
     * Get active context info for sharing with Inertia & checking access
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
                'user_organizations' => [],
            ];
        }

        $userOrganizations = $user->organizations()
            ->wherePivot('is_active', true)
            ->get(['organizations.id', 'organizations.name', 'organizations.slug', 'organizations.logo', 'organizations.type']);

        $contextType = $request->session()->get('current_context');
        $activeOrg = self::getActiveOrganization($request);

        // If organization was set as context but no longer valid or not yet set
        if ($contextType === self::CONTEXT_ORGANIZATION && !$activeOrg) {
            if ($userOrganizations->count() > 0) {
                $activeOrg = $userOrganizations->first();
                self::setOrganizationContext($request, $activeOrg->id);
            } else {
                $contextType = self::CONTEXT_COMPETITOR;
                self::setCompetitorContext($request);
            }
        } elseif (!$contextType) {
            if ($userOrganizations->count() > 0) {
                $contextType = self::CONTEXT_ORGANIZATION;
                $activeOrg = $userOrganizations->first();
                self::setOrganizationContext($request, $activeOrg->id);
            } else {
                $contextType = self::CONTEXT_COMPETITOR;
                self::setCompetitorContext($request);
            }
        }

        $roleInOrg = null;
        if ($activeOrg) {
            $membership = $user->organizations()->where('organizations.id', $activeOrg->id)->first();
            $roleInOrg = $membership?->pivot?->role ?? ($activeOrg->owner_id === $user->id ? 'admin' : 'collaborator');
        }

        return [
            'type' => $contextType,
            'active_organization' => $activeOrg ? [
                'id' => $activeOrg->id,
                'name' => $activeOrg->name,
                'slug' => $activeOrg->slug,
                'type' => $activeOrg->type,
                'logo' => $activeOrg->logo,
                'is_owner' => $activeOrg->owner_id === $user->id,
            ] : null,
            'role_in_organization' => $roleInOrg,
            'is_org_admin' => $roleInOrg === 'admin' || ($activeOrg && $activeOrg->owner_id === $user->id),
            'user_organizations' => $userOrganizations->map(fn($o) => [
                'id' => $o->id,
                'name' => $o->name,
                'slug' => $o->slug,
                'type' => $o->type,
                'logo' => $o->logo,
                'role' => $o->pivot->role,
            ])->values()->all(),
            'has_competitor_profile' => true,
        ];
    }

    /**
     * Switch to competitor context
     */
    public static function setCompetitorContext(Request $request): void
    {
        $request->session()->put('current_context', self::CONTEXT_COMPETITOR);
        $request->session()->forget('active_organization_id');
    }

    /**
     * Switch to specific organization context
     */
    public static function setOrganizationContext(Request $request, string $organizationId): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $hasAccess = $user->organizations()
            ->where('organizations.id', $organizationId)
            ->wherePivot('is_active', true)
            ->exists();

        if (!$hasAccess && !Organization::where('id', $organizationId)->where('owner_id', $user->id)->exists()) {
            return false;
        }

        $request->session()->put('current_context', self::CONTEXT_ORGANIZATION);
        $request->session()->put('active_organization_id', $organizationId);

        return true;
    }
}
