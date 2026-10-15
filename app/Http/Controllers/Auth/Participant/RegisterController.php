<?php

namespace App\Http\Controllers\Auth\Participant;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Response;

class RegisterController extends Controller
{
    public function index(Request $request): Response
    {
        return inertia('Participant/Auth/Register', [
            'initialType' => 'competitor',
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->index($request);
    }

    public function store(Request $request, ?\App\Actions\User\CreateUserAccountAction $createUserAction = null)
    {
        $createUserAction ??= app(\App\Actions\User\CreateUserAccountAction::class);
        $type = $request->input('type', 'competitor');

        // Se o tipo for organização, delega para o controller de organização
        if ($type === 'organization') {
            return app(\App\Http\Controllers\Auth\Organization\RegisterController::class)->store($request);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:6'],
        ]);

        $user = $createUserAction->execute($validated);

        Auth::login($user);

        ContextService::setCompetitorContext($request);

        return redirect()->route('dashboard.index')
            ->with('success', 'Conta de participante criada com sucesso! Explore os Hackathons disponíveis.');
    }
}
