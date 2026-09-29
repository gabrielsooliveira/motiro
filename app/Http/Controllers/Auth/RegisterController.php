<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Register', [
            'initialType' => $request->query('type'),
        ]);
    }

    public function store(Request $request)
    {
        $type = $request->input('type', 'competitor');

        if ($type === 'organization') {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:organizations,email'],
                'password' => ['required', 'confirmed', 'min:6'],
                'organization_type' => ['required', 'string', 'in:Empresa,Universidade,Escola,Organização social,Órgão público,Comunidade,Startup,Outro'],
                'website' => ['nullable', 'url'],
            ]);

            $user = DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'terms_accepted_at' => now(),
                    'lgpd_consent_at' => now(),
                ]);

                $baseSlug = Str::slug($request->name);
                $slug = $baseSlug;
                $counter = 1;
                while (Organization::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $org = Organization::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'slug' => $slug,
                    'type' => $request->organization_type,
                    'website' => $request->website,
                    'password' => Hash::make($request->password),
                    'is_active' => true,
                    'owner_id' => $user->id,
                ]);

                OrganizationUser::create([
                    'organization_id' => $org->id,
                    'user_id' => $user->id,
                    'role' => 'admin',
                ]);

                $orgAdminRole = Role::where('name', 'org_admin')->first();
                if ($orgAdminRole) {
                    $user->roles()->attach($orgAdminRole->id, [
                        'organization_id' => $org->id,
                    ]);
                }

                return $user;
            });

            Auth::login($user);
            return redirect()->route('dashboard.index')->with('success', 'Organização cadastrada com sucesso! Bem-vindo ao painel.');
        }

        // Competitor registration flow
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:6'],
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'terms_accepted_at' => now(),
                'lgpd_consent_at' => now(),
            ]);

            $competitorRole = Role::where('name', 'competitor')->first();
            if ($competitorRole) {
                $user->roles()->attach($competitorRole->id);
            }

            return $user;
        });

        Auth::login($user);
        return redirect()->route('dashboard.index')->with('success', 'Conta de competidor criada com sucesso! Explore os Hackathons disponíveis.');
    }
}
