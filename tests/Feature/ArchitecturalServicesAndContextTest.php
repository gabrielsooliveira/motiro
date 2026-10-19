<?php

use App\Models\EvaluationCriterion;
use App\Models\Hackathon;
use App\Models\HackathonStage;
use App\Models\JudgeAssignment;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\HackathonService;
use App\Services\JudgeService;
use App\Services\TeamService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login rejects invalid explicit context parameter', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create([
        'email' => 'user@test.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('participant.login.store'), [
        'email' => 'user@test.com',
        'password' => 'password123',
        'context' => 'invalid_malicious_context',
    ]);

    $response->assertSessionHasErrors('context');
    $this->assertGuest();
});

test('participant login accepts valid participant context and configures session correctly', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create([
        'email' => 'participant@test.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('participant.login.store'), [
        'email' => 'participant@test.com',
        'password' => 'password123',
        'context' => 'participant',
    ]);

    $response->assertRedirect(route('participant.dashboard'));
    $this->assertAuthenticatedAs($user);
    expect(session('current_context'))->toBe('competitor');
});

test('TeamService creates team with leader and registration atomically', function () {
    $creator = User::factory()->create();
    $service = app(TeamService::class);

    $team = $service->createTeam($creator, [
        'name' => 'Alpha Builders',
        'description' => 'Time focado em inovação',
        'area_of_focus' => 'Fintech',
        'is_looking_for_members' => true,
    ]);

    expect($team->id)->not->toBeNull();
    expect($team->leader_id)->toBe($creator->id);
    expect($team->members()->where('users.id', $creator->id)->exists())->toBeTrue();
    expect($team->members()->first()->pivot->role)->toBe('leader');
});

test('TeamService handles member invitations and limits correctly', function () {
    $leader = User::factory()->create();
    $invitee = User::factory()->create();

    $service = app(TeamService::class);
    $team = $service->createTeam($leader, ['name' => 'Beta Innovators']);

    // Send invitation
    $invitation = $service->inviteMember($team, $leader, $invitee->email);
    expect($invitation->status)->toBe('pending');
    expect($invitation->team_id)->toBe($team->id);

    // Accept invitation
    $service->acceptInvitation($invitation, $invitee);
    expect($team->members()->where('users.id', $invitee->id)->exists())->toBeTrue();
    expect($invitation->fresh()->status)->toBe('accepted');
});

test('HackathonService creates hackathon with stages and criteria with proper weights', function () {
    $owner = User::factory()->create();
    $org = Organization::create([
        'name' => 'Tech Hub',
        'slug' => 'tech-hub',
        'owner_id' => $owner->id,
        'is_active' => true,
    ]);

    OrganizationUser::create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'role' => 'admin',
        'is_active' => true,
    ]);

    $service = app(HackathonService::class);

    $hackathon = $service->createHackathon($owner, [
        'organization_id' => $org->id,
        'title' => 'InovaTech Hackathon 2026',
        'modality' => 'online',
        'min_team_members' => 2,
        'max_team_members' => 5,
        'stages' => [
            [
                'name' => 'Fase 1: Inscrições',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(7)->toDateString(),
                'order' => 1,
            ],
            [
                'name' => 'Fase 2: Pitch Final',
                'start_date' => now()->addDays(8)->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
                'order' => 2,
            ],
        ],
        'criteria' => [
            [
                'name' => 'Inovação',
                'weight' => 40, // 40%
                'min_score' => 0,
                'max_score' => 10,
            ],
            [
                'name' => 'Viabilidade',
                'weight' => 60, // 60%
                'min_score' => 0,
                'max_score' => 10,
            ],
        ],
    ]);

    expect($hackathon->id)->not->toBeNull();
    expect($hackathon->stages()->count())->toBe(2);
    expect($hackathon->evaluationCriteria()->count())->toBe(2);

    $c1 = $hackathon->evaluationCriteria()->where('name', 'Inovação')->first();
    $c2 = $hackathon->evaluationCriteria()->where('name', 'Viabilidade')->first();

    expect((float)$c1->weight)->toBe(0.40);
    expect((float)$c2->weight)->toBe(0.60);
});

test('JudgeService computes weighted scores and blocks evaluations with conflicts', function () {
    $owner = User::factory()->create();
    $judge = User::factory()->create();

    $org = Organization::create([
        'name' => 'Judge Org',
        'slug' => 'judge-org',
        'owner_id' => $owner->id,
        'is_active' => true,
    ]);

    $hackathon = Hackathon::create([
        'organization_id' => $org->id,
        'title' => 'Judged Hackathon',
        'slug' => 'judged-hackathon',
        'modality' => 'online',
        'status' => 'voting',
        'min_team_members' => 1,
        'max_team_members' => 5,
    ]);

    $crit1 = EvaluationCriterion::create([
        'hackathon_id' => $hackathon->id,
        'name' => 'Critério 1 (60%)',
        'weight' => 0.60,
        'min_score' => 0,
        'max_score' => 10,
        'order' => 1,
    ]);

    $crit2 = EvaluationCriterion::create([
        'hackathon_id' => $hackathon->id,
        'name' => 'Critério 2 (40%)',
        'weight' => 0.40,
        'min_score' => 0,
        'max_score' => 10,
        'order' => 2,
    ]);

    $team = Team::create(['name' => 'Finalist Team', 'slug' => 'finalist-team', 'leader_id' => $owner->id]);
    $project = Project::create([
        'hackathon_id' => $hackathon->id,
        'team_id' => $team->id,
        'title' => 'Solução IA',
        'slug' => 'solucao-ia',
        'status' => 'submitted',
    ]);

    // Assign judge
    JudgeAssignment::create([
        'hackathon_id' => $hackathon->id,
        'user_id' => $judge->id,
        'project_id' => $project->id,
        'status' => 'assigned',
    ]);

    $service = app(JudgeService::class);

    // Test evaluation submission: score 10 in crit1 (60%) and 5 in crit2 (40%) -> 6 + 2 = 8.0
    $evaluation = $service->storeEvaluation(
        $project,
        $judge,
        [
            ['criteria_id' => $crit1->id, 'score' => 10],
            ['criteria_id' => $crit2->id, 'score' => 5],
        ],
        'submitted',
        'Excelente apresentação',
        'Pode refinar a documentação'
    );

    expect((float)$evaluation->final_score)->toBe(8.0);
    expect($evaluation->status)->toBe('submitted');

    // Declare conflict
    $service->declareConflict($judge, [
        'hackathon_id' => $hackathon->id,
        'project_id' => $project->id,
        'reason' => 'Tenho relação com o time',
    ]);

    // Trying to evaluate again should throw exception
    expect(fn() => $service->storeEvaluation(
        $project,
        $judge,
        [
            ['criteria_id' => $crit1->id, 'score' => 9],
            ['criteria_id' => $crit2->id, 'score' => 9],
        ],
        'submitted'
    ))->toThrow(Exception::class);
});
