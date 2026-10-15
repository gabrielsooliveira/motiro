<?php

namespace App\Http\Controllers\Auth\Organization;

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

    public function create(): Response
    {
        return $this->index();
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'As credenciais fornecidas não coincidem com nossos registros.',
            ]);
        }

        $user = Auth::user();

        // Consulta as organizações ativas vinculadas ao usuário
        $userOrgs = $user->organizations()
            ->wherePivot('is_active', true)
            ->get();

        // Se não houver organizações autorizadas, nega o acesso administrativo com orientação adequada
        // Não redireciona automaticamente para a área do participante
        if ($userOrgs->isEmpty()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Sua conta não possui vínculo com nenhuma organização. Caso deseje participar de eventos, acesse a Área do Participante ou realize o cadastro de uma nova organização.',
            ]);
        }

        $request->session()->regenerate();

        // Se houver apenas uma organização, direciona diretamente para ela
        if ($userOrgs->count() === 1) {
            $organization = $userOrgs->first();
            ContextService::setOrganizationContext($request, $organization->id);

            return redirect()->route('organization.dashboard')
                ->with('success', 'Login realizado com sucesso. Bem-vindo à área institucional.');
        }

        // Se houver várias organizações, direciona para a tela de seleção
        return redirect()->route('organization.select');
    }

    public function login(Request $request)
    {
        return $this->store($request);
    }
}
