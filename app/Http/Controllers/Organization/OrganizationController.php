<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationUser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = OrganizationUser::where('user_id', auth()->user()->id)
            ->with('organization:id,name,logo,description')
            ->select('organization_id', 'role')
            ->get();

        return inertia('Dashboard/Organization/Index', [
            'organizations' => $organizations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:organizations,email',
            'password' => 'required|min:6',
            'website' => 'nullable|url',
            'description' => 'nullable|string|max:500',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'slug' => Str::slug($validated['name']),
            'password' => bcrypt($validated['password']),
            'website' => $validated['website'] ?? null,
            'description' => $validated['description'] ?? null,
            'owner_id' => $request->user()->id,
        ];

        $org = Organization::create($data);
        OrganizationUser::create([
            "organization_id" => $org->id,
            "user_id" => $request->user()->id,
            "role" => "admin",
        ]);

        return redirect()->back()->with('success', 'Solicitação de organização criada com sucesso. Aguarde a aprovação.');
    }
}
