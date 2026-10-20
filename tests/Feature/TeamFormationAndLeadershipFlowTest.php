<?php

namespace Tests\Feature;

use App\Models\Hackathon;
use App\Models\HackathonRegistration;
use App\Models\Organization;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\RegistrationService;
use App\Services\TeamService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createTestSetup(): array
{
    $owner = User::factory()->create();
    $org = Organization::create([
        'id' => (string) Str::uuid(),
        'owner_id' => $owner->id,
        'name' => 'Instituição Teste',
        'slug' => 'instituicao-teste-' . Str::random(5),
    ]);

    $org->users()->attach($owner->id, ['role' => 'admin', 'is_active' => true]);

    return [$org, $owner];
}

function createEvent(Organization $org, array $overrides = []): Hackathon
{
    return Hackathon::create(array_merge([
        'id' => (string) Str::uuid(),
        'organization_id' => $org->id,
        'title' => 'Hackathon Inovação ' . Str::random(5),
        'slug' => 'hackathon-' . Str::random(8),
        'modality' => 'online',
        'status' => 'registration',
        'registration_type' => 'individual_or_team',
        'team_formation_mode' => 'by_participants',
        'team_leader_required' => true,
        'allow_team_creation' => true,
        'allow_team_invites' => true,
        'allow_team_requests' => true,
        'allow_org_team_management' => true,
        'min_team_members' => 2,
        'max_team_members' => 4,
        'max_teams' => 20,
        'max_participants' => 100,
        'auto_approve_registrations' => true,
        'start_date' => now()->addDays(2),
        'end_date' => now()->addDays(5),
        'registration_start_date' => now()->subDay(),
        'registration_end_date' => now()->addDay(),
    ], $overrides));
}

test('1. Inscrição individual em hackathons individuais', function () {
    [$org, $owner] = createTestSetup();
    $hackathon = createEvent($org, ['registration_type' => 'individual']);
    $user = User::factory()->create();

    $regService = app(RegistrationService::class);
    $reg = $regService->registerIndividual($hackathon, $user);

    expect($reg)->not->toBeNull();
    expect($reg->user_id)->toBe($user->id);
    expect($reg->hackathon_id)->toBe($hackathon->id);
    expect($reg->participation_type)->toBe('individual');
    expect($reg->is_eligible)->toBeTrue();
    expect($reg->team_id)->toBeNull();
});

test('2. Inscrição de equipes em hackathons por equipe', function () {
    [$org, $owner] = createTestSetup();
    $hackathon = createEvent($org, [
        'registration_type' => 'team_only',
        'min_team_members' => 2,
        'max_team_members' => 4,
    ]);

    $leader = User::factory()->create();
    $member = User::factory()->create();

    $team = Team::create([
        'id' => (string) Str::uuid(),
        'name' => 'Team Rocket',
        'slug' => 'team-rocket',
        'leader_id' => $leader->id,
    ]);
    $team->members()->attach($leader->id, ['id' => (string) Str::uuid(), 'role' => 'leader']);
    $team->members()->attach($member->id, ['id' => (string) Str::uuid(), 'role' => 'member']);

    $regService = app(RegistrationService::class);
    $reg = $regService->registerTeam($hackathon, $team, $leader);

    expect($reg->team_id)->toBe($team->id);
    expect($team->fresh()->hackathon_id)->toBe($hackathon->id);
    expect(HackathonRegistration::where('hackathon_id', $hackathon->id)->count())->toBe(2);
});

test('3. Criação de equipes pela organização com participantes elegíveis', function () {
    [$org, $owner] = createTestSetup();
    $hackathon = createEvent($org, [
        'registration_type' => 'team_only',
        'team_formation_mode' => 'by_organization',
        'min_team_members' => 2,
        'max_team_members' => 4,
    ]);

    $regService = app(RegistrationService::class);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $reg1 = $regService->registerIndividual($hackathon, $user1);
    $reg2 = $regService->registerIndividual($hackathon, $user2);

    $response = $this->actingAs($owner)
        ->post(route('organization.hackathon.participants.create_team', $hackathon->id), [
            'name' => 'Equipe Montada Pela Org',
            'member_registration_ids' => [$reg1->id, $reg2->id],
            'leader_id' => $user1->id,
        ]);

    $response->assertSessionHasNoErrors();
    $team = Team::where('name', 'Equipe Montada Pela Org')->first();
    expect($team)->not->toBeNull();
    expect($team->leader_id)->toBe($user1->id);
    expect($team->members)->toHaveCount(2);
    expect($reg1->fresh()->team_id)->toBe($team->id);
    expect($reg2->fresh()->team_id)->toBe($team->id);
});

test('4. Impedimento de adição de participantes de outro hackathon', function () {
    [$org, $owner] = createTestSetup();
    $hackathonA = createEvent($org);
    $hackathonB = createEvent($org);

    $regService = app(RegistrationService::class);
    $userOtherHackathon = User::factory()->create();
    $regB = $regService->registerIndividual($hackathonB, $userOtherHackathon);

    $teamA = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathonA->id,
        'name' => 'Team Evento A',
        'slug' => 'team-evento-a',
    ]);

    // Tentar atribuir participante com inscrição de outro hackathon
    expect(function () use ($regService, $regB, $teamA, $owner) {
        $regService->assignParticipantToTeam($regB, $teamA, $owner);
    })->toThrow(Exception::class);
});

test('5. Impedimento de adição de participante em múltiplas equipes no mesmo evento (exclusividade absoluta)', function () {
    [$org, $owner] = createTestSetup();
    $hackathon = createEvent($org);
    $regService = app(RegistrationService::class);

    $user = User::factory()->create();
    $reg = $regService->registerIndividual($hackathon, $user);

    $team1 = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Equipe Primeira',
        'slug' => 'equipe-primeira',
    ]);
    $team2 = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Equipe Segunda',
        'slug' => 'equipe-segunda',
    ]);

    // Vincula à equipe 1
    $regService->assignParticipantToTeam($reg, $team1, $owner);
    expect($team1->members()->where('users.id', $user->id)->exists())->toBeTrue();

    // Tenta vincular o mesmo participante à equipe 2 sem desvincular da equipe 1
    expect(function () use ($regService, $hackathon, $user, $team2) {
        $regService->ensureUserNotInAnotherTeam($hackathon->id, $user->id, $team2->id);
    })->toThrow(Exception::class);
});

test('6. Criação de equipe independente na área do participante e convite por e-mail', function () {
    $creator = User::factory()->create();
    $inviteEmail = 'futuro_parceiro@exemplo.com';

    $teamService = app(TeamService::class);
    $team = $teamService->createTeam($creator, [
        'name' => 'Equipe Independente Alpha',
        'description' => 'Trabalhando em side-projects',
        'invite_emails' => $inviteEmail,
    ]);

    expect($team)->not->toBeNull();
    expect($team->hackathon_id)->toBeNull();
    expect($team->leader_id)->toBe($creator->id);
    expect($team->members)->toHaveCount(1);

    // Convite pendente registrado para o e-mail
    $invitation = TeamInvitation::where('team_id', $team->id)->where('email', $inviteEmail)->first();
    expect($invitation)->not->toBeNull();
    expect($invitation->status)->toBe('pending');
    expect($invitation->invitee_id)->toBeNull();
});

test('7. Associação de convite aceito ao usuário existente sem duplicação de conta', function () {
    $creator = User::factory()->create();
    $teamService = app(TeamService::class);

    $team = $teamService->createTeam($creator, [
        'name' => 'Inovadores Independentes',
    ]);

    $existingInvitee = User::factory()->create(['email' => 'colaborador@empresa.com']);

    // Convite enviado usando o e-mail
    $invitation = $teamService->inviteMember($team, $creator, 'colaborador@empresa.com');
    expect($invitation->invitee_id)->toBe($existingInvitee->id);

    // O convidado aceita o convite
    $teamService->acceptInvitation($invitation, $existingInvitee);

    expect($team->fresh()->members)->toHaveCount(2);
    expect($invitation->fresh()->status)->toBe('accepted');
    expect(User::where('email', 'colaborador@empresa.com')->count())->toBe(1);
});

test('8. Bloqueio de finalização de inscrição da equipe caso falte líder e o evento exija essa condição', function () {
    [$org, $owner] = createTestSetup();
    $hackathon = createEvent($org, [
        'registration_type' => 'team_only',
        'team_leader_required' => true,
        'min_team_members' => 2,
    ]);

    $member1 = User::factory()->create();
    $member2 = User::factory()->create();

    // Equipe preparada inicialmente sem líder
    $team = Team::create([
        'id' => (string) Str::uuid(),
        'name' => 'Equipe Sem Líder',
        'slug' => 'equipe-sem-lider',
        'leader_id' => null,
    ]);
    $team->members()->attach($member1->id, ['id' => (string) Str::uuid(), 'role' => 'member']);
    $team->members()->attach($member2->id, ['id' => (string) Str::uuid(), 'role' => 'member']);

    $regService = app(RegistrationService::class);

    expect(function () use ($regService, $hackathon, $team, $member1) {
        $regService->registerTeam($hackathon, $team, $member1);
    })->toThrow(Exception::class, 'Este Hackathon exige a definição de um líder');

    // Ao eleger um líder, a inscrição é autorizada
    $teamService = app(TeamService::class);
    $teamService->electLeader($team, $member1, $member1);
    expect($team->fresh()->leader_id)->toBe($member1->id);

    $reg = $regService->registerTeam($hackathon, $team->fresh(), $member1);
    expect($reg)->not->toBeNull();
});

test('9. Escolha livre de líder entre os integrantes de uma equipe sem impor o criador', function () {
    $creator = User::factory()->create();
    $peer = User::factory()->create();

    $teamService = app(TeamService::class);

    // Criador opta por não se colocar como líder na criação
    $team = $teamService->createTeam($creator, [
        'name' => 'Equipe Democrática',
        'set_as_leader' => false,
    ]);

    expect($team->leader_id)->toBeNull();
    expect($team->members()->first()->pivot->role)->toBe('member');

    // Adiciona peer como membro
    $team->members()->attach($peer->id, ['id' => (string) Str::uuid(), 'role' => 'member']);

    // Grupo elege o peer como líder
    $teamService->electLeader($team, $peer, $creator);

    expect($team->fresh()->leader_id)->toBe($peer->id);
    expect($team->members()->where('users.id', $peer->id)->first()->pivot->role)->toBe('leader');
    expect($team->members()->where('users.id', $creator->id)->first()->pivot->role)->toBe('member');
});

test('10. Substituição de líder da equipe', function () {
    $leaderA = User::factory()->create();
    $memberB = User::factory()->create();

    $teamService = app(TeamService::class);
    $team = $teamService->createTeam($leaderA, [
        'name' => 'Equipe Mutável',
    ]);
    $team->members()->attach($memberB->id, ['id' => (string) Str::uuid(), 'role' => 'member']);

    expect($team->leader_id)->toBe($leaderA->id);

    // Substituição de liderança de A para B
    $teamService->electLeader($team, $memberB, $leaderA);

    expect($team->fresh()->leader_id)->toBe($memberB->id);
    expect($team->members()->where('users.id', $memberB->id)->first()->pivot->role)->toBe('leader');
    expect($team->members()->where('users.id', $leaderA->id)->first()->pivot->role)->toBe('member');
});

test('11. Organização deixando a liderança da equipe sem definição inicial', function () {
    [$org, $owner] = createTestSetup();
    $hackathon = createEvent($org, [
        'registration_type' => 'team_only',
        'min_team_members' => 2,
    ]);

    $regService = app(RegistrationService::class);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $reg1 = $regService->registerIndividual($hackathon, $user1);
    $reg2 = $regService->registerIndividual($hackathon, $user2);

    $team = $regService->createTeamForHackathon(
        $hackathon,
        null,
        ['name' => 'Equipe Org Sem Líder'],
        $owner,
        [$user1->id, $user2->id],
        null // Sem líder inicial
    );

    expect($team->leader_id)->toBeNull();
    expect($team->members)->toHaveCount(2);
    expect($team->members->every(fn($m) => $m->pivot->role === 'member'))->toBeTrue();
});

test('12. Tentativa de nomear como líder alguém que não seja integrante da equipe (deve falhar)', function () {
    $creator = User::factory()->create();
    $stranger = User::factory()->create();

    $teamService = app(TeamService::class);
    $team = $teamService->createTeam($creator, ['name' => 'Equipe Fechada']);

    expect(function () use ($teamService, $team, $stranger, $creator) {
        $teamService->electLeader($team, $stranger, $creator);
    })->toThrow(Exception::class, 'O líder designado deve ser um integrante da equipe');
});

test('13. Respeito aos limites mínimos e máximos de integrantes', function () {
    [$org, $owner] = createTestSetup();
    $hackathon = createEvent($org, [
        'registration_type' => 'team_only',
        'min_team_members' => 3,
        'max_team_members' => 4,
    ]);

    $regService = app(RegistrationService::class);
    $users = User::factory()->count(5)->create();

    // Tentativa com menos integrantes que o mínimo (2 integrantes)
    expect(function () use ($regService, $hackathon, $owner, $users) {
        $regService->createTeamForHackathon(
            $hackathon,
            null,
            ['name' => 'Equipe Pequena'],
            $owner,
            [$users[0]->id, $users[1]->id]
        );
    })->toThrow(Exception::class, 'A equipe deve ter no mínimo 3 integrantes');

    // Tentativa com mais integrantes que o máximo (5 integrantes)
    expect(function () use ($regService, $hackathon, $owner, $users) {
        $regService->createTeamForHackathon(
            $hackathon,
            null,
            ['name' => 'Equipe Gigante'],
            $owner,
            $users->pluck('id')->toArray()
        );
    })->toThrow(Exception::class, 'A equipe excede o limite máximo de 4 integrantes');
});

test('14. Isolamento adequado dos relacionamentos no banco de dados', function () {
    [$org, $owner] = createTestSetup();
    $hackathon = createEvent($org);

    $user = User::factory()->create();
    $teamService = app(TeamService::class);

    // Criação de equipe independente
    $team = $teamService->createTeam($user, ['name' => 'Equipe Isolada']);

    // A equipe independente NÃO deve criar registros espúrios em hackathons
    expect($team->hackathon_id)->toBeNull();
    expect(HackathonRegistration::where('team_id', $team->id)->count())->toBe(0);
    expect(HackathonRegistration::where('user_id', $user->id)->count())->toBe(0);

    // Integridade relacional preservada
    expect($team->members)->toHaveCount(1);
    expect($team->leader_id)->toBe($user->id);
    expect($user->teams)->toHaveCount(1);
});
