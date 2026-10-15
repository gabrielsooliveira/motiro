<?php

namespace Tests\Feature;

use App\Models\Hackathon;
use App\Models\HackathonRegistration;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ParticipantHackathonDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createOrganization(array $overrides = []): Organization
    {
        $owner = User::factory()->create();
        return Organization::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'Instituto de Inovação',
            'email' => 'contato@inovacao-' . Str::random(5) . '.org',
            'slug' => 'instituto-inovacao-' . Str::random(6),
            'password' => bcrypt('password123'),
            'owner_id' => $owner->id,
            'is_active' => true,
        ], $overrides));
    }

    private function createHackathon(Organization $org, array $overrides = []): Hackathon
    {
        return Hackathon::create(array_merge([
            'id' => (string) Str::uuid(),
            'organization_id' => $org->id,
            'title' => 'Hackathon Inovação Aberta ' . Str::random(4),
            'slug' => 'hackathon-' . Str::random(8),
            'status' => 'registration',
            'registration_type' => 'individual_or_team',
            'modality' => 'online',
            'category' => 'Tecnologia',
            'min_team_members' => 1,
            'max_team_members' => 4,
            'allow_team_creation' => true,
            'allow_team_invites' => true,
            'allow_team_requests' => true,
            'auto_approve_registrations' => true,
            'registration_start_date' => now()->subDay(),
            'registration_end_date' => now()->addWeek(),
            'submission_start_date' => now()->subDay(),
            'submission_end_date' => now()->addWeek(),
        ], $overrides));
    }

    public function test_unauthenticated_user_is_redirected_to_participant_login(): void
    {
        $response = $this->get(route('participant.hackathons'));
        $response->assertRedirect(route('participant.login'));

        $altResponse = $this->get('/dashboard/hackathons');
        $altResponse->assertRedirect(route('login'));
    }

    public function test_authenticated_participant_can_render_discovery_page(): void
    {
        $user = User::factory()->create();
        $org = $this->createOrganization();
        $this->createHackathon($org, [
            'title' => 'Hackathon Inovação Aberta',
        ]);

        $response = $this->actingAs($user)->get(route('participant.hackathons'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Participant/Dashboard/Hackathons')
            ->has('hackathons.data', 1)
            ->where('hackathons.data.0.title', 'Hackathon Inovação Aberta')
            ->has('filters')
            ->has('userTeams')
        );
    }

    public function test_draft_hackathons_are_excluded_from_discovery(): void
    {
        $user = User::factory()->create();
        $org = $this->createOrganization();

        $this->createHackathon($org, [
            'status' => 'draft',
            'title' => 'Rascunho Secreto',
        ]);

        $this->createHackathon($org, [
            'status' => 'registration',
            'title' => 'Hackathon Público Oficial',
        ]);

        $response = $this->actingAs($user)->get(route('participant.hackathons'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Participant/Dashboard/Hackathons')
            ->has('hackathons.data', 1)
            ->where('hackathons.data.0.title', 'Hackathon Público Oficial')
        );
    }

    public function test_search_filter_finds_by_title_category_or_organization(): void
    {
        $user = User::factory()->create();
        $org1 = $this->createOrganization(['name' => 'GovTech Brasil']);
        $org2 = $this->createOrganization(['name' => 'FinTech Valley']);

        $this->createHackathon($org1, [
            'title' => 'Desafio Cidades Inteligentes',
            'category' => 'Cidades',
        ]);

        $this->createHackathon($org2, [
            'title' => 'Open Finance Hack',
            'category' => 'Finanças',
        ]);

        // Search by title term
        $response1 = $this->actingAs($user)->get(route('participant.hackathons', ['search' => 'Inteligentes']));
        $response1->assertInertia(fn (Assert $page) => $page
            ->has('hackathons.data', 1)
            ->where('hackathons.data.0.title', 'Desafio Cidades Inteligentes')
        );

        // Search by organization name
        $response2 = $this->actingAs($user)->get(route('participant.hackathons', ['search' => 'FinTech Valley']));
        $response2->assertInertia(fn (Assert $page) => $page
            ->has('hackathons.data', 1)
            ->where('hackathons.data.0.title', 'Open Finance Hack')
        );
    }

    public function test_modality_and_status_filters(): void
    {
        $user = User::factory()->create();
        $org = $this->createOrganization();

        $this->createHackathon($org, [
            'status' => 'registration',
            'modality' => 'online',
            'title' => 'Hack Online',
        ]);

        $this->createHackathon($org, [
            'status' => 'finished',
            'modality' => 'presential',
            'title' => 'Hack Presencial Encerrado',
        ]);

        // Filter modality online
        $resOnline = $this->actingAs($user)->get(route('participant.hackathons', ['modality' => 'online']));
        $resOnline->assertInertia(fn (Assert $page) => $page
            ->has('hackathons.data', 1)
            ->where('hackathons.data.0.title', 'Hack Online')
        );

        // Filter status finished
        $resFinished = $this->actingAs($user)->get(route('participant.hackathons', ['status' => 'finished']));
        $resFinished->assertInertia(fn (Assert $page) => $page
            ->has('hackathons.data', 1)
            ->where('hackathons.data.0.title', 'Hack Presencial Encerrado')
        );
    }

    public function test_user_registration_status_is_accurately_detected(): void
    {
        $user = User::factory()->create();
        $org = $this->createOrganization();

        $hackathonRegistered = $this->createHackathon($org, [
            'title' => 'Hackathon que Já Me Inscrevi',
        ]);

        $hackathonNotRegistered = $this->createHackathon($org, [
            'title' => 'Hackathon que Ainda Não Me Inscrevi',
        ]);

        HackathonRegistration::create([
            'id' => (string) Str::uuid(),
            'hackathon_id' => $hackathonRegistered->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'registered_at' => now(),
        ]);

        // List all and check is_registered flag
        $response = $this->actingAs($user)->get(route('participant.hackathons'));
        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($hackathonRegistered, $hackathonNotRegistered) {
            $page->component('Participant/Dashboard/Hackathons')
                ->where('hackathons.data', function ($items) use ($hackathonRegistered, $hackathonNotRegistered) {
                    $regItem = collect($items)->firstWhere('id', $hackathonRegistered->id);
                    $notRegItem = collect($items)->firstWhere('id', $hackathonNotRegistered->id);
                    return $regItem['is_registered'] === true && $notRegItem['is_registered'] === false;
                });
        });

        // Filter by my_status=registered
        $responseRegistered = $this->actingAs($user)->get(route('participant.hackathons', ['my_status' => 'registered']));
        $responseRegistered->assertInertia(fn (Assert $page) => $page
            ->has('hackathons.data', 1)
            ->where('hackathons.data.0.id', $hackathonRegistered->id)
        );

        // Filter by my_status=not_registered
        $responseNotRegistered = $this->actingAs($user)->get(route('participant.hackathons', ['my_status' => 'not_registered']));
        $responseNotRegistered->assertInertia(fn (Assert $page) => $page
            ->has('hackathons.data', 1)
            ->where('hackathons.data.0.id', $hackathonNotRegistered->id)
        );
    }

    public function test_user_can_submit_registration_from_discovery(): void
    {
        $user = User::factory()->create();
        $org = $this->createOrganization();

        $hackathon = $this->createHackathon($org, [
            'status' => 'registration',
            'registration_type' => 'individual',
            'registration_start_date' => now()->subDay(),
            'registration_end_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($user)->post(route('registration.store'), [
            'hackathon_id' => $hackathon->id,
            'participation_type' => 'individual',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('hackathon_registrations', [
            'hackathon_id' => $hackathon->id,
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
    }
}
