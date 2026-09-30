<?php

namespace App\Http\Middleware;

use App\Services\ContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $activeOrg = ContextService::getActiveOrganization($request);

        // If no active organization in session, try to auto-select
        if (!$activeOrg) {
            $userOrgs = $user->organizations()->wherePivot('is_active', true)->get();

            if ($userOrgs->isEmpty()) {
                // User has no organizations at all
                return redirect()->route('dashboard.index')->with('error', 'Você não possui vínculo com nenhuma organização.');
            }

            if ($userOrgs->count() === 1) {
                ContextService::setOrganizationContext($request, $userOrgs->first()->id);
                $activeOrg = $userOrgs->first();
            } else {
                return redirect()->route('context.select');
            }
        }

        // Verify specific role if requested (e.g. 'admin')
        if ($requiredRole === 'admin') {
            $isOwner = $activeOrg->owner_id === $user->id;
            $isAdmin = $user->organizations()
                ->where('organizations.id', $activeOrg->id)
                ->wherePivot('role', 'admin')
                ->wherePivot('is_active', true)
                ->exists();

            if (!$isOwner && !$isAdmin) {
                abort(403, 'Acesso restrito aos administradores da organização.');
            }
        }

        return $next($request);
    }
}
