<?php

use App\Models\Evaluation;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\Hackathon;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Services\EvaluationService;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('complete hackathon lifecycle flow from registration to scoring', function () {
    // 1. Create Organization & Owner
    $owner = User::factory()->create();
    $org = Organization::create([
        'id' => (string) Str::uuid(),
        'name' => 'Tech Corp',
        'email' => 'tech@corp.test',
        'slug' => 'tech-corp',
        'password' => bcrypt('secret'),
        'owner_id' => $owner->id,
        'is_active' => true,
    ]);

    // 2. Create Hackathon
    $hackathon = Hackathon::create([
        'id' => (string) Str::uuid(),
        'organization_id' => $org->id,
        'title' => 'Inova Hackathon 2026',
        'slug' => 'inova-hack-2026',
        'status' => 'registration',
        'min_team_members' => 1,
        'max_team_members' => 4,
        'requires_team' => true,
        'auto_approve_registrations' => false,
        'scoring_algorithm' => 'weighted_average',
    ]);

    // 3. Create Criteria
    $c1 = EvaluationCriterion::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Inovação',
        'weight' => 0.60,
    ]);
    $c2 = EvaluationCriterion::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'name' => 'Técnica',
        'weight' => 0.40,
    ]);

    // 4. Create Competitor and Team
    $competitor = User::factory()->create();
    $team = Team::create([
        'id' => (string) Str::uuid(),
        'name' => 'Alpha Coders',
        'slug' => 'alpha-coders',
        'leader_id' => $competitor->id,
    ]);
    $team->members()->attach($competitor->id, [
        'id' => (string) Str::uuid(),
        'role' => 'leader',
    ]);

    // 5. Register Team into Hackathon
    $regService = new RegistrationService();
    $registration = $regService->registerTeam($hackathon, $team, $competitor);
    expect($registration->status)->toBe('pending');

    // Admin Approves Team
    $regService->updateStatus($registration, 'approved', $owner);
    expect($registration->fresh()->status)->toBe('approved');

    // 6. Submit Project
    $project = Project::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'team_id' => $team->id,
        'title' => 'EcoTrack AI',
        'slug' => 'ecotrack-ai',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    // 7. Judge Evaluates
    $judge = User::factory()->create();
    $evaluation = Evaluation::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'project_id' => $project->id,
        'user_id' => $judge->id,
        'status' => 'submitted',
        'final_score' => (10.0 * 0.60) + (8.0 * 0.40), // 6 + 3.2 = 9.2
        'submitted_at' => now(),
    ]);

    EvaluationScore::create([
        'id' => (string) Str::uuid(),
        'evaluation_id' => $evaluation->id,
        'criteria_id' => $c1->id,
        'score' => 10.0,
    ]);
    EvaluationScore::create([
        'id' => (string) Str::uuid(),
        'evaluation_id' => $evaluation->id,
        'criteria_id' => $c2->id,
        'score' => 8.0,
    ]);

    // 8. Calculate Rankings
    $evalService = new EvaluationService();
    $rankings = $evalService->calculateRankings($hackathon);

    expect($rankings)->toHaveCount(1);
    expect($rankings[0]['final_score'])->toBe(9.2);
    expect($rankings[0]['project']->title)->toBe('EcoTrack AI');
});
