<?php

namespace Tests\Feature;

use App\Models\Hackathon;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HackathonStatusManagementAndDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createOrganization(User $owner, array $overrides = []): Organization
    {
        return Organization::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'Instituição de Teste',
            'email' => 'teste@org.test',
            'slug' => 'instituicao-teste-' . Str::random(6),
            'password' => bcrypt('password123'),
            'owner_id' => $owner->id,
            'is_active' => true,
        ], $overrides));
    }

    public function test_can_create_hackathon_with_explicit_status_open_for_registration(): void
    {
        $owner = User::factory()->create();
        $org = $this->createOrganization($owner);

        $response = $this->actingAs($owner)->post(route('hackathon.manage.store'), [
            'organization_id' => $org->id,
            'title' => 'Hackathon Nacional Aberto 2026',
            'modality' => 'online',
            'status' => 'registration',
            'min_team_members' => 1,
            'max_team_members' => 4,
            'registration_start_date' => now()->subDay()->toISOString(),
            'registration_end_date' => now()->addWeek()->toISOString(),
        ]);

        $hackathon = Hackathon::where('title', 'Hackathon Nacional Aberto 2026')->first();
        $this->assertNotNull($hackathon);
        $this->assertEquals('registration', $hackathon->status);

        $response->assertRedirect(route('hackathon.manage.index', $hackathon->id));

        // Verificar que aparece imediatamente no catálogo de hackathons para participantes
        $participant = User::factory()->create();
        $catalogResponse = $this->actingAs($participant)->get(route('participant.hackathons'));
        $catalogResponse->assertOk();
        $catalogResponse->assertInertia(fn (Assert $page) => $page
            ->component('Participant/Dashboard/Hackathons')
            ->has('hackathons.data', 1)
            ->where('hackathons.data.0.title', 'Hackathon Nacional Aberto 2026')
            ->where('hackathons.data.0.status', 'registration')
        );

        // Verificar filtro de abertos
        $openResponse = $this->actingAs($participant)->get(route('participant.hackathons', ['status' => 'open']));
        $openResponse->assertInertia(fn (Assert $page) => $page
            ->has('hackathons.data', 1)
            ->where('hackathons.data.0.title', 'Hackathon Nacional Aberto 2026')
        );
    }

    public function test_can_create_hackathon_as_draft_and_it_is_hidden_from_catalog(): void
    {
        $owner = User::factory()->create();
        $org = $this->createOrganization($owner);

        $this->actingAs($owner)->post(route('hackathon.manage.store'), [
            'organization_id' => $org->id,
            'title' => 'Hackathon Rascunho Inicial',
            'modality' => 'online',
            'status' => 'draft',
            'min_team_members' => 1,
            'max_team_members' => 4,
        ]);

        $hackathon = Hackathon::where('title', 'Hackathon Rascunho Inicial')->first();
        $this->assertNotNull($hackathon);
        $this->assertEquals('draft', $hackathon->status);

        // Não aparece no catálogo de participantes
        $participant = User::factory()->create();
        $catalogResponse = $this->actingAs($participant)->get(route('participant.hackathons'));
        $catalogResponse->assertInertia(fn (Assert $page) => $page
            ->has('hackathons.data', 0)
        );
    }

    public function test_admin_can_update_status_directly_via_patch_route(): void
    {
        $owner = User::factory()->create();
        $org = $this->createOrganization($owner);

        $hackathon = Hackathon::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org->id,
            'title' => 'Hackathon Transição de Status',
            'slug' => 'hackathon-transicao-status',
            'status' => 'draft',
            'modality' => 'online',
            'min_team_members' => 1,
            'max_team_members' => 4,
            'registration_start_date' => now()->subDay(),
            'registration_end_date' => now()->addWeek(),
        ]);

        // Participante não vê o rascunho
        $participant = User::factory()->create();
        $respBefore = $this->actingAs($participant)->get(route('participant.hackathons'));
        $respBefore->assertInertia(fn (Assert $page) => $page->has('hackathons.data', 0));

        // Admin atualiza status para 'registration'
        $patchResponse = $this->actingAs($owner)->patch(route('hackathon.manage.status', $hackathon->id), [
            'status' => 'registration',
        ]);

        $patchResponse->assertRedirect();
        $this->assertEquals('registration', $hackathon->fresh()->status);

        // Agora o participante vê o hackathon no catálogo de abertos
        $respAfter = $this->actingAs($participant)->get(route('participant.hackathons', ['status' => 'open']));
        $respAfter->assertInertia(fn (Assert $page) => $page
            ->has('hackathons.data', 1)
            ->where('hackathons.data.0.title', 'Hackathon Transição de Status')
            ->where('hackathons.data.0.status', 'registration')
        );

        // Atualiza para 'submission'
        $this->actingAs($owner)->patch(route('hackathon.manage.status', $hackathon->id), [
            'status' => 'submission',
        ]);
        $this->assertEquals('submission', $hackathon->fresh()->status);

        // Agora não aparece no filtro 'open', mas aparece no filtro 'in_progress'
        $respOpen = $this->actingAs($participant)->get(route('participant.hackathons', ['status' => 'open']));
        $respOpen->assertInertia(fn (Assert $page) => $page->has('hackathons.data', 0));

        $respProgress = $this->actingAs($participant)->get(route('participant.hackathons', ['status' => 'in_progress']));
        $respProgress->assertInertia(fn (Assert $page) => $page->has('hackathons.data', 1));
    }

    public function test_updating_status_with_invalid_value_fails_validation(): void
    {
        $owner = User::factory()->create();
        $org = $this->createOrganization($owner);

        $hackathon = Hackathon::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org->id,
            'title' => 'Hackathon Validação Status',
            'slug' => 'hackathon-validacao-status',
            'status' => 'draft',
            'modality' => 'online',
            'min_team_members' => 1,
            'max_team_members' => 4,
        ]);

        $response = $this->actingAs($owner)->patch(route('hackathon.manage.status', $hackathon->id), [
            'status' => 'invalid_status_xyz',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('draft', $hackathon->fresh()->status);
    }

    public function test_collaborator_cannot_update_hackathon_status(): void
    {
        $owner = User::factory()->create();
        $org = $this->createOrganization($owner);

        $collaborator = User::factory()->create();
        $org->users()->attach($collaborator->id, [
            'role' => 'collaborator',
            'is_active' => true,
        ]);

        $hackathon = Hackathon::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org->id,
            'title' => 'Hackathon Teste Colaborador',
            'slug' => 'hackathon-teste-colaborador',
            'status' => 'draft',
            'modality' => 'online',
            'min_team_members' => 1,
            'max_team_members' => 4,
        ]);

        $response = $this->actingAs($collaborator)->patch(route('hackathon.manage.status', $hackathon->id), [
            'status' => 'registration',
        ]);

        $response->assertForbidden();
        $this->assertEquals('draft', $hackathon->fresh()->status);
    }

    public function test_registration_is_allowed_when_status_is_registration_even_if_start_date_was_future(): void
    {
        $owner = User::factory()->create();
        $org = $this->createOrganization($owner);

        // Hackathon com status registration e data no futuro
        $hackathon = Hackathon::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org->id,
            'title' => 'Hackathon Aberto Imediato',
            'slug' => 'hackathon-aberto-imediato',
            'status' => 'registration',
            'modality' => 'online',
            'registration_type' => 'individual_or_team',
            'min_team_members' => 1,
            'max_team_members' => 4,
            'registration_start_date' => now()->addDay(),
            'registration_end_date' => now()->addDays(10),
            'auto_approve_registrations' => true,
        ]);

        $participant = User::factory()->create();

        $response = $this->actingAs($participant)->post(route('registration.store'), [
            'hackathon_id' => $hackathon->id,
            'participation_type' => 'individual',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('hackathon_registrations', [
            'hackathon_id' => $hackathon->id,
            'user_id' => $participant->id,
        ]);
    }

    public function test_registration_is_blocked_when_status_is_published_and_start_date_is_future(): void
    {
        $owner = User::factory()->create();
        $org = $this->createOrganization($owner);

        // Hackathon apenas 'published' com data no futuro
        $hackathon = Hackathon::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org->id,
            'title' => 'Hackathon Apenas Publicado',
            'slug' => 'hackathon-apenas-publicado',
            'status' => 'published',
            'modality' => 'online',
            'registration_type' => 'individual_or_team',
            'min_team_members' => 1,
            'max_team_members' => 4,
            'registration_start_date' => now()->addDay(),
            'registration_end_date' => now()->addDays(10),
        ]);

        $participant = User::factory()->create();

        $response = $this->actingAs($participant)->post(route('registration.store'), [
            'hackathon_id' => $hackathon->id,
            'participation_type' => 'individual',
        ]);

        $response->assertSessionHasErrors(['registration' => 'O período de inscrições para este Hackathon ainda não foi iniciado.']);
    }

    public function test_admin_can_update_event_dates_in_manage(): void
    {
        $owner = User::factory()->create();
        $org = $this->createOrganization($owner);

        $hackathon = Hackathon::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org->id,
            'title' => 'Hackathon Edição de Datas',
            'slug' => 'hackathon-edicao-datas',
            'status' => 'registration',
            'modality' => 'online',
            'registration_type' => 'individual_or_team',
            'min_team_members' => 1,
            'max_team_members' => 4,
            'registration_start_date' => now()->addDay(),
            'registration_end_date' => now()->addDays(5),
        ]);

        $newStart = now()->subHour()->format('Y-m-d H:i:s');
        $newEnd = now()->addDays(15)->format('Y-m-d H:i:s');

        $response = $this->actingAs($owner)->put(route('hackathon.manage.update', $hackathon->id), [
            'registration_start_date' => $newStart,
            'registration_end_date' => $newEnd,
        ]);

        $response->assertSessionHasNoErrors();
        $hackathon->refresh();
        $this->assertEquals($newStart, $hackathon->registration_start_date->format('Y-m-d H:i:s'));
        $this->assertEquals($newEnd, $hackathon->registration_end_date->format('Y-m-d H:i:s'));
    }
}
