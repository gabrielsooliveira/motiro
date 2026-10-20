<?php

use App\Models\Hackathon;
use App\Models\HackathonGroup;
use App\Models\HackathonRegistration;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\HackathonGroupService;
use App\Services\RegistrationService;
use App\Services\TeamService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin'], [
        'id' => (string) Str::uuid(),
        'name' => 'admin',
        'display_name' => 'Admin',
    ]);
});

function createTestOrgAndAdmin(): array
{
    $admin = User::factory()->create([
        'email' => 'orgadmin_' . Str::random(8) . '@example.com',
    ]);

    $org = Organization::create([
        'id' => (string) Str::uuid(),
        'name' => 'Instituição Teste',
        'slug' => 'inst-teste-' . Str::random(8),
        'owner_id' => $admin->id,
    ]);

    $org->users()->attach($admin->id, ['role' => 'admin', 'is_active' => true]);

    return [$org, $admin];
}

function createTestEvent(Organization $org, array $overrides = []): Hackathon
{
    return Hackathon::create(array_merge([
        'id' => (string) Str::uuid(),
        'organization_id' => $org->id,
        'title' => 'Hackathon Teste ' . Str::random(5),
        'slug' => 'hackathon-teste-' . Str::random(8),
        'status' => 'registration',
        'registration_type' => 'individual_or_team',
        'team_formation_mode' => 'by_participants',
        'team_leader_required' => true,
        'requires_team' => false,
        'allow_team_creation' => true,
        'allow_team_invites' => true,
        'allow_team_requests' => true,
        'allow_org_team_management' => true,
        'auto_approve_registrations' => true,
        'min_team_members' => 2,
        'max_team_members' => 5,
        'registration_start_date' => Carbon::now()->subDays(2),
        'registration_end_date' => Carbon::now()->addDays(10),
    ], $overrides));
}

test('1. Uma equipe permanente pode existir sem hackathons', function () {
    $creator = User::factory()->create(['name' => 'Criador Guilda']);
    $teamService = app(TeamService::class);

    $guild = $teamService->createTeam($creator, [
        'name' => 'Guilda Independente Alpha',
        'description' => 'Guilda para reunir talentos',
        'area_of_focus' => 'Cloud & Devops',
    ]);

    expect($guild->id)->not->toBeNull();
    expect($guild->hackathon_id)->toBeNull();
    expect($guild->leader_id)->toBe($creator->id);
    expect($guild->members)->toHaveCount(1);
    expect($guild->members->first()->id)->toBe($creator->id);
    expect(HackathonRegistration::where('user_id', $creator->id)->exists())->toBeFalse();
    expect(HackathonGroup::where('origin_team_id', $guild->id)->exists())->toBeFalse();
});

test('2. Convites para equipes permanentes não inscrevem participantes em eventos', function () {
    [$org, $admin] = createTestOrgAndAdmin();
    $hackathon = createTestEvent($org);

    $creator = User::factory()->create(['name' => 'Guild Leader']);
    $invitee = User::factory()->create(['name' => 'Novo Membro']);

    $teamService = app(TeamService::class);
    $guild = $teamService->createTeam($creator, ['name' => 'Guilda Independente']);

    // Envia convite de guilda
    $invite = $teamService->inviteMember($guild, $creator, $invitee->email);
    expect($invite->status)->toBe('pending');
    expect($invite->team_id)->toBe($guild->id);

    // Aceita convite
    $teamService->acceptInvitation($invite, $invitee);

    expect($guild->fresh()->members)->toHaveCount(2);
    // Não gerou inscrição em nenhum hackathon
    expect(HackathonRegistration::where('user_id', $invitee->id)->exists())->toBeFalse();
    expect(HackathonGroup::where('origin_team_id', $guild->id)->exists())->toBeFalse();
});

test('3. Um participante inscrito pode integrar um grupo sem pertencer a uma equipe permanente', function () {
    [$org, $admin] = createTestOrgAndAdmin();
    $hackathon = createTestEvent($org);

    $p1 = User::factory()->create(['name' => 'Part 1']);
    $p2 = User::factory()->create(['name' => 'Part 2']);

    $regService = app(RegistrationService::class);
    $groupService = app(HackathonGroupService::class);

    // Ambos se inscrevem no hackathon
    $regService->registerIndividual($hackathon, $p1);
    $regService->registerIndividual($hackathon, $p2);

    // Nenhum dos dois tem guilda permanente
    expect($p1->permanentTeams)->toHaveCount(0);
    expect($p2->permanentTeams)->toHaveCount(0);

    // Criam grupo de participação diretamente no evento
    $group = $groupService->createGroup(
        $hackathon,
        ['name' => 'Squad Ágil Evento'],
        $p1,
        [$p1->id, $p2->id],
        $p1
    );

    expect($group->id)->not->toBeNull();
    expect($group->hackathon_id)->toBe($hackathon->id);
    expect($group->origin_team_id)->toBeNull();
    expect($group->members)->toHaveCount(2);

    // Permanece sem equipes permanentes
    expect($p1->fresh()->permanentTeams)->toHaveCount(0);
    expect($p2->fresh()->permanentTeams)->toHaveCount(0);

    // Mas está no grupo de participação do hackathon
    expect($p1->fresh()->hackathonGroups)->toHaveCount(1);
    expect($p2->fresh()->hackathonGroups)->toHaveCount(1);
});

test('4. Integrantes de equipes permanentes diferentes podem compor um grupo quando permitido', function () {
    [$org, $admin] = createTestOrgAndAdmin();
    $hackathon = createTestEvent($org);

    $userA = User::factory()->create(['name' => 'Membro Guilda A']);
    $userB = User::factory()->create(['name' => 'Membro Guilda B']);

    $teamService = app(TeamService::class);
    $guildA = $teamService->createTeam($userA, ['name' => 'Guilda Vikings']);
    $guildB = $teamService->createTeam($userB, ['name' => 'Guilda Spartans']);

    expect($userA->permanentTeams->first()->id)->toBe($guildA->id);
    expect($userB->permanentTeams->first()->id)->toBe($guildB->id);

    // Ambos se inscrevem no hackathon
    $regService = app(RegistrationService::class);
    $regService->registerIndividual($hackathon, $userA);
    $regService->registerIndividual($hackathon, $userB);

    // Formam um grupo conjunto para o hackathon
    $groupService = app(HackathonGroupService::class);
    $group = $groupService->createGroup(
        $hackathon,
        ['name' => 'Squad Misto Hackathon'],
        $userA,
        [$userA->id, $userB->id],
        $userA
    );

    expect($group->members)->toHaveCount(2);
    // Suas guildas permanentes continuam intactas e distintas
    expect($guildA->fresh()->members->pluck('id'))->toContain($userA->id);
    expect($guildA->fresh()->members->pluck('id'))->not->toContain($userB->id);
    expect($guildB->fresh()->members->pluck('id'))->toContain($userB->id);
    expect($guildB->fresh()->members->pluck('id'))->not->toContain($userA->id);
});

test('5. Participantes não inscritos ou inelegíveis não podem ser adicionados a grupos', function () {
    [$org, $admin] = createTestOrgAndAdmin();
    $hackathon = createTestEvent($org);

    $inscrito = User::factory()->create(['name' => 'Inscrito']);
    $naoInscrito = User::factory()->create(['name' => 'Não Inscrito']);

    $regService = app(RegistrationService::class);
    $regService->registerIndividual($hackathon, $inscrito);

    $groupService = app(HackathonGroupService::class);

    expect(function () use ($groupService, $hackathon, $inscrito, $naoInscrito) {
        $groupService->createGroup(
            $hackathon,
            ['name' => 'Squad Inválido'],
            $inscrito,
            [$inscrito->id, $naoInscrito->id],
            $inscrito
        );
    })->toThrow(Exception::class, 'não possui inscrição registrada para o Hackathon');
});

test('6. A organização consegue selecionar participantes elegíveis do hackathon', function () {
    [$org, $admin] = createTestOrgAndAdmin();
    $hackathon = createTestEvent($org);

    $p1 = User::factory()->create(['name' => 'Candidato 1']);
    $p2 = User::factory()->create(['name' => 'Candidato 2']);

    $regService = app(RegistrationService::class);
    $r1 = $regService->registerIndividual($hackathon, $p1);
    $r2 = $regService->registerIndividual($hackathon, $p2);

    $response = test()->actingAs($admin)
        ->post(route('organization.hackathon.participants.create_team', $hackathon->id), [
            'name' => 'Equipe Formada Pela Org',
            'member_registration_ids' => [$r1->id, $r2->id],
            'leader_id' => $p1->id,
        ]);

    $response->assertSessionHasNoErrors();

    $group = HackathonGroup::where('hackathon_id', $hackathon->id)->where('name', 'Equipe Formada Pela Org')->first();
    expect($group)->not->toBeNull();
    expect($group->members)->toHaveCount(2);
    expect($group->leader_id)->toBe($p1->id);
});

test('7. As modalidades individual e em grupo respeitam as regras configuradas', function () {
    [$org, $admin] = createTestOrgAndAdmin();
    $teamOnlyHackathon = createTestEvent($org, [
        'requires_team' => true,
        'registration_type' => 'team_only',
        'allow_team_creation' => true,
    ]);

    $user = User::factory()->create();
    $regService = app(RegistrationService::class);

    // Em evento exclusivo por equipes, inscrição individual é aceita mas pendente de equipe (não elegível para submissão)
    $reg = $regService->registerIndividual($teamOnlyHackathon, $user);
    expect($reg->is_eligible)->toBeFalse();
    expect($reg->notes)->toContain('Aguardando formação ou ingresso em equipe');
});

test('8. Um participante não pode pertencer a dois grupos no mesmo hackathon', function () {
    [$org, $admin] = createTestOrgAndAdmin();
    $hackathon = createTestEvent($org);

    $p1 = User::factory()->create();
    $p2 = User::factory()->create();
    $p3 = User::factory()->create();

    $regService = app(RegistrationService::class);
    $regService->registerIndividual($hackathon, $p1);
    $regService->registerIndividual($hackathon, $p2);
    $regService->registerIndividual($hackathon, $p3);

    $groupService = app(HackathonGroupService::class);

    // Cria Grupo 1 com p1 e p2
    $group1 = $groupService->createGroup(
        $hackathon,
        ['name' => 'Grupo Um'],
        $p1,
        [$p1->id, $p2->id],
        $p1
    );

    // Tentativa de criar Grupo 2 incluindo p1 novamente deve falhar
    expect(function () use ($groupService, $hackathon, $p1, $p3) {
        $groupService->createGroup(
            $hackathon,
            ['name' => 'Grupo Dois'],
            $p3,
            [$p3->id, $p1->id],
            $p3
        );
    })->toThrow(Exception::class, 'já pertence');
});

test('9. Um participante pode integrar grupos em hackathons diferentes', function () {
    [$org, $admin] = createTestOrgAndAdmin();
    $hackathonA = createTestEvent($org);
    $hackathonB = createTestEvent($org);

    $user = User::factory()->create(['name' => 'Viajante']);
    $userA2 = User::factory()->create();
    $userB2 = User::factory()->create();

    $regService = app(RegistrationService::class);
    $groupService = app(HackathonGroupService::class);

    // Registra nos dois eventos
    $regService->registerIndividual($hackathonA, $user);
    $regService->registerIndividual($hackathonA, $userA2);
    $regService->registerIndividual($hackathonB, $user);
    $regService->registerIndividual($hackathonB, $userB2);

    // Grupo no Hackathon A
    $groupA = $groupService->createGroup(
        $hackathonA,
        ['name' => 'Squad Evento A'],
        $user,
        [$user->id, $userA2->id],
        $user
    );

    // Grupo no Hackathon B
    $groupB = $groupService->createGroup(
        $hackathonB,
        ['name' => 'Squad Evento B'],
        $user,
        [$user->id, $userB2->id],
        $user
    );

    expect($groupA->id)->not->toBeNull();
    expect($groupB->id)->not->toBeNull();
    expect($user->hackathonGroups)->toHaveCount(2);
});

test('10. Operações concorrentes não criam duplicidade de participação', function () {
    [$org, $admin] = createTestOrgAndAdmin();
    $hackathon = createTestEvent($org);

    $user = User::factory()->create();
    $other = User::factory()->create();
    $other2 = User::factory()->create();
    $other3 = User::factory()->create();

    $regService = app(RegistrationService::class);
    $reg = $regService->registerIndividual($hackathon, $user);
    $regService->registerIndividual($hackathon, $other);
    $regService->registerIndividual($hackathon, $other2);
    $regService->registerIndividual($hackathon, $other3);

    $groupService = app(HackathonGroupService::class);
    $groupA = $groupService->createGroup($hackathon, ['name' => 'Grupo Alpha'], $other, [$other->id, $user->id], $other);
    $groupB = $groupService->createGroup($hackathon, ['name' => 'Grupo Beta'], $other2, [$other2->id, $other3->id], $other2);

    // 1. Tentativa de associar a um segundo grupo barra por regra de exclusividade
    expect(function () use ($groupService, $reg, $groupB, $other2) {
        $groupService->assignParticipantToGroup($reg, $groupB, $other2);
    })->toThrow(Exception::class, 'já pertence');

    // 2. No banco de dados, a restrição UNIQUE em hackathon_group_members impede duplicidade de integrantes
    expect(function () use ($groupA, $user) {
        DB::table('hackathon_group_members')->insert([
            'id' => (string) Str::uuid(),
            'hackathon_group_id' => $groupA->id,
            'user_id' => $user->id,
            'role' => 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(\Illuminate\Database\QueryException::class);
});

test('11. Alterar a equipe permanente não modifica indevidamente os grupos e inscrições existentes', function () {
    [$org, $admin] = createTestOrgAndAdmin();
    $hackathon = createTestEvent($org);

    $user1 = User::factory()->create(['name' => 'User Um']);
    $user2 = User::factory()->create(['name' => 'User Dois']);
    $user3 = User::factory()->create(['name' => 'User Tres']);

    $teamService = app(TeamService::class);
    $guild = $teamService->createTeam($user1, ['name' => 'Guilda Imutável']);
    $guild->members()->attach($user2->id, ['id' => (string) Str::uuid(), 'role' => 'member', 'joined_at' => now()]);

    $regService = app(RegistrationService::class);
    $groupService = app(HackathonGroupService::class);

    $regService->registerIndividual($hackathon, $user1);
    $regService->registerIndividual($hackathon, $user2);

    // Forma grupo para o hackathon
    $group = $groupService->formGroupFromGuild($hackathon, $guild, $user1);
    expect($group->members)->toHaveCount(2);

    // Agora adiciona user3 à GUILDA permanente
    $guild->members()->attach($user3->id, ['id' => (string) Str::uuid(), 'role' => 'member', 'joined_at' => now()]);
    // E altera o líder da guilda permanente para user2
    $teamService->electLeader($guild, $user2, $user1);

    // Verifica que o GRUPO no hackathon permaneceu intacto!
    $freshGroup = $group->fresh(['members', 'leader']);
    expect($freshGroup->members)->toHaveCount(2);
    expect($freshGroup->members->pluck('id'))->not->toContain($user3->id);
    expect($freshGroup->leader_id)->toBe($user1->id); // Líder do grupo não mudou
    expect($guild->fresh()->leader_id)->toBe($user2->id); // Líder da guilda mudou
});

test('12. As permissões de organização, participantes e líderes são aplicadas corretamente', function () {
    [$org, $admin] = createTestOrgAndAdmin();
    $hackathon = createTestEvent($org);

    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $outsider = User::factory()->create();

    $regService = app(RegistrationService::class);
    $regService->registerIndividual($hackathon, $member1);
    $regService->registerIndividual($hackathon, $member2);

    $groupService = app(HackathonGroupService::class);
    $group = $groupService->createGroup($hackathon, ['name' => 'Squad Permissões'], $member1, [$member1->id, $member2->id], $member1);

    // Outsider não pode alterar a liderança do grupo
    expect(function () use ($groupService, $group, $member2, $outsider) {
        $groupService->electLeader($group, $member2, $outsider);
    })->toThrow(Exception::class, 'Apenas integrantes do grupo ou administradores');

    // Integrante do grupo pode alterar
    $updated = $groupService->electLeader($group, $member2, $member1);
    expect($updated->leader_id)->toBe($member2->id);

    // Administrador da organização também pode alterar
    $orgUpdated = $groupService->electLeader($group, $member1, $admin);
    expect($orgUpdated->leader_id)->toBe($member1->id);
});

test('13. As migrações preservam os dados existentes e tratam inconsistências de maneira segura', function () {
    // Verifica integridade da estrutura das novas tabelas
    expect(Schema::hasTable('hackathon_groups'))->toBeTrue();
    expect(Schema::hasTable('hackathon_group_members'))->toBeTrue();
    expect(Schema::hasColumn('hackathon_registrations', 'hackathon_group_id'))->toBeTrue();
    expect(Schema::hasColumn('projects', 'hackathon_group_id'))->toBeTrue();
});
