<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Middleware\Authenticate;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('organizacao*') || $request->is('organization*')) {
                return route('organization.login');
            }
            if ($request->is('participante*')) {
                return route('participant.login');
            }
            return route('login');
        });
        $middleware->alias([
            'auth'                => Authenticate::class,
            'org.context'         => \App\Http\Middleware\EnsureOrganizationContext::class,
            'participant.context' => \App\Http\Middleware\EnsureParticipantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
