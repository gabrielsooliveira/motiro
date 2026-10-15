<?php

namespace App\Http\Middleware;

use App\Services\ContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationContext
{
    /**
     * Handle an incoming request for institutional context.
     */
    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('organization.login')
                ->with('error', 'Faça login na Área da Organização para acessar.');
        }

        $activeOrg = ContextService::getActiveOrganization($request);

        // If no active organization in session, try to resolve safely
        if (!$activeOrg) {
            $userOrgs = $user->organizations()->wherePivot('is_active', true)->get();

            if ($userOrgs->isEmpty()) {
                // Deny administrative access without leaking or redirecting to participant portal
                abort(403, 'Acesso restrito: você não possui vínculo ativo com nenhuma organização.');
            }

            if ($userOrgs->count() === 1) {
                ContextService::setOrganizationContext($request, $userOrgs->first()->id);
                $activeOrg = $userOrgs->first();
            } else {
                return redirect()->route('organization.select');
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
