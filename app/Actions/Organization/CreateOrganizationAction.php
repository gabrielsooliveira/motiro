<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrganizationAction
{
    /**
     * Cria uma organização atomicamente, vinculando o usuário como administrador e definindo o contexto ativo.
     *
     * @param User $user
     * @param array $data
     * @param Request|null $request
     * @return Organization
     */
    public function execute(User $user, array $data, ?Request $request = null): Organization
    {
        return DB::transaction(function () use ($user, $data, $request) {
            $name = trim($data['name']);

            // Upload de logotipo caso fornecido
            $logoUrl = null;
            if (isset($data['logo']) && $data['logo'] instanceof UploadedFile && $data['logo']->isValid()) {
                $path = $data['logo']->store('organizations/logos', 'public');
                $logoUrl = '/storage/' . $path;
            } elseif (isset($data['logo']) && is_string($data['logo']) && !empty($data['logo'])) {
                $logoUrl = $data['logo'];
            }

            // Geração de slug único e legível
            $baseSlug = Str::slug($name);
            $slug = $baseSlug ?: 'organizacao';
            $counter = 1;
            while (Organization::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $organization = Organization::create([
                'name' => $name,
                'slug' => $slug,
                'type' => $data['type'] ?? 'Empresa',
                'email' => $data['email'] ?? null, // E-mail institucional independente da conta de login
                'website' => $data['website'] ?? null,
                'description' => $data['description'] ?? null,
                'logo' => $logoUrl,
                'is_active' => true,
                'owner_id' => $user->id,
            ]);

            // Vínculo inicial de administrador principal
            OrganizationUser::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'is_active' => true,
            ]);

            // Atribuição de permissão RBAC org_admin para esta organização
            $orgAdminRole = Role::where('name', 'org_admin')->first();
            if ($orgAdminRole) {
                $user->roles()->syncWithoutDetaching([
                    $orgAdminRole->id => ['organization_id' => $organization->id],
                ]);
            }

            // Ativa o contexto institucional da organização recém-criada
            if ($request) {
                ContextService::setOrganizationContext($request, $organization->id);
            }

            return $organization;
        });
    }
}
