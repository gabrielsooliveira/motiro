<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Hackathon permissions
            ['name' => 'hackathon.create', 'display_name' => 'Criar Hackathons', 'group' => 'hackathon'],
            ['name' => 'hackathon.update', 'display_name' => 'Editar Hackathons', 'group' => 'hackathon'],
            ['name' => 'hackathon.delete', 'display_name' => 'Remover Hackathons', 'group' => 'hackathon'],
            ['name' => 'hackathon.publish', 'display_name' => 'Publicar Hackathons', 'group' => 'hackathon'],
            ['name' => 'hackathon.configure', 'display_name' => 'Configurar Regras e Etapas', 'group' => 'hackathon'],

            // Team permissions
            ['name' => 'team.view', 'display_name' => 'Visualizar Equipes', 'group' => 'team'],
            ['name' => 'team.approve', 'display_name' => 'Aprovar Equipes', 'group' => 'team'],
            ['name' => 'team.reject', 'display_name' => 'Rejeitar Equipes', 'group' => 'team'],
            ['name' => 'team.disqualify', 'display_name' => 'Desclassificar Equipes', 'group' => 'team'],

            // Judge permissions
            ['name' => 'judge.create', 'display_name' => 'Convidar Juízes', 'group' => 'judge'],
            ['name' => 'judge.assign', 'display_name' => 'Distribuir Projetos para Juízes', 'group' => 'judge'],
            ['name' => 'judge.remove', 'display_name' => 'Remover Juízes', 'group' => 'judge'],

            // Evaluation permissions
            ['name' => 'evaluation.create', 'display_name' => 'Avaliar Projetos', 'group' => 'evaluation'],
            ['name' => 'evaluation.update', 'display_name' => 'Atualizar Avaliação', 'group' => 'evaluation'],
            ['name' => 'evaluation.view_all', 'display_name' => 'Visualizar Todas Avaliações', 'group' => 'evaluation'],
            ['name' => 'evaluation.publish', 'display_name' => 'Publicar Resultados da Avaliação', 'group' => 'evaluation'],

            // Results & Awards
            ['name' => 'result.view', 'display_name' => 'Visualizar Resultados e Ranking', 'group' => 'result'],
            ['name' => 'result.publish', 'display_name' => 'Publicar Ranking Oficial', 'group' => 'result'],

            // Audit
            ['name' => 'audit.view', 'display_name' => 'Visualizar Logs de Auditoria', 'group' => 'audit'],
        ];

        $permissionModels = [];
        foreach ($permissions as $perm) {
            $permissionModels[$perm['name']] = Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['display_name' => $perm['display_name'], 'group' => $perm['group']]
            );
        }

        // Roles
        $orgAdmin = Role::firstOrCreate(
            ['name' => 'org_admin'],
            ['display_name' => 'Administrador da Organização', 'description' => 'Acesso total de gestão dos hackathons da organização']
        );
        $orgAdmin->permissions()->sync(array_map(fn($p) => $p->id, $permissionModels));

        $orgModerator = Role::firstOrCreate(
            ['name' => 'org_moderator'],
            ['display_name' => 'Moderador', 'description' => 'Auxilia na aprovação de equipes e moderação']
        );
        $orgModerator->permissions()->sync([
            $permissionModels['team.view']->id,
            $permissionModels['team.approve']->id,
            $permissionModels['team.reject']->id,
            $permissionModels['result.view']->id,
        ]);

        $judge = Role::firstOrCreate(
            ['name' => 'judge'],
            ['display_name' => 'Juiz / Avaliador', 'description' => 'Avalia projetos designados']
        );
        $judge->permissions()->sync([
            $permissionModels['evaluation.create']->id,
            $permissionModels['evaluation.update']->id,
        ]);

        $competitor = Role::firstOrCreate(
            ['name' => 'competitor'],
            ['display_name' => 'Competidor', 'description' => 'Participante de equipes e hackathons']
        );

        $mentor = Role::firstOrCreate(
            ['name' => 'mentor'],
            ['display_name' => 'Mentor', 'description' => 'Acompanha equipes sem acesso às notas dos juízes']
        );
    }
}
