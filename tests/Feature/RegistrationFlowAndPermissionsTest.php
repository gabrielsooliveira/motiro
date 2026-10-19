<?php

namespace Tests\Feature;

use App\Models\ConflictOfInterest;
use App\Models\EvaluationCriterion;
use App\Models\Hackathon;
use App\Models\HackathonMember;
use App\Models\HackathonRegistration;
use App\Models\HackathonStage;
use App\Models\JudgeAssignment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationFlowAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganization(User $owner, array $attributes = []): Organization
    {
        $org = Organization::create(array_merge([
            'name' => 'Org Teste Permissoes',
            'slug' => 'org-teste-' . uniqid(),
            'owner_id' => $owner->id,
            'is_active' => true,
        ], $attributes));

        $org->users()->attach($owner->id, ['role' => 'admin', 'is_active' => true]);

        return $org;
    }

    private function createHackathon(Organization $org, array $attributes = []): Hackathon
    {
        return Hackathon::create(array_merge([
            'organization_id' => $org->id,
            'title' => 'Hackathon Matriz de Permissoes',
            'slug' => 'hackathon-matriz-' . uniqid(),
            'status' => 'registration',
            'modality' => 'online',
            'registration_type' => 'individual_or_team',
            'registration_start_date' => now()->subDay(),
            'registration_end_date' => now()->addDays(10),
            'start_date' => now()->addDays(11),
            'end_date' => now()->addDays(13),
            'min_team_members' => 2,
            'max_team_members' => 5,
        ], $attributes));
    }

    public function test_individual_registration_works_even_when_user_belongs_to_other_teams(): void
    {
        $user = User::factory()->create();
        $orgOwner = User::factory()->create();
        $org = $this->createOrganization($orgOwner);
        $hackathon = $this->createHackathon($org, [
            'registration_type' => 'individual_or_team',
        ]);

        // User is leader of a team
        $team = Team::create([
            'name' => 'Equipe Beta',
            'slug' => 'equipe-beta-' . uniqid(),
            'leader_id' => $user->id,
        ]);
        $team->members()->attach($user->id, [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'role' => 'leader',
        ]);

        // User explicitly registers individually
        $response = $this->actingAs($user)->post(route('registration.store'), [
            'hackathon_id' => $hackathon->id,
            'participation_type' => 'individual',
            'team_id' => $team->id, // Send team_id to assert backend strips it
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('hackathon_registrations', [
            'hackathon_id' => $hackathon->id,
            'user_id' => $user->id,
            'team_id' => null,
            'participation_type' => 'individual',
            'status' => 'pending',
        ]);
    }

    public function test_team_registration_fails_if_user_is_not_member_or_leader(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $orgOwner = User::factory()->create();
        $org = $this->createOrganization($orgOwner);
        $hackathon = $this->createHackathon($org, [
            'registration_type' => 'team_only',
            'min_team_members' => 1,
            'max_team_members' => 4,
        ]);

        $team = Team::create([
            'name' => 'Equipe Alheia',
            'slug' => 'equipe-alheia-' . uniqid(),
            'leader_id' => $otherUser->id,
        ]);
        $team->members()->attach($otherUser->id, [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'role' => 'leader',
        ]);

        $response = $this->actingAs($user)->post(route('registration.store'), [
            'hackathon_id' => $hackathon->id,
            'participation_type' => 'team',
            'team_id' => $team->id,
        ]);

        $response->assertSessionHasErrors(['registration']);
        $this->assertDatabaseMissing('hackathon_registrations', [
            'hackathon_id' => $hackathon->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_participant_can_cancel_own_registration_within_deadline(): void
    {
        $user = User::factory()->create();
        $orgOwner = User::factory()->create();
        $org = $this->createOrganization($orgOwner);
        $hackathon = $this->createHackathon($org);

        $reg = HackathonRegistration::create([
            'hackathon_id' => $hackathon->id,
            'user_id' => $user->id,
            'participation_type' => 'individual',
            'status' => 'approved',
            'registered_at' => now(),
        ]);

        $response = $this->actingAs($user)->delete(route('registration.cancel', $reg->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('hackathon_registrations', [
            'id' => $reg->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_organization_collaborator_is_strictly_forbidden_from_approving_or_rejecting_registrations(): void
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $participant = User::factory()->create();

        $org = $this->createOrganization($owner);
        $org->users()->attach($collaborator->id, ['role' => 'collaborator', 'is_active' => true]);

        $hackathon = $this->createHackathon($org);

        $reg = HackathonRegistration::create([
            'hackathon_id' => $hackathon->id,
            'user_id' => $participant->id,
            'participation_type' => 'individual',
            'status' => 'pending',
            'registered_at' => now(),
        ]);

        // Collaborator attempts to approve registration
        $response = $this->actingAs($collaborator)->patch(route('registration.status.update', $reg->id), [
            'status' => 'approved',
        ]);

        $response->assertForbidden();
        $this->assertEquals('pending', $reg->fresh()->status);

        // Owner can approve
        $ownerResponse = $this->actingAs($owner)->patch(route('registration.status.update', $reg->id), [
            'status' => 'approved',
        ]);

        $ownerResponse->assertRedirect();
        $this->assertEquals('approved', $reg->fresh()->status);
    }

    public function test_organization_collaborator_is_forbidden_from_altering_stages_or_criteria(): void
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();

        $org = $this->createOrganization($owner);
        $org->users()->attach($collaborator->id, ['role' => 'collaborator', 'is_active' => true]);

        $hackathon = $this->createHackathon($org);

        $stage = HackathonStage::create([
            'hackathon_id' => $hackathon->id,
            'name' => 'Etapa 1',
            'order' => 1,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
        ]);

        // Collaborator tries to create stage
        $createStageResponse = $this->actingAs($collaborator)->post(route('hackathon.manage.stage.store', $hackathon->id), [
            'name' => 'Etapa Proibida',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ]);
        $createStageResponse->assertForbidden();

        // Collaborator tries to delete stage
        $deleteStageResponse = $this->actingAs($collaborator)->delete(route('hackathon.manage.stage.destroy', [$hackathon->id, $stage->id]));
        $deleteStageResponse->assertForbidden();

        // Collaborator tries to create evaluation criterion
        $createCritResponse = $this->actingAs($collaborator)->post(route('hackathon.manage.criterion.store', $hackathon->id), [
            'name' => 'Critério Inovação',
            'weight' => 20,
        ]);
        $createCritResponse->assertForbidden();
    }

    public function test_judge_policy_allows_assigned_judge_and_blocks_unassigned_or_conflicted(): void
    {
        $owner = User::factory()->create();
        $judge = User::factory()->create();
        $unassignedJudge = User::factory()->create();
        $participant = User::factory()->create();

        $org = $this->createOrganization($owner);
        $hackathon = $this->createHackathon($org, ['status' => 'voting']);

        $team = Team::create([
            'name' => 'Equipe Projeto',
            'slug' => 'equipe-projeto-' . uniqid(),
            'leader_id' => $participant->id,
        ]);
        $team->members()->attach($participant->id, [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'role' => 'leader',
        ]);

        $project = Project::create([
            'hackathon_id' => $hackathon->id,
            'team_id' => $team->id,
            'user_id' => $participant->id,
            'title' => 'Projeto Incrível',
            'slug' => 'projeto-incrivel-' . uniqid(),
            'status' => 'submitted',
        ]);

        // Assign only $judge to project
        JudgeAssignment::create([
            'hackathon_id' => $hackathon->id,
            'project_id' => $project->id,
            'user_id' => $judge->id,
        ]);

        // 1. Assigned judge can access evaluation
        $responseAssigned = $this->actingAs($judge)->get(route('judge.evaluate', $project->id));
        $responseAssigned->assertOk();

        // 2. Unassigned judge gets 403
        $responseUnassigned = $this->actingAs($unassignedJudge)->get(route('judge.evaluate', $project->id));
        $responseUnassigned->assertForbidden();

        // 3. Assigned judge declares conflict of interest -> now blocked
        ConflictOfInterest::create([
            'hackathon_id' => $hackathon->id,
            'project_id' => $project->id,
            'team_id' => $team->id,
            'user_id' => $judge->id,
            'reason' => 'Mentor da equipe',
        ]);

        $responseConflicted = $this->actingAs($judge)->get(route('judge.evaluate', $project->id));
        $responseConflicted->assertForbidden();
    }
}
