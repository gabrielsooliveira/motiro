<?php

namespace App\Http\Middleware;

use App\Services\ContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationContext
{
    /**
     * Garante que o usuário autenticado possua vínculo ativo com a organização.
     * Impede que usuários puramente participantes acessem a área administrativa.
     */
    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('organization.login')
                ->with('error', 'Faça login na Área da Organização para acessar.');
        }

        // Verifica se a conta possui ao menos uma organização vinculada
        if (!$user->hasOrganizationProfile()) {
            abort(403, 'Acesso restrito: sua conta não possui vínculo ativo com nenhuma organização.');
        }

        $activeOrg = ContextService::getActiveOrganization($request);

        // Se nenhuma organização estiver ativa na sessão, resolve com segurança
        if (!$activeOrg) {
            if (method_exists($user, 'ensureOwnershipIntegrity')) {
                $user->ensureOwnershipIntegrity();
            }

            $userOrgs = $user->activeOrganizations()->get();

            if ($userOrgs->isEmpty()) {
                abort(403, 'Acesso restrito: você não possui vínculo ativo com nenhuma organização.');
            }

            if ($userOrgs->count() === 1) {
                ContextService::setOrganizationContext($request, $userOrgs->first()->id);
                $activeOrg = $userOrgs->first();
            } else {
                return redirect()->route('organization.select');
            }
        }

        // Validação de papel restrito (ex: admin ou owner)
        if ($requiredRole === 'admin') {
            $isOwner = $activeOrg->owner_id === $user->id;
            $isAdmin = $user->organizations()
                ->where('organizations.id', $activeOrg->id)
                ->whereIn('organization_user.role', ['owner', 'admin'])
                ->wherePivot('is_active', true)
                ->exists();

            if (!$isOwner && !$isAdmin) {
                abort(403, 'Acesso restrito aos administradores da organização.');
            }
        }

        return $next($request);
    }
}
