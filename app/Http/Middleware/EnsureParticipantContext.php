<?php

namespace App\Http\Middleware;

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
                ->with('error', 'Faça login para acessar a área do participante.');
        }

        return $next($request);
    }
}
