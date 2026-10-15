<?php

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('participant login redirects to participant dashboard', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create([
        'email' => 'competitor@test.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('participant.login.store'), [
        'email' => 'competitor@test.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('participant.dashboard'));
    $this->assertAuthenticatedAs($user);
    expect(session('current_context'))->toBe('competitor');
});

test('organization login redirects to organization dashboard when user has one organization', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create([
        'email' => 'orgadmin@test.com',
        'password' => bcrypt('password123'),
    ]);

    $org = Organization::create([
        'name' => 'Single Org',
        'email' => 'contact@single.test',
        'slug' => 'single-org',
        'owner_id' => $user->id,
        'is_active' => true,
    ]);

    OrganizationUser::create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'role' => 'admin',
        'is_active' => true,
    ]);

    $response = $this->post(route('organization.login.store'), [
        'email' => 'orgadmin@test.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('organization.dashboard'));
    $this->assertAuthenticatedAs($user);
    expect(session('current_context'))->toBe('organization');
    expect(session('active_organization_id'))->toBe($org->id);
});

test('organization login redirects to context select when user has multiple organizations', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create([
        'email' => 'multi@test.com',
        'password' => bcrypt('password123'),
    ]);

    $org1 = Organization::create([
        'name' => 'Org One',
        'email' => 'one@test.com',
        'slug' => 'org-one',
        'owner_id' => $user->id,
        'is_active' => true,
    ]);
    $org2 = Organization::create([
        'name' => 'Org Two',
        'email' => 'two@test.com',
        'slug' => 'org-two',
        'owner_id' => $user->id,
        'is_active' => true,
    ]);

    OrganizationUser::create(['organization_id' => $org1->id, 'user_id' => $user->id, 'role' => 'admin', 'is_active' => true]);
    OrganizationUser::create(['organization_id' => $org2->id, 'user_id' => $user->id, 'role' => 'admin', 'is_active' => true]);

    $response = $this->post(route('organization.login.store'), [
        'email' => 'multi@test.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('organization.select'));
    $this->assertAuthenticatedAs($user);
});

test('organization login denies access with guidance when user has no organizations', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create([
        'email' => 'no_org@test.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('organization.login.store'), [
        'email' => 'no_org@test.com',
        'password' => 'password123',
    ]);

    // O login de organização nega o acesso e não redireciona para a área do participante
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('unauthenticated users are redirected from protected portals', function () {
    $respParticipant = $this->get(route('participant.dashboard'));
    $respParticipant->assertRedirect(route('participant.login'));

    $respOrg = $this->get(route('organization.dashboard'));
    $respOrg->assertRedirect(route('organization.login'));
});

test('user without organizations cannot access organization dashboard directly', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create([
        'email' => 'competitor_only@test.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->actingAs($user)->get(route('organization.dashboard'));
    $response->assertStatus(403);
});

test('same identity can access participant dashboard and organization dashboard when authorized', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create([
        'email' => 'shared@test.com',
        'password' => bcrypt('password123'),
    ]);

    $org = Organization::create([
        'name' => 'Shared Org',
        'email' => 'shared@org.test',
        'slug' => 'shared-org',
        'owner_id' => $user->id,
        'is_active' => true,
    ]);

    OrganizationUser::create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'role' => 'admin',
        'is_active' => true,
    ]);

    // 1. Logs into participant portal
    $resp1 = $this->post(route('participant.login.store'), [
        'email' => 'shared@test.com',
        'password' => 'password123',
    ]);
    $resp1->assertRedirect(route('participant.dashboard'));
    expect(session('current_context'))->toBe('competitor');

    // 2. Logs out
    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->assertGuest();

    // 3. Logs into organization portal using the same email and password
    $resp2 = $this->post(route('organization.login.store'), [
        'email' => 'shared@test.com',
        'password' => 'password123',
    ]);
    $resp2->assertRedirect(route('organization.dashboard'));
    expect(session('current_context'))->toBe('organization');
});
