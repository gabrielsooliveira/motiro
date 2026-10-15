<?php

use App\Models\Hackathon;
use App\Models\HackathonRegistration;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\RegistrationService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function createTestOrgAndOwner(): array
{
    $owner = User::factory()->create();
    $org = Organization::create([
        'id' => (string) Str::uuid(),
        'name' => 'Instituto de Inovação',
        'email' => 'contato@inovacao.org',
        'slug' => 'instituto-inovacao-' . Str::random(6),
        'password' => bcrypt('password123'),
        'owner_id' => $owner->id,
        'is_active' => true,
    ]);
    \App\Models\OrganizationUser::create([
        'id' => (string) Str::uuid(),
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'role' => 'admin',
        'is_active' => true,
    ]);

    return [$org, $owner];
}

function createTestHackathon(Organization $org, array $overrides = []): Hackathon
{
    return Hackathon::create(array_merge([
        'id' => (string) Str::uuid(),
        'organization_id' => $org->id,
        'title' => 'Hackathon Flexível ' . Str::random(4),
        'slug' => 'hackathon-flexivel-' . Str::random(6),
        'status' => 'registration',
        'registration_type' => 'individual_or_team',
        'min_team_members' => 1,
        'max_team_members' => 4,
        'allow_team_creation' => true,
        'allow_team_invites' => true,
        'allow_team_requests' => true,
        'allow_org_team_management' => true,
        'auto_approve_registrations' => true,
        'registration_start_date' => now()->subDay(),
        'registration_end_date' => now()->addWeek(),
        'submission_start_date' => now()->subDay(),
        'submission_end_date' => now()->addWeek(),
    ], $overrides));
}

test('1. Inscrição individual em hackathon configurado para participação individual', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'individual',
        'auto_approve_registrations' => true,
    ]);

    $competitor = User::factory()->create();

    $response = $this->actingAs($competitor)
        ->post(route('registration.store'), [
            'hackathon_id' => $hackathon->id,
            'type' => 'individual',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->assertDatabaseHas('hackathon_registrations', [
        'hackathon_id' => $hackathon->id,
        'user_id' => $competitor->id,
        'team_id' => null,
        'participation_type' => 'individual',
        'is_eligible' => 1,
        'status' => 'approved',
    ]);
});

test('2. Inscrição individual em hackathon configurado para participação mista (individual ou equipe)', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'individual_or_team',
        'auto_approve_registrations' => true,
    ]);

    $competitor = User::factory()->create();

    $response = $this->actingAs($competitor)
        ->post(route('registration.store'), [
            'hackathon_id' => $hackathon->id,
            'type' => 'individual',
        ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('hackathon_registrations', [
        'hackathon_id' => $hackathon->id,
        'user_id' => $competitor->id,
        'team_id' => null,
        'participation_type' => 'individual',
        'is_eligible' => 1,
    ]);
});

test('3. Tentativa de inscrição individual em hackathon exclusivo por equipe registra como pendente de equipe e não elegível', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'team_only',
        'min_team_members' => 2,
    ]);

    $competitor = User::factory()->create();

    $response = $this->actingAs($competitor)
        ->post(route('registration.store'), [
            'hackathon_id' => $hackathon->id,
            'type' => 'individual',
        ]);

    $response->assertSessionHasNoErrors();

    $registration = HackathonRegistration::where('hackathon_id', $hackathon->id)
        ->where('user_id', $competitor->id)
        ->first();

    expect($registration)->not->toBeNull();
    expect($registration->participation_type)->toBe('individual');
    expect($registration->team_id)->toBeNull();
    expect($registration->is_eligible)->toBeFalse();
    expect($registration->notes)->toContain('Aguardando');
});

test('4. Criação de equipe por participante quando a funcionalidade estiver habilitada', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'team_only',
        'allow_team_creation' => true,
    ]);

    $competitor = User::factory()->create();

    $response = $this->actingAs($competitor)
        ->post(route('team.store'), [
            'hackathon_id' => $hackathon->id,
            'name' => 'Equipe Beta Inovadora',
            'description' => 'Desenvolvendo soluções em IA',
        ]);

    $response->assertSessionHasNoErrors();

    $team = Team::where('name', 'Equipe Beta Inovadora')->first();
    expect($team)->not->toBeNull();
    expect($team->leader_id)->toBe($competitor->id);
    expect($team->hackathon_id)->toBe($hackathon->id);

    $this->assertDatabaseHas('hackathon_registrations', [
        'hackathon_id' => $hackathon->id,
        'user_id' => $competitor->id,
        'team_id' => $team->id,
        'participation_type' => 'team',
        'is_eligible' => 1,
    ]);
});

test('5. Bloqueio de criação de equipe por participante quando a funcionalidade estiver desabilitada', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'team_only',
        'allow_team_creation' => false,
    ]);

    $competitor = User::factory()->create();

    $response = $this->actingAs($competitor)
        ->post(route('team.store'), [
            'hackathon_id' => $hackathon->id,
            'name' => 'Equipe Bloqueada',
        ]);

    $response->assertSessionHasErrors(['hackathon_id']);
    $this->assertDatabaseMissing('teams', ['name' => 'Equipe Bloqueada']);
});

test('6. Criação de equipe pela organização em nome dos participantes', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'team_only',
        'allow_org_team_management' => true,
    ]);

    $leader = User::factory()->create();
    $member = User::factory()->create();

    // Register them individually first
    $service = app(RegistrationService::class);
    $leaderReg = $service->registerIndividual($hackathon, $leader);
    $memberReg = $service->registerIndividual($hackathon, $member);

    $response = $this->actingAs($owner)
        ->post(route('organization.hackathon.participants.create_team', $hackathon->id), [
            'name' => 'Equipe Criada Pela Org',
            'leader_id' => $leader->id,
            'description' => 'Montada pelos organizadores',
            'member_registration_ids' => [$memberReg->id],
        ]);

    $response->assertSessionHasNoErrors();

    $team = Team::where('name', 'Equipe Criada Pela Org')->first();
    expect($team)->not->toBeNull();
    expect($team->leader_id)->toBe($leader->id);
    expect($team->hackathon_id)->toBe($hackathon->id);
    expect($team->members)->toHaveCount(2);

    $this->assertDatabaseHas('hackathon_registrations', [
        'id' => $leaderReg->id,
        'team_id' => $team->id,
        'is_eligible' => 1,
    ]);
    $this->assertDatabaseHas('hackathon_registrations', [
        'id' => $memberReg->id,
        'team_id' => $team->id,
        'is_eligible' => 1,
    ]);
});

test('7. Bloqueio de criação de equipes após o prazo limite', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'team_only',
        'allow_team_creation' => true,
        'team_formation_deadline' => now()->subMinutes(10),
    ]);

    $competitor = User::factory()->create();

    $response = $this->actingAs($competitor)
        ->post(route('team.store'), [
            'hackathon_id' => $hackathon->id,
            'name' => 'Equipe Atrasada',
        ]);

    $response->assertSessionHasErrors(['hackathon_id']);
    $this->assertDatabaseMissing('teams', ['name' => 'Equipe Atrasada']);
});

test('8. Envio e aceitação de convite para equipe com atualização do status de elegibilidade', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'team_only',
        'min_team_members' => 2,
        'max_team_members' => 4,
    ]);

    $leader = User::factory()->create();
    $invitee = User::factory()->create();

    $service = app(RegistrationService::class);
    $inviteeReg = $service->registerIndividual($hackathon, $invitee);
    expect($inviteeReg->is_eligible)->toBeFalse();

    // Leader creates team for hackathon
    $team = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Alpha Team',
        'slug' => 'alpha-team',
        'leader_id' => $leader->id,
    ]);
    $team->members()->attach($leader->id, [
        'id' => (string) Str::uuid(),
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    // Leader invites invitee
    $this->actingAs($leader)
        ->post(route('team.invite', $team->id), [
            'email_or_username' => $invitee->email,
        ])
        ->assertSessionHasNoErrors();

    $invitation = TeamInvitation::where('team_id', $team->id)
        ->where('invitee_id', $invitee->id)
        ->first();
    expect($invitation)->not->toBeNull();

    // Invitee accepts invite
    $this->actingAs($invitee)
        ->post(route('team.invite.accept', $invitation->id))
        ->assertSessionHasNoErrors();

    expect($team->fresh()->members)->toHaveCount(2);

    $freshInviteeReg = $inviteeReg->fresh();
    expect($freshInviteeReg->team_id)->toBe($team->id);
    expect($freshInviteeReg->is_eligible)->toBeTrue();
    expect($freshInviteeReg->participation_type)->toBe('team');
});

test('9. Solicitação de entrada em equipe e aprovação pelo líder', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'team_only',
        'allow_team_requests' => true,
        'min_team_members' => 2,
    ]);

    $leader = User::factory()->create();
    $applicant = User::factory()->create();

    $team = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Gamma Team',
        'slug' => 'gamma-team',
        'leader_id' => $leader->id,
    ]);
    $team->members()->attach($leader->id, [
        'id' => (string) Str::uuid(),
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    // Applicant sends join request
    $this->actingAs($applicant)
        ->post(route('team.request', $team->id))
        ->assertSessionHasNoErrors();

    $req = TeamInvitation::where('team_id', $team->id)
        ->where('inviter_id', $applicant->id)
        ->where('type', 'request')
        ->first();
    expect($req)->not->toBeNull();

    // Leader accepts request
    $this->actingAs($leader)
        ->post(route('team.request.accept', $req->id))
        ->assertSessionHasNoErrors();

    expect($team->fresh()->members)->toHaveCount(2);
    $this->assertDatabaseHas('hackathon_registrations', [
        'hackathon_id' => $hackathon->id,
        'user_id' => $applicant->id,
        'team_id' => $team->id,
        'is_eligible' => 1,
    ]);
});

test('10. Tentativa de adicionar membros acima do limite máximo da equipe (deve falhar)', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'team_only',
        'min_team_members' => 1,
        'max_team_members' => 2,
    ]);

    $leader = User::factory()->create();
    $member1 = User::factory()->create();
    $extraUser = User::factory()->create();

    $team = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Max Limit Team',
        'slug' => 'max-limit-team',
        'leader_id' => $leader->id,
    ]);
    $team->members()->attach([
        $leader->id => ['id' => (string) Str::uuid(), 'role' => 'leader', 'joined_at' => now()],
        $member1->id => ['id' => (string) Str::uuid(), 'role' => 'member', 'joined_at' => now()],
    ]);

    // Team has 2 members, which equals max_team_members (2)
    expect($team->hasAvailableSlots())->toBeFalse();

    // Attempt to invite 3rd member
    $response = $this->actingAs($leader)
        ->post(route('team.invite', $team->id), [
            'email_or_username' => $extraUser->email,
        ]);

    $response->assertSessionHasErrors(['email_or_username']);
});

test('11. Verificação de elegibilidade de equipe com número de membros abaixo do mínimo', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'team_only',
        'min_team_members' => 3,
        'max_team_members' => 5,
    ]);

    $leader = User::factory()->create();
    $team = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Under Minimum Team',
        'slug' => 'under-min-team',
        'leader_id' => $leader->id,
    ]);
    $team->members()->attach($leader->id, [
        'id' => (string) Str::uuid(),
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    // Has 1 member, but min is 3
    expect($team->hasMinimumMembers())->toBeFalse();
    expect($team->hasAvailableSlots())->toBeTrue();
});

test('12. Alocação de participante individual em equipe pela organização', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'individual_or_team',
        'allow_org_team_management' => true,
    ]);

    $competitor = User::factory()->create();
    $leader = User::factory()->create();

    $service = app(RegistrationService::class);
    $reg = $service->registerIndividual($hackathon, $competitor);

    $team = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Target Team',
        'slug' => 'target-team',
        'leader_id' => $leader->id,
    ]);
    $team->members()->attach($leader->id, ['id' => (string) Str::uuid(), 'role' => 'leader']);

    $response = $this->actingAs($owner)
        ->post(route('organization.hackathon.participants.assign', $hackathon->id), [
            'registration_id' => $reg->id,
            'team_id' => $team->id,
        ]);

    $response->assertSessionHasNoErrors();

    $freshReg = $reg->fresh();
    expect($freshReg->team_id)->toBe($team->id);
    expect($freshReg->participation_type)->toBe('team');
    expect($freshReg->is_eligible)->toBeTrue();
    expect($team->fresh()->members)->toHaveCount(2);
});

test('13. Transferência de participante entre equipes pela organização', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'team_only',
        'allow_org_team_management' => true,
    ]);

    $leader1 = User::factory()->create();
    $leader2 = User::factory()->create();
    $member = User::factory()->create();

    $teamA = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Equipe Origem',
        'slug' => 'origem',
        'leader_id' => $leader1->id,
    ]);
    $teamA->members()->attach([
        $leader1->id => ['id' => (string) Str::uuid(), 'role' => 'leader'],
        $member->id => ['id' => (string) Str::uuid(), 'role' => 'member'],
    ]);

    $teamB = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Equipe Destino',
        'slug' => 'destino',
        'leader_id' => $leader2->id,
    ]);
    $teamB->members()->attach($leader2->id, ['id' => (string) Str::uuid(), 'role' => 'leader']);

    $reg = HackathonRegistration::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'user_id' => $member->id,
        'team_id' => $teamA->id,
        'participation_type' => 'team',
        'status' => 'approved',
        'is_eligible' => true,
    ]);

    $response = $this->actingAs($owner)
        ->post(route('organization.hackathon.participants.transfer', $hackathon->id), [
            'registration_id' => $reg->id,
            'target_team_id' => $teamB->id,
        ]);

    $response->assertSessionHasNoErrors();

    expect($teamA->fresh()->members)->toHaveCount(1);
    expect($teamB->fresh()->members)->toHaveCount(2);
    expect($reg->fresh()->team_id)->toBe($teamB->id);
});

test('14. Remoção de participante de equipe com retorno ao status individual', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'individual_or_team',
        'allow_org_team_management' => true,
    ]);

    $leader = User::factory()->create();
    $member = User::factory()->create();

    $team = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Equipe Para Remover',
        'slug' => 'equipe-remover',
        'leader_id' => $leader->id,
    ]);
    $team->members()->attach([
        $leader->id => ['id' => (string) Str::uuid(), 'role' => 'leader'],
        $member->id => ['id' => (string) Str::uuid(), 'role' => 'member'],
    ]);

    $reg = HackathonRegistration::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'user_id' => $member->id,
        'team_id' => $team->id,
        'participation_type' => 'team',
        'status' => 'approved',
        'is_eligible' => true,
    ]);

    $response = $this->actingAs($owner)
        ->delete(route('organization.hackathon.participants.remove_team', [$hackathon->id, $reg->id]));

    $response->assertSessionHasNoErrors();

    expect($team->fresh()->members)->toHaveCount(1);
    $freshReg = $reg->fresh();
    expect($freshReg->team_id)->toBeNull();
    expect($freshReg->participation_type)->toBe('individual');
});

test('15. Submissão de projeto por participante individual em hackathon individual', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'individual',
    ]);

    $competitor = User::factory()->create();
    $service = app(RegistrationService::class);
    $service->registerIndividual($hackathon, $competitor);

    $response = $this->actingAs($competitor)
        ->post(route('project.submit'), [
            'hackathon_id' => $hackathon->id,
            'title' => 'Projeto Solo Incrível',
            'short_description' => 'Solução individual desenvolvida para o desafio',
            'github_repository_url' => 'https://github.com/competitor/solo-project',
        ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('projects', [
        'hackathon_id' => $hackathon->id,
        'user_id' => $competitor->id,
        'team_id' => null,
        'title' => 'Projeto Solo Incrível',
    ]);
});

test('16. Submissão de projeto por equipe em hackathon por equipe', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'team_only',
    ]);

    $leader = User::factory()->create();
    $team = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Equipe Triunfo',
        'slug' => 'equipe-triunfo',
        'leader_id' => $leader->id,
    ]);
    $team->members()->attach($leader->id, ['id' => (string) Str::uuid(), 'role' => 'leader']);

    $response = $this->actingAs($leader)
        ->post(route('project.submit'), [
            'hackathon_id' => $hackathon->id,
            'team_id' => $team->id,
            'title' => 'Projeto Coletivo da Equipe',
            'short_description' => 'Solução em grupo',
            'github_repository_url' => 'https://github.com/team/triunfo-project',
        ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('projects', [
        'hackathon_id' => $hackathon->id,
        'team_id' => $team->id,
        'title' => 'Projeto Coletivo da Equipe',
    ]);
});

test('17. Bloqueio de submissão individual em hackathon exclusivo por equipe', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'team_only',
    ]);

    $competitor = User::factory()->create();
    $service = app(RegistrationService::class);
    $service->registerIndividual($hackathon, $competitor);

    $response = $this->actingAs($competitor)
        ->post(route('project.submit'), [
            'hackathon_id' => $hackathon->id,
            'title' => 'Tentativa Solo em Hackathon de Equipe',
        ]);

    $response->assertSessionHasErrors(['project']);
    $this->assertDatabaseMissing('projects', ['title' => 'Tentativa Solo em Hackathon de Equipe']);
});

test('18. Tentativa de inscrição duplicada do mesmo usuário no mesmo hackathon (deve falhar)', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'individual_or_team',
    ]);

    $competitor = User::factory()->create();

    // First registration succeeds
    $this->actingAs($competitor)
        ->post(route('registration.store'), [
            'hackathon_id' => $hackathon->id,
            'type' => 'individual',
        ])
        ->assertSessionHasNoErrors();

    // Second registration fails
    $response = $this->actingAs($competitor)
        ->post(route('registration.store'), [
            'hackathon_id' => $hackathon->id,
            'type' => 'individual',
        ]);

    $response->assertSessionHasErrors(['registration']);
    expect(HackathonRegistration::where('hackathon_id', $hackathon->id)->where('user_id', $competitor->id)->count())->toBe(1);
});

test('19. Participação do mesmo usuário em múltiplos hackathons diferentes com equipes diferentes', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $h1 = createTestHackathon($org, ['title' => 'Hackathon 1']);
    $h2 = createTestHackathon($org, ['title' => 'Hackathon 2']);

    $user = User::factory()->create();

    $team1 = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $h1->id,
        'name' => 'Team H1',
        'slug' => 'team-h1',
        'leader_id' => $user->id,
    ]);
    $team1->members()->attach($user->id, ['id' => (string) Str::uuid(), 'role' => 'leader']);

    $team2 = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $h2->id,
        'name' => 'Team H2',
        'slug' => 'team-h2',
        'leader_id' => $user->id,
    ]);
    $team2->members()->attach($user->id, ['id' => (string) Str::uuid(), 'role' => 'leader']);

    HackathonRegistration::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $h1->id,
        'user_id' => $user->id,
        'team_id' => $team1->id,
        'participation_type' => 'team',
        'status' => 'approved',
        'is_eligible' => true,
    ]);

    HackathonRegistration::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $h2->id,
        'user_id' => $user->id,
        'team_id' => $team2->id,
        'participation_type' => 'team',
        'status' => 'approved',
        'is_eligible' => true,
    ]);

    expect(HackathonRegistration::where('user_id', $user->id)->count())->toBe(2);
    expect($user->registrations()->where('hackathon_id', $h1->id)->first()->team->name)->toBe('Team H1');
    expect($user->registrations()->where('hackathon_id', $h2->id)->first()->team->name)->toBe('Team H2');
});

test('20. Isolamento de equipes entre hackathons diferentes', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $h1 = createTestHackathon($org, ['title' => 'Hackathon Alpha']);
    $h2 = createTestHackathon($org, ['title' => 'Hackathon Beta']);

    $leader = User::factory()->create();
    $competitor = User::factory()->create();

    $teamInH1 = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $h1->id,
        'name' => 'Team Exclusively H1',
        'slug' => 'team-h1-exclusive',
        'leader_id' => $leader->id,
    ]);
    $teamInH1->members()->attach($leader->id, ['id' => (string) Str::uuid(), 'role' => 'leader']);

    $service = app(RegistrationService::class);
    $regH2 = $service->registerIndividual($h2, $competitor);

    // Attempting to assign Team from H1 to a participant registered in H2 must be rejected
    $response = $this->actingAs($owner)
        ->post(route('organization.hackathon.participants.assign', $h2->id), [
            'registration_id' => $regH2->id,
            'team_id' => $teamInH1->id,
        ]);

    $response->assertForbidden();
    expect($regH2->fresh()->team_id)->toBeNull();
});

test('21. Preservação de integridade de dados ao alterar a modalidade do hackathon', function () {
    [$org, $owner] = createTestOrgAndOwner();
    $hackathon = createTestHackathon($org, [
        'registration_type' => 'individual_or_team',
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $service = app(RegistrationService::class);
    $regA = $service->registerIndividual($hackathon, $userA);

    $team = Team::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Equipe Resistente',
        'slug' => 'equipe-resistente',
        'leader_id' => $userB->id,
    ]);
    $team->members()->attach($userB->id, ['id' => (string) Str::uuid(), 'role' => 'leader']);

    $regB = HackathonRegistration::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'user_id' => $userB->id,
        'team_id' => $team->id,
        'participation_type' => 'team',
        'status' => 'approved',
        'is_eligible' => true,
    ]);

    // Update hackathon format via organization dashboard
    $response = $this->actingAs($owner)
        ->put(route('hackathon.manage.update', $hackathon->id), [
            'registration_type' => 'team_only',
            'min_team_members' => 2,
            'max_team_members' => 6,
            'allow_team_creation' => true,
            'allow_team_invites' => true,
            'allow_team_requests' => true,
            'allow_org_team_management' => true,
        ]);

    $response->assertSessionHasNoErrors();

    $freshHackathon = $hackathon->fresh();
    expect($freshHackathon->registration_type)->toBe('team_only');
    expect($freshHackathon->max_team_members)->toBe(6);

    // Existing registrations and teams are preserved
    expect(HackathonRegistration::where('hackathon_id', $hackathon->id)->count())->toBe(2);
    expect($team->fresh()->members)->toHaveCount(1);
    expect($regA->fresh()->id)->toBe($regA->id);
    expect($regB->fresh()->team_id)->toBe($team->id);
});
