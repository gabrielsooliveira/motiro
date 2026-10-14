<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class LoginController extends Controller
{
    public function index(): Response
    {
        return inertia('Organization/Auth/Login');
    }

    public function login(Request $request)
    {
       $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->with('error', 'As credenciais fornecidas não coincidem com nossos registros.');
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if ($context === 'organization') {
            // Verify the user has at least one active organization
            $userOrgs = $user->organizations()->wherePivot('is_active', true)->get();

            if ($userOrgs->isEmpty()) {
                // No org access — redirect to participant dashboard with warning
                ContextService::setCompetitorContext($request);
                return redirect()->route('dashboard.index')
                    ->with('warning', 'Sua conta não possui acesso a nenhuma organização. Você foi redirecionado para a área do participante.');
            }

            if ($userOrgs->count() === 1) {
                ContextService::setOrganizationContext($request, $userOrgs->first()->id);
                return redirect()->route('organization.dashboard')
                    ->with('success', 'Login realizado com sucesso. Bem-vindo à área institucional.');
            }

            // Multiple organizations — ask user to choose
            return redirect()->route('context.select')
                ->with('info', 'Selecione a organização em que deseja trabalhar.');
        }

        // Default: participant context
        ContextService::setCompetitorContext($request);
        return redirect()->route('dashboard.index')
            ->with('success', 'Login realizado com sucesso. Bem-vindo ao Motirõ!');
    }
}
