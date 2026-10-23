<?php

namespace App\Http\Controllers\Auth\Participant;

use App\Actions\User\CreateUserAccountAction;
use App\Http\Controllers\Controller;
use App\Services\ContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    /**
     * Exibe a tela de cadastro para novos participantes.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Participant/Auth/Register', [
            'initialType' => 'competitor',
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->index($request);
    }

    /**
     * Registra uma nova conta com perfil de participante em user_participant.
     */
    public function store(Request $request, CreateUserAccountAction $createUserAction): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:6'],
            'terms' => ['nullable'],
        ], [
            'name.required' => 'Informe seu nome completo.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado. Faça login ou recupere sua senha.',
            'password.required' => 'Defina uma senha de acesso.',
            'password.confirmed' => 'A confirmação de senha não coincide.',
            'password.min' => 'A senha deve conter no mínimo :min caracteres.',
        ]);

        // Cria a identidade do usuário e provisiona automaticamente o perfil de participante
        $user = $createUserAction->execute($validated, true);

        Auth::login($user);
        $request->session()->regenerate();

        ContextService::setCompetitorContext($request);

        return redirect()->route('participant.dashboard')
            ->with('success', 'Conta de participante criada com sucesso! Explore os Hackathons disponíveis.');
    }
}
