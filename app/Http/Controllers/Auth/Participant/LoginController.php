<?php

namespace App\Http\Controllers\Auth\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginContextRequest;
use App\Services\ContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Exibe o formulário de login para participantes.
     */
    public function index(): Response
    {
        return Inertia::render('Participant/Auth/Login');
    }

    public function create(): Response
    {
        return $this->index();
    }

    /**
     * Processa a autenticação do participante com verificação de perfil e contexto.
     */
    public function store(LoginContextRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'As credenciais fornecidas não coincidem com nossos registros.',
            ]);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        $hasParticipant = $user->hasParticipantProfile();
        $hasOrganization = $user->hasOrganizationProfile();

        // 1. Se possuir ambos os perfis, direciona para a tela de escolha
        if ($hasParticipant && $hasOrganization) {
            return redirect()->route('context.select');
        }

        // 2. Se possuir perfil de participante, direciona direto ao painel
        if ($hasParticipant) {
            ContextService::setCompetitorContext($request);
            return redirect()->route('participant.dashboard');
        }

        // 3. Se for usuário apenas de organização (sem perfil de participante),
        // não autoriza o acesso direto e orienta a completar o perfil de competidor
        return redirect()->route('participant.complete_profile')
            ->with('info', 'Sua conta ainda não possui um perfil de participante. Complete seus dados para participar de hackathons.');
    }

    public function login(LoginContextRequest $request): RedirectResponse
    {
        return $this->store($request);
    }
}
