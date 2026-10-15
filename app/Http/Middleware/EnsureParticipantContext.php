<?php

namespace App\Http\Middleware;

use App\Services\ContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParticipantContext
{
    /**
     * Ensures the user is authenticated to access the participant portal.
     * Redirects to the participant login page if not authenticated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('participant.login')
                ->with('error', 'Faça login na Área do Participante para continuar.');
        }

        if ($request->session()->get('current_context') !== ContextService::CONTEXT_COMPETITOR) {
            ContextService::setCompetitorContext($request);
        }

        return $next($request);
    }
}
