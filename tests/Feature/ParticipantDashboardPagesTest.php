<?php

use App\Models\Hackathon;
use App\Models\HackathonGroup;
use App\Models\HackathonRegistration;
use App\Models\Organization;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function createTestParticipant(): User
{
    return User::factory()->create();
}

function createOrgWithHackathon(): array
{
    $orgOwner = User::factory()->create();
    $org = Organization::create([
        'id' => (string) Str::uuid(),
        'name' => 'Tech Corp',
        'slug' => 'tech-corp',
        'owner_id' => $orgOwner->id,
        'status' => 'approved',
    ]);

    $hackathon = Hackathon::create([
        'id' => (string) Str::uuid(),
        'organization_id' => $org->id,
        'title' => 'AI Revolution 2026',
        'slug' => 'ai-revolution-2026',
        'registration_type' => 'both',
        'status' => 'registration',
        'modality' => 'online',
    ]);

    return [$org, $hackathon];
}

test('1. Visitante não autenticado é redirecionado para o login', function () {
    $this->get(route('participant.dashboard'))->assertRedirect(route('participant.login'));
    $this->get(route('participant.teams.index'))->assertRedirect(route('participant.login'));
    $this->get(route('participant.groups.index'))->assertRedirect(route('participant.login'));
    $this->get(route('participant.my_hackathons'))->assertRedirect(route('participant.login'));
});

test('2. Tela inicial de Meu Painel renderiza componente Home com resumo de métricas', function () {
    $user = createTestParticipant();

    $response = $this->actingAs($user)
        ->get(route('participant.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Participant/Dashboard/Home')
        ->has('user')
        ->has('competitor')
        ->has('judge')
        ->has('notifications')
    );
});

test('3. Aba Minhas Equipes renderiza página Teams com guildas permanentes e convites', function () {
    $user = createTestParticipant();

    // Guilda do participante
    $team = Team::create([
        'id' => (string) Str::uuid(),
        'name' => 'ByteCraft Guild',
        'slug' => 'bytecraft-guild',
        'leader_id' => $user->id,
        'area_of_focus' => 'Fintech',
    ]);
    $team->members()->attach($user->id, ['id' => (string) Str::uuid(), 'role' => 'leader']);

    // Convite pendente para outra equipe
    $otherTeam = Team::create([
        'id' => (string) Str::uuid(),
        'name' => 'InovaTech',
        'slug' => 'inovatech',
        'leader_id' => User::factory()->create()->id,
    ]);
    TeamInvitation::create([
        'id' => (string) Str::uuid(),
        'team_id' => $otherTeam->id,
        'inviter_id' => $otherTeam->leader_id,
        'invitee_id' => $user->id,
        'email' => $user->email,
        'status' => 'pending',
        'token' => Str::random(32),
        'expires_at' => now()->addDays(7),
    ]);

    $response = $this->actingAs($user)
        ->get(route('participant.teams.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Participant/Dashboard/Teams')
        ->has('teams', 1)
        ->has('invitations', 1)
        ->where('teams.0.name', 'ByteCraft Guild')
    );
});

test('4. Aba Grupos de Hackathons renderiza página Groups com formações competitivas', function () {
    $user = createTestParticipant();
    [$org, $hackathon] = createOrgWithHackathon();

    // Inscrição no evento
    $reg = HackathonRegistration::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'user_id' => $user->id,
        'status' => 'approved',
        'is_eligible' => true,
    ]);

    // Grupo competitivo
    $group = HackathonGroup::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Esquadrão Alpha',
        'slug' => 'esquadrao-alpha',
        'leader_id' => $user->id,
        'status' => 'active',
    ]);
    $group->members()->attach($user->id, ['id' => (string) Str::uuid(), 'role' => 'leader']);
    $reg->update(['hackathon_group_id' => $group->id]);

    $response = $this->actingAs($user)
        ->get(route('participant.groups.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Participant/Dashboard/Groups')
        ->has('groups', 1)
        ->has('registrations', 1)
        ->where('groups.0.name', 'Esquadrão Alpha')
    );
});

test('5. Aba Meus Hackathons renderiza página MyHackathons com histórico de inscrições', function () {
    $user = createTestParticipant();
    [$org, $hackathon] = createOrgWithHackathon();

    HackathonRegistration::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'user_id' => $user->id,
        'participation_type' => 'individual',
        'status' => 'approved',
        'is_eligible' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('participant.my_hackathons'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Participant/Dashboard/MyHackathons')
        ->has('registrations', 1)
        ->where('registrations.0.hackathon.title', 'AI Revolution 2026')
    );
});

test('6. Rotas legadas continuam funcionando de forma transparente', function () {
    $user = createTestParticipant();

    $this->actingAs($user)->get(route('team.index'))->assertOk();
    $this->actingAs($user)->get(route('group.index'))->assertOk();
    $this->actingAs($user)->get(route('my_hackathons.explore'))->assertOk();
});
