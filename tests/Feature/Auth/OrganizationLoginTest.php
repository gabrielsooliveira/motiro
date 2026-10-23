<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Models\UserParticipant;
use App\Services\ContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrganizationLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_org_user_can_login_with_personal_email_and_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'orguser@teste.com',
            'password' => Hash::make('password123'),
        ]);

        $org = Organization::factory()->create([
            'owner_id' => $user->id,
            'is_active' => true,
        ]);

        OrganizationUser::firstOrCreate(
            ['organization_id' => $org->id, 'user_id' => $user->id],
            ['role' => 'admin', 'is_active' => true]
        );

        $response = $this->post(route('organization.login.store'), [
            'email' => 'orguser@teste.com',
            'password' => 'password123',
            'context' => 'organization',
        ]);

        $response->assertRedirect(route('organization.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertEquals(ContextService::CONTEXT_ORGANIZATION, session('current_context'));
        $this->assertEquals($org->id, session('active_organization_id'));

        // Access dashboard
        $dashboardResponse = $this->get(route('organization.dashboard'));
        $dashboardResponse->assertStatus(200);
    }

    public function test_org_owner_can_login_using_organization_institutional_contact_email(): void
    {
        $owner = User::factory()->create([
            'email' => 'personal_owner@teste.com',
            'password' => Hash::make('secretpass'),
        ]);

        $org = Organization::factory()->create([
            'email' => 'contato@empresa-inovadora.com',
            'owner_id' => $owner->id,
            'is_active' => true,
        ]);

        $response = $this->post(route('organization.login.store'), [
            'email' => 'contato@empresa-inovadora.com',
            'password' => 'secretpass',
            'context' => 'organization',
        ]);

        $response->assertRedirect(route('organization.dashboard'));
        $this->assertAuthenticatedAs($owner);
        $this->assertEquals(ContextService::CONTEXT_ORGANIZATION, session('current_context'));
        $this->assertEquals($org->id, session('active_organization_id'));
    }

    public function test_org_user_with_only_owner_id_auto_syncs_pivot_and_accesses_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'owneronly@teste.com',
            'password' => Hash::make('password123'),
        ]);

        $org = Organization::factory()->create([
            'owner_id' => $user->id,
            'is_active' => true,
        ]);

        $response = $this->post(route('organization.login.store'), [
            'email' => 'owneronly@teste.com',
            'password' => 'password123',
            'context' => 'organization',
        ]);

        $response->assertRedirect(route('organization.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertEquals(ContextService::CONTEXT_ORGANIZATION, session('current_context'));
        $this->assertEquals($org->id, session('active_organization_id'));

        $dashboardResponse = $this->get(route('organization.dashboard'));
        $dashboardResponse->assertStatus(200);
    }

    public function test_user_without_org_cannot_login_at_org_login(): void
    {
        $user = User::factory()->create([
            'email' => 'pureuser@teste.com',
            'password' => Hash::make('password123'),
        ]);

        UserParticipant::create(['user_id' => $user->id]);

        $response = $this->post(route('organization.login.store'), [
            'email' => 'pureuser@teste.com',
            'password' => 'password123',
            'context' => 'organization',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_hybrid_user_logging_in_at_org_login_accesses_org_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'hybrid@teste.com',
            'password' => Hash::make('password123'),
        ]);

        UserParticipant::create(['user_id' => $user->id]);

        $org = Organization::factory()->create([
            'owner_id' => $user->id,
            'is_active' => true,
        ]);

        $response = $this->post(route('organization.login.store'), [
            'email' => 'hybrid@teste.com',
            'password' => 'password123',
            'context' => 'organization',
        ]);

        $response->assertRedirect(route('organization.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertEquals(ContextService::CONTEXT_ORGANIZATION, session('current_context'));
        $this->assertEquals($org->id, session('active_organization_id'));
    }

    public function test_org_user_with_multiple_organizations_redirects_to_select(): void
    {
        $user = User::factory()->create([
            'email' => 'multiorg@teste.com',
            'password' => Hash::make('password123'),
        ]);

        $org1 = Organization::factory()->create(['owner_id' => $user->id, 'is_active' => true]);
        $org2 = Organization::factory()->create(['owner_id' => $user->id, 'is_active' => true]);

        $response = $this->post(route('organization.login.store'), [
            'email' => 'multiorg@teste.com',
            'password' => 'password123',
            'context' => 'organization',
        ]);

        $response->assertRedirect(route('organization.select'));
        $this->assertAuthenticatedAs($user);
    }
}
