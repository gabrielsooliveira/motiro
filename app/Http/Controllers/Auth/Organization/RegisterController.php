<?php

namespace App\Http\Controllers\Auth\Organization;

use App\Actions\Organization\AcceptOrganizationInvitationAction;
use App\Actions\Organization\CreateOrganizationAction;
use App\Actions\User\CreateUserAccountAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginPersonalAccountRequest;
use App\Http\Requests\Auth\StoreUserAccountRequest;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    /**
     * Categorias oficiais de tipos de organização no Motirõ
     */
    public const ORGANIZATION_CATEGORIES = [
        'Empresa',
        'Universidade ou instituição de ensino',
        'Comunidade ou coletivo',
        'Organização social / Sem fins lucrativos',
        'Instituição pública',
        'Startup',
        'Outra',
    ];

    /**
     * Exibe o formulário de cadastro de organização.
     */
    public function index(Request $request): Response
    {
        return $this->create($request);
    }

    /**
     * Renderiza a tela de cadastro de organização em etapas.
     * Identifica usuário logado para avançar automaticamente a Etapa 1.
     */
    public function create(Request $request): Response
    {
        $invitationToken = $request->query('invitation');
        $invitation = null;

        if ($invitationToken) {
            $invitation = OrganizationInvitation::with('organization')
                ->where('token', $invitationToken)
                ->where('status', 'pending')
                ->first();
        }

        $user = Auth::user();
        $authUser = $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ] : null;

        return Inertia::render('Organization/Auth/Register', [
            'initialType' => $request->query('type', 'organization'),
            'authUser' => $authUser,
            'categories' => self::ORGANIZATION_CATEGORIES,
            'invitation' => $invitation ? [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'organization_name' => $invitation->organization?->name,
            ] : null,
        ]);
    }

    /**
     * Etapa 1 (Opção A) — Autentica uma conta pessoal existente no fluxo de cadastro.
     */
    public function loginPersonalAccount(LoginPersonalAccountRequest $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'login_email' => 'As credenciais fornecidas não coincidem com nossos registros. Verifique seu e-mail e senha.',
            ]);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        }

        return back()->with('success', 'Conta pessoal autenticada! Prossiga com os dados da organização.');
    }

    /**
     * Etapa 1 (Opção B) — Cria uma conta pessoal única no Motirõ.
     */
    public function registerPersonalAccount(
        StoreUserAccountRequest $request,
        CreateUserAccountAction $createUserAction
    ): JsonResponse|RedirectResponse {
        $user = $createUserAction->execute($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        }

        return back()->with('success', 'Conta pessoal criada com sucesso! Prossiga com os dados da organização.');
    }

    /**
     * Etapa 3 — Criação definitiva da Organização com vínculo atômico de administrador.
     */
    public function store(
        Request $request,
        CreateUserAccountAction $createUserAction,
        CreateOrganizationAction $createOrgAction,
        AcceptOrganizationInvitationAction $acceptInviteAction
    ): RedirectResponse {
        // Aceitação de convite institucional
        if ($request->filled('invitation_token')) {
            return $this->handleInvitation($request, $createUserAction, $acceptInviteAction);
        }

        // Resolução do usuário autenticado responsável
        $user = Auth::user();
        if (!$user) {
            $user = $this->resolveUserFromGuestPayload($request, $createUserAction);
            if (!$user) {
                return back()->withErrors([
                    'auth' => 'Você precisa estar conectado à sua conta pessoal para cadastrar uma organização.',
                ]);
            }
        }

        /** @var StoreOrganizationRequest $orgRequest */
        $orgRequest = app(StoreOrganizationRequest::class);
        $orgName = $orgRequest->resolvedOrganizationName();

        if (empty($orgName)) {
            return back()->withErrors(['organization_name' => 'O nome da organização é obrigatório.']);
        }

        $targetOrg = $createOrgAction->execute($user, [
            'name' => $orgName,
            'type' => $orgRequest->resolvedOrganizationType(),
            'email' => $orgRequest->resolvedInstitutionalEmail(),
            'website' => $orgRequest->input('website'),
            'description' => $orgRequest->input('description'),
            'logo' => $orgRequest->file('logo') ?: $orgRequest->input('logo'),
        ], $request);

        if ($request->input('redirect_to') === 'dashboard') {
            return redirect()->route('organization.dashboard')
                ->with('success', 'Organização cadastrada com sucesso! Bem-vindo ao painel.');
        }

        return redirect()->route('organization.register.success', ['organization' => $targetOrg->id])
            ->with('success', 'Organização cadastrada com sucesso! Configure seu espaço para começar.');
    }

    /**
     * Etapa 4 — Tela de Conclusão e Acesso ao Painel Institucional.
     */
    public function success(Organization $organization): Response|RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('organization.login');
        }

        $hasLink = $organization->owner_id === $user->id ||
            $organization->users()->where('users.id', $user->id)->exists();

        if (!$hasLink) {
            abort(403, 'Acesso não autorizado aos dados desta organização.');
        }

        return Inertia::render('Organization/Auth/Success', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type,
                'email' => $organization->email,
                'website' => $organization->website,
                'logo' => $organization->logo,
                'description' => $organization->description,
            ],
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Auxiliar para resolver usuário se enviado em payload convidado combinado
     */
    private function resolveUserFromGuestPayload(Request $request, CreateUserAccountAction $createUserAction): ?User
    {
        $userName = $request->input('personal_name') ?: $request->input('user_name') ?: $request->input('name');
        $userEmail = $request->input('personal_email') ?: $request->input('user_email') ?: $request->input('email');
        $password = $request->input('password');

        if (!$userEmail || !$password) {
            return null;
        }

        $request->validate([
            'password' => ['required', 'confirmed', 'min:6'],
        ]);

        $existingUser = User::where('email', strtolower(trim($userEmail)))->first();
        if ($existingUser) {
            if (!Hash::check($password, $existingUser->password)) {
                return null;
            }
            $user = $existingUser;
        } else {
            $user = $createUserAction->execute([
                'name' => $userName ?: 'Usuário Organizador',
                'email' => $userEmail,
                'password' => $password,
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $user;
    }

    /**
     * Processa a aceitação de convite institucional
     */
    private function handleInvitation(
        Request $request,
        CreateUserAccountAction $createUserAction,
        AcceptOrganizationInvitationAction $acceptInviteAction
    ): RedirectResponse {
        $invitation = OrganizationInvitation::where('token', $request->input('invitation_token'))
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return back()->withErrors([
                'invitation_token' => 'Este convite não foi encontrado ou já expirou.',
            ]);
        }

        $currentUser = Auth::user();

        if ($currentUser) {
            $user = $currentUser;
        } else {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', 'min:6'],
            ]);

            $existingUser = User::where('email', $invitation->email)->first();
            if ($existingUser) {
                if (!Hash::check($request->password, $existingUser->password)) {
                    return back()->withErrors([
                        'password' => 'A senha informada não confere com a conta existente vinculada a este convite.',
                    ]);
                }
                $user = $existingUser;
            } else {
                $user = $createUserAction->execute([
                    'name' => $request->name,
                    'email' => $invitation->email,
                    'password' => $request->password,
                ]);
            }

            Auth::login($user);
            $request->session()->regenerate();
        }

        $acceptInviteAction->execute($invitation, $user, $request);

        return redirect()->route('organization.dashboard')
            ->with('success', 'Convite aceito! Bem-vindo(a) à equipe da organização.');
    }
}
