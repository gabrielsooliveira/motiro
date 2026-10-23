<?php

namespace App\Http\Controllers\Auth\Organization;

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
     * Exibe a tela de login institucional da organização.
     */
    public function index(): Response
    {
        return Inertia::render('Organization/Auth/Login');
    }

    /**
     * Alias REST para index.
     */
    public function create(): Response
    {
        return $this->index();
    }

    /**
     * Processa a autenticação do usuário e roteia para o contexto institucional.
     */
    public function store(LoginContextRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            // Tenta autenticar o proprietário da instituição caso tenha sido fornecido o e-mail de contato da organização
            $organization = \App\Models\Organization::where('email', $credentials['email'])->first();
            $authenticatedViaOrg = false;

            if ($organization && $organization->owner) {
                $authenticatedViaOrg = Auth::attempt([
                    'email' => $organization->owner->email,
                    'password' => $credentials['password'],
                ], $remember);
            }

            if (!$authenticatedViaOrg) {
                return back()->withErrors([
                    'email' => 'As credenciais fornecidas não coincidem com nossos registros.',
                ]);
            }
        }

        $request->session()->regenerate();
        $user = Auth::user();

        // Garante a integridade e sincronização com organization_user para proprietários
        if (method_exists($user, 'ensureOwnershipIntegrity')) {
            $user->ensureOwnershipIntegrity();
        }

        $hasParticipant = $user->hasParticipantProfile();
        $hasOrganization = $user->hasOrganizationProfile();

        // Se o usuário não possuir organizações vinculadas, nega o acesso ao painel administrativo
        if (!$hasOrganization) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Sua conta não possui vínculo com nenhuma organização. Caso deseje cadastrar uma nova instituição, acesse o Cadastro de Organização ou participe de eventos através da Área do Participante.',
            ]);
        }

        $userOrgs = $user->activeOrganizations()->get();

        // Se o usuário possuir exatamente uma organização:
        // Como o login foi realizado especificamente no Portal da Organização, ativa o contexto e vai direto ao dashboard
        if ($userOrgs->count() === 1) {
            $organization = $userOrgs->first();
            ContextService::setOrganizationContext($request, $organization->id);

            return redirect()->route('organization.dashboard')
                ->with('success', 'Login realizado com sucesso. Bem-vindo à área institucional.');
        }

        // Se possuir múltiplas organizações (ou for híbrido com múltiplas), direciona para seleção
        if ($hasParticipant) {
            return redirect()->route('context.select');
        }

        return redirect()->route('organization.select');
    }

    /**
     * Método de compatibilidade para requisições diretas de login.
     */
    public function login(LoginContextRequest $request): RedirectResponse
    {
        return $this->store($request);
    }
}
