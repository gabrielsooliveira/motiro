<?php

namespace App\Http\Controllers\Auth\Participant;

use App\Http\Controllers\Controller;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class LoginController extends Controller
{
    public function index(): Response
    {
        return inertia('Participant/Auth/Login');
    }

    public function create(): Response
    {
        return $this->index();
    }

    public function store(\App\Http\Requests\Auth\LoginContextRequest $request)
    {
        $validated = $request->validated();

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            ContextService::setCompetitorContext($request);

            return redirect()->route('participant.dashboard');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não coincidem com nossos registros.',
        ]);
    }

    public function login(\App\Http\Requests\Auth\LoginContextRequest $request)
    {
        return $this->store($request);
    }
}
