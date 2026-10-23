<?php

namespace App\Http\Middleware;

use App\Services\ContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParticipantContext
{
    /**
     * Garante que o usuário autenticado possua perfil de participante ativo.
     * Impede que usuários exclusivamente organizacionais acessem o portal de participante sem perfil.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('participant.login')
                ->with('error', 'Faça login na Área do Participante para continuar.');
        }

        // Se a requisição já for para completar o perfil de participante, permite prosseguir
        if ($request->routeIs('participant.complete_profile*')) {
            return $next($request);
        }

        // Validação estrutural do perfil de participante
        if (!$user->hasParticipantProfile()) {
            return redirect()->route('participant.complete_profile')
                ->with('warning', 'Para acessar a Área do Participante, complete o seu perfil de competidor.');
        }

        if ($request->session()->get('current_context') !== ContextService::CONTEXT_COMPETITOR) {
            ContextService::setCompetitorContext($request);
        }

        return $next($request);
    }
}
