<?php

use App\Http\Middleware\EnsureOrganizationContext;
use App\Http\Middleware\EnsureParticipantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
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
        });
        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();
            if ($user) {
                if ($request->is('organizacao*') || $request->is('organization*')) {
                    return route('organization.dashboard');
                }
                if ($request->is('participante*')) {
                    return route('participant.dashboard');
                }
            }
        });
        $middleware->alias([
            'auth'                => Authenticate::class,
            'guest'               => RedirectIfAuthenticated::class,
            'org.context'         => EnsureOrganizationContext::class,
            'participant.context' => EnsureParticipantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
