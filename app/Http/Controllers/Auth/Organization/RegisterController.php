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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

    public function index(Request $request): Response
    {
        return $this->create($request);
    }

    /**
     * Renderiza a tela de cadastro de organização em etapas.
     * Pula a Etapa 1 automaticamente se o usuário já estiver autenticado.
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

        return inertia('Organization/Auth/Register', [
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
     * Etapa 1 (Opção A) — Autentica uma conta pessoal existente para prosseguir no fluxo.
     */
    public function loginPersonalAccount(LoginPersonalAccountRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors([
                'login_email' => 'As credenciais fornecidas não coincidem com nossos registros. Verifique seu e-mail e senha ou recupere seu acesso.',
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

        return back()->with('success', 'Conta conectada com sucesso! Prossiga com as informações da sua organização.');
    }

    /**
     * Etapa 1 (Opção B) — Criação de conta pessoal única no Motirõ.
     */
    public function registerPersonalAccount(
        StoreUserAccountRequest $request,
        ?CreateUserAccountAction $createUserAction = null
    ) {
        $createUserAction ??= app(CreateUserAccountAction::class);
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

        return back()->with('success', 'Conta pessoal criada com sucesso! Agora vamos aos dados da organização.');
    }

    /**
     * Etapa 3 — Criação definitiva da Organização com vínculo administrativo atômico.
     */
    public function store(
        Request $request,
        ?CreateUserAccountAction $createUserAction = null,
        ?CreateOrganizationAction $createOrgAction = null,
        ?AcceptOrganizationInvitationAction $acceptInviteAction = null
    ) {
        $createUserAction ??= app(CreateUserAccountAction::class);
        $createOrgAction ??= app(CreateOrganizationAction::class);
        $acceptInviteAction ??= app(AcceptOrganizationInvitationAction::class);

        $type = $request->input('type', 'organization');

        // Se o tipo for participante, delega para o controller de participante
        if ($type === 'competitor') {
            return app(\App\Http\Controllers\Auth\Participant\RegisterController::class)->store($request);
        }

        /** @var StoreOrganizationRequest $orgRequest */
        $orgRequest = $request instanceof StoreOrganizationRequest
            ? $request
            : app(StoreOrganizationRequest::class);

        // Caso seja aceitação de convite institucional
        $invitationToken = $request->input('invitation_token');
        if ($invitationToken) {
            $invitation = OrganizationInvitation::where('token', $invitationToken)
                ->where('status', 'pending')
                ->first();

            if ($invitation) {
                return $this->handleInvitation(
                    $request,
                    $invitation,
                    $createUserAction,
                    $acceptInviteAction
                );
            }
        }

        // Resolução do usuário responsável pela criação
        $user = Auth::user();

        // Suporte a requisição combinada (guest enviando criação de conta pessoal + organização ao mesmo tempo)
        if (!$user) {
            $user = $this->resolveUserFromGuestPayload($request, $createUserAction);
            if (!$user) {
                return back()->withErrors([
                    'auth' => 'Você precisa estar conectado à sua conta pessoal para cadastrar uma organização.',
                ]);
            }
        }

        $orgName = $orgRequest->resolvedOrganizationName();
        if (empty($orgName)) {
            return back()->withErrors(['organization_name' => 'O nome da organização é obrigatório.']);
        }

        // Criação atômica da organização via Action
        $targetOrg = $createOrgAction->execute($user, [
            'name' => $orgName,
            'type' => $orgRequest->resolvedOrganizationType(),
            'email' => $orgRequest->resolvedInstitutionalEmail(),
            'website' => $orgRequest->input('website'),
            'description' => $orgRequest->input('description'),
            'logo' => $orgRequest->file('logo') ?: $orgRequest->input('logo'),
        ], $request);

        // Se a chamada veio da rota unificada register.store ou pediu redirecionamento direto
        if ($request->routeIs('register.store') || $request->input('redirect_to') === 'dashboard') {
            return redirect()->route('organization.dashboard')
                ->with('success', 'Organização cadastrada com sucesso! Bem-vindo ao painel.');
        }

        // Redireciona para a Etapa 4 (Conclusão e acesso ao painel)
        return redirect()->route('organization.register.success', ['organization' => $targetOrg->id])
            ->with('success', 'Organização cadastrada com sucesso! Agora você pode configurar seu espaço e começar a organizar seus hackathons.');
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

        // Garante que o usuário autenticado tenha vínculo com a organização criada
        $hasLink = $organization->owner_id === $user->id ||
            $organization->users()->where('users.id', $user->id)->exists();

        if (!$hasLink) {
            abort(403, 'Acesso não autorizado aos dados desta organização.');
        }

        return inertia('Organization/Auth/Success', [
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
     * Auxiliar para resolver usuário a partir de payload combinado
     */
    private function resolveUserFromGuestPayload(Request $request, CreateUserAccountAction $createUserAction): ?User
    {
        $userName = $request->input('personal_name') ?: $request->input('user_name');
        $userEmail = $request->input('personal_email') ?: $request->input('user_email');
        $password = $request->input('password');

        if (!$userName && $request->has('name')) {
            $userName = $request->input('name');
        }
        if (!$userEmail && $request->has('email')) {
            $userEmail = $request->input('email');
        }

        if (!$userEmail || !$password) {
            return null;
        }

        $request->validate([
            'password' => ['required', 'confirmed', 'min:6'],
        ]);

        $existingUser = User::where('email', strtolower(trim($userEmail)))->first();
        if ($existingUser) {
            if (!Hash::check($password, $existingUser->password)) {
                abort(back()->withErrors([
                    'login_email' => 'Já existe uma conta para este e-mail pessoal, mas a senha informada não confere.',
                ]));
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
     * Processa aceitação de convite institucional delegando para Actions
     */
    private function handleInvitation(
        Request $request,
        OrganizationInvitation $invitation,
        CreateUserAccountAction $createUserAction,
        AcceptOrganizationInvitationAction $acceptInviteAction
    ) {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', 'min:6'],
        ]);

        $currentUser = Auth::user();

        if ($currentUser) {
            $user = $currentUser;
        } else {
            $existingUser = User::where('email', $invitation->email)->first();
            if ($existingUser) {
                if (!Hash::check($request->password, $existingUser->password)) {
                    abort(422, 'A senha informada não confere com a conta existente vinculada a este convite.');
                }
                $user = $existingUser;
            } else {
                $user = $createUserAction->execute([
                    'name' => $request->name,
                    'email' => $invitation->email,
                    'password' => $request->password,
                ]);
            }
        }

        $acceptInviteAction->execute($invitation, $user, $request);

        if (!Auth::check()) {
            Auth::login($user);
        }

        $request->session()->regenerate();

        return redirect()->route('organization.dashboard')
            ->with('success', 'Convite aceito! Bem-vindo(a) à equipe da organização.');
    }
}
