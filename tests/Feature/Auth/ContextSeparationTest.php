<?php

namespace Tests\Feature\Auth;

use App\Actions\Organization\AcceptOrganizationInvitationAction;
use App\Actions\Organization\CreateOrganizationAction;
use App\Actions\Participant\CreateParticipantProfileAction;
use App\Actions\User\CreateUserAccountAction;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Models\UserParticipant;
use App\Services\ContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContextSeparationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Autenticação centralizada em users sem dependência de users.type
     */
    public function test_authentication_is_centralized_in_users(): void
    {
        $user = User::factory()->create([
            'email' => 'shared@motiro.test',
            'password' => Hash::make('password123'),
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'shared@motiro.test',
        ]);

        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * 2. Criação de perfil de participante específico em user_participant
     */
    public function test_participant_profile_is_stored_in_user_participant_table(): void
    {
        $createUserAction = app(CreateUserAccountAction::class);

        $user = $createUserAction->execute([
            'name' => 'Competidor Exemplo',
            'email' => 'competidor@motiro.test',
            'password' => 'password123',
            'education_institution' => 'USP',
            'education_course' => 'Sistemas de Informação',
            'skills' => ['Laravel', 'Vue.js'],
        ], true);

        $this->assertTrue($user->hasParticipantProfile());
        $this->assertFalse($user->hasOrganizationProfile());

        $this->assertDatabaseHas('user_participant', [
            'user_id' => $user->id,
            'education_institution' => 'USP',
            'education_course' => 'Sistemas de Informação',
        ]);
    }

    /**
     * 3. Bloqueio de acesso ao dashboard de participante sem perfil user_participant
     */
    public function test_user_without_participant_profile_cannot_access_participant_dashboard(): void
    {
        // Usuário puramente de organização (sem perfil de participante)
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('participant.dashboard'));

        // Redireciona para completar perfil de participante com aviso
        $response->assertRedirect(route('participant.complete_profile'));
    }

    /**
     * 4. Usuário puramente de organização bloqueado de acesso administrativo sem vínculo
     */
    public function test_user_without_organization_link_cannot_access_organization_dashboard(): void
    {
        $user = User::factory()->create();

        // Tenta acessar dashboard da organização sem possuir vínculos
        $response = $this->actingAs($user)->get(route('organization.dashboard'));

        $response->assertStatus(403);
    }

    /**
     * 5. Vínculos organizacionais múltiplos, papéis e isolamento em organization_user
     */
    public function test_user_can_belong_to_multiple_organizations_with_different_roles(): void
    {
        $user = User::factory()->create();

        $orgA = Organization::factory()->create(['name' => 'Org Alpha']);
        $orgB = Organization::factory()->create(['name' => 'Org Beta']);

        OrganizationUser::create([
            'organization_id' => $orgA->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        OrganizationUser::create([
            'organization_id' => $orgB->id,
            'user_id' => $user->id,
            'role' => 'collaborator',
            'is_active' => true,
        ]);

        $this->assertTrue($user->hasOrganizationProfile());
        $this->assertEquals(2, $user->activeOrganizations()->count());

        $this->assertEquals('admin', $user->getOrganizationRole($orgA->id));
        $this->assertEquals('collaborator', $user->getOrganizationRole($orgB->id));

        $this->assertTrue($user->isOrganizationAdmin($orgA->id));
        $this->assertFalse($user->isOrganizationAdmin($orgB->id));
    }

    /**
     * 6. Seleção e alternância de contexto
     */
    public function test_context_switching_between_participant_and_organization(): void
    {
        $user = User::factory()->create();

        // Cria perfil de participante
        UserParticipant::create(['user_id' => $user->id]);

        // Vincula a uma organização
        $org = Organization::factory()->create();
        OrganizationUser::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->assertTrue($user->hasParticipantProfile());
        $this->assertTrue($user->hasOrganizationProfile());

        // Alterna para participante
        $response = $this->actingAs($user)->post(route('context.switch'), [
            'type' => 'competitor',
        ]);
        $response->assertRedirect(route('participant.dashboard'));
        $this->assertEquals(ContextService::CONTEXT_COMPETITOR, session('current_context'));

        // Alterna para organização
        $response = $this->actingAs($user)->post(route('context.switch'), [
            'type' => 'organization',
            'organization_id' => $org->id,
        ]);
        $response->assertRedirect(route('organization.dashboard'));
        $this->assertEquals(ContextService::CONTEXT_ORGANIZATION, session('current_context'));
        $this->assertEquals($org->id, session('active_organization_id'));
    }

    /**
     * 7. Login de usuário com ambos os perfis redireciona para tela de seleção de contexto
     */
    public function test_login_user_with_both_profiles_redirects_to_context_selector(): void
    {
        $user = User::factory()->create([
            'email' => 'hibrido@motiro.test',
            'password' => Hash::make('secret123'),
        ]);

        UserParticipant::create(['user_id' => $user->id]);

        $org = Organization::factory()->create();
        OrganizationUser::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post(route('participant.login.store'), [
            'email' => 'hibrido@motiro.test',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('context.select'));
    }

    /**
     * 8. Aceite de convite institucional vincula o usuário sem duplicar a conta
     */
    public function test_accepting_organization_invitation_links_existing_user_without_duplication(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'membro@empresa.com',
        ]);

        $org = Organization::factory()->create();

        $invitation = OrganizationInvitation::create([
            'organization_id' => $org->id,
            'email' => 'membro@empresa.com',
            'role' => 'manager',
            'token' => Str::random(32),
            'status' => 'pending',
        ]);

        $action = app(AcceptOrganizationInvitationAction::class);
        $action->execute($invitation, $existingUser);

        // Verifica que o número total de contas users continua sendo 1
        $this->assertEquals(1, User::where('email', 'membro@empresa.com')->count());

        // Verifica vínculo em organization_user com papel correto
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $org->id,
            'user_id' => $existingUser->id,
            'role' => 'manager',
            'is_active' => 1,
        ]);

        // Verifica convite aceito
        $this->assertEquals('accepted', $invitation->fresh()->status);
    }

    /**
     * 9. Usuário de organização pode criar perfil de participante posteriormente
     */
    public function test_organization_user_can_later_activate_participant_profile(): void
    {
        $user = User::factory()->create();

        $org = Organization::factory()->create();
        OrganizationUser::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->assertFalse($user->hasParticipantProfile());

        // Ativa perfil de participante
        $action = app(CreateParticipantProfileAction::class);
        $action->execute($user, [
            'education_institution' => 'Unicamp',
            'skills' => ['Data Science', 'Python'],
        ]);

        $user->refresh();
        $this->assertTrue($user->hasParticipantProfile());
        $this->assertTrue($user->hasOrganizationProfile());

        $this->assertDatabaseHas('user_participant', [
            'user_id' => $user->id,
            'education_institution' => 'Unicamp',
        ]);
    }

    /**
     * 10. Logout compartilhado encerra a sessão e limpa os contextos
     */
    public function test_logout_invalidates_session_and_clears_context(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    /**
     * 11. Endpoint HTTP de completar perfil ativa o contexto de competidor
     */
    public function test_complete_profile_route_persists_participant_and_redirects(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('participant.complete_profile.store'), [
            'education_institution' => 'FIAP',
            'education_course' => 'Engenharia de Software',
            'is_profile_public' => true,
        ]);

        $response->assertRedirect(route('participant.dashboard'));
        $this->assertTrue($user->fresh()->hasParticipantProfile());
        $this->assertEquals(ContextService::CONTEXT_COMPETITOR, session('current_context'));
    }

    /**
     * 12. Cadastro de novo participante cria usuário, perfil e autentica
     */
    public function test_participant_registration_endpoint_creates_user_and_profile(): void
    {
        $response = $this->post(route('participant.register.store'), [
            'name' => 'Novo Participante',
            'email' => 'novo.participante@motiro.test',
            'password' => 'senhaSegura123',
            'password_confirmation' => 'senhaSegura123',
            'terms' => true,
        ]);

        $response->assertRedirect(route('participant.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'novo.participante@motiro.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasParticipantProfile());
        $this->assertEquals(ContextService::CONTEXT_COMPETITOR, session('current_context'));
    }
}
