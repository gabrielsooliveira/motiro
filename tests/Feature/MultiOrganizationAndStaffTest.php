<?php

use App\Models\Hackathon;
use App\Models\HackathonMember;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('user can be linked to multiple organizations and switch context', function () {
    $user = User::factory()->create(['name' => 'Gabriel MultiOrg', 'email' => 'gabriel@multiorg.test']);

    $orgA = Organization::create([
        'name' => 'Universidade Alpha',
        'email' => 'alpha@univ.test',
        'slug' => 'universidade-alpha',
        'password' => bcrypt('password'),
        'owner_id' => $user->id,
        'is_active' => true,
    ]);

    $orgB = Organization::create([
        'name' => 'Prefeitura Beta',
        'email' => 'beta@pref.test',
        'slug' => 'prefeitura-beta',
        'password' => bcrypt('password'),
        'owner_id' => $user->id,
        'is_active' => true,
    ]);

    OrganizationUser::create(['organization_id' => $orgA->id, 'user_id' => $user->id, 'role' => 'admin', 'is_active' => true]);
    OrganizationUser::create(['organization_id' => $orgB->id, 'user_id' => $user->id, 'role' => 'admin', 'is_active' => true]);

    $this->actingAs($user);

    // Switch to Org A
    $respA = $this->post(route('context.switch'), [
        'type' => 'organization',
        'organization_id' => $orgA->id,
    ]);
    $respA->assertRedirect(route('organization.dashboard'));
    expect(session('active_organization_id'))->toBe($orgA->id);
    expect(session('current_context'))->toBe('organization');

    // Switch to Org B
    $respB = $this->post(route('context.switch'), [
        'type' => 'organization',
        'organization_id' => $orgB->id,
    ]);
    $respB->assertRedirect(route('organization.dashboard'));
    expect(session('active_organization_id'))->toBe($orgB->id);

    // Switch to Competitor
    $respComp = $this->post(route('context.switch'), [
        'type' => 'competitor',
    ]);
    $respComp->assertRedirect(route('dashboard.index'));
    expect(session('current_context'))->toBe('competitor');
    expect(session('active_organization_id'))->toBeNull();
});

test('organization dashboard isolates hackathons per institution', function () {
    $user = User::factory()->create();
    $org1 = Organization::create([
        'name' => 'Org One',
        'email' => 'one@org.test',
        'slug' => 'org-one',
        'password' => bcrypt('password'),
        'owner_id' => $user->id,
        'is_active' => true,
    ]);
    $org2 = Organization::create([
        'name' => 'Org Two',
        'email' => 'two@org.test',
        'slug' => 'org-two',
        'password' => bcrypt('password'),
        'owner_id' => $user->id,
        'is_active' => true,
    ]);

    OrganizationUser::create(['organization_id' => $org1->id, 'user_id' => $user->id, 'role' => 'admin', 'is_active' => true]);
    OrganizationUser::create(['organization_id' => $org2->id, 'user_id' => $user->id, 'role' => 'admin', 'is_active' => true]);

    $hackathon1 = Hackathon::create([
        'organization_id' => $org1->id,
        'title' => 'Hackathon 1 Org 1',
        'slug' => 'hackathon-1-org-1',
        'modality' => 'online',
        'status' => 'registration',
    ]);

    $hackathon2 = Hackathon::create([
        'organization_id' => $org2->id,
        'title' => 'Hackathon 2 Org 2',
        'slug' => 'hackathon-2-org-2',
        'modality' => 'online',
        'status' => 'registration',
    ]);

    $this->actingAs($user)->withSession([
        'current_context' => 'organization',
        'active_organization_id' => $org1->id,
    ]);

    $response = $this->get(route('organization.dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) =>
        $page->component('Dashboard/Organization/Dashboard')
            ->where('organization.id', $org1->id)
            ->where('kpis.total_hackathons', 1)
    );
});

test('organization admin can invite user and manage roles', function () {
    $admin = User::factory()->create(['email' => 'admin@inst.test']);
    $org = Organization::create([
        'name' => 'Tech Institute',
        'email' => 'contact@tech.test',
        'slug' => 'tech-institute',
        'password' => bcrypt('password'),
        'owner_id' => $admin->id,
        'is_active' => true,
    ]);
    OrganizationUser::create(['organization_id' => $org->id, 'user_id' => $admin->id, 'role' => 'admin', 'is_active' => true]);

    $this->actingAs($admin)->withSession([
        'current_context' => 'organization',
        'active_organization_id' => $org->id,
    ]);

    // 1. Invite non-existing user
    $inviteResponse = $this->post(route('organization.users.invite'), [
        'email' => 'newcollaborator@test.com',
        'role' => 'collaborator',
    ]);
    $inviteResponse->assertRedirect();
    expect(OrganizationInvitation::where('email', 'newcollaborator@test.com')->exists())->toBeTrue();

    // 2. Add existing user
    $existingUser = User::factory()->create(['email' => 'existing@test.com']);
    $addResponse = $this->post(route('organization.users.invite'), [
        'email' => 'existing@test.com',
        'role' => 'manager',
    ]);
    $addResponse->assertRedirect();
    expect(OrganizationUser::where('organization_id', $org->id)->where('user_id', $existingUser->id)->exists())->toBeTrue();

    // 3. Update role
    $updateRoleResponse = $this->put(route('organization.users.update_role', $existingUser->id), [
        'role' => 'collaborator',
    ]);
    $updateRoleResponse->assertRedirect();
    expect(OrganizationUser::where('organization_id', $org->id)->where('user_id', $existingUser->id)->value('role'))->toBe('collaborator');

    // 4. Toggle active status
    $toggleResponse = $this->put(route('organization.users.toggle_status', $existingUser->id));
    $toggleResponse->assertRedirect();
    expect((bool)OrganizationUser::where('organization_id', $org->id)->where('user_id', $existingUser->id)->value('is_active'))->toBeFalse();

    // 5. Remove member
    $removeResponse = $this->delete(route('organization.users.remove', $existingUser->id));
    $removeResponse->assertRedirect();
    expect(OrganizationUser::where('organization_id', $org->id)->where('user_id', $existingUser->id)->exists())->toBeFalse();
});

test('organization admin can assign staff to specific hackathons and enforce authorization', function () {
    $admin = User::factory()->create(['email' => 'lead@inst.test']);
    $collab1 = User::factory()->create(['name' => 'Collab One', 'email' => 'c1@inst.test']);
    $collab2 = User::factory()->create(['name' => 'Collab Two', 'email' => 'c2@inst.test']);

    $org = Organization::create([
        'name' => 'Org Staff Test',
        'email' => 'staff@org.test',
        'slug' => 'org-staff-test',
        'password' => bcrypt('password'),
        'owner_id' => $admin->id,
        'is_active' => true,
    ]);

    OrganizationUser::create(['organization_id' => $org->id, 'user_id' => $admin->id, 'role' => 'admin', 'is_active' => true]);
    OrganizationUser::create(['organization_id' => $org->id, 'user_id' => $collab1->id, 'role' => 'collaborator', 'is_active' => true]);
    OrganizationUser::create(['organization_id' => $org->id, 'user_id' => $collab2->id, 'role' => 'collaborator', 'is_active' => true]);

    $hackathonA = Hackathon::create([
        'organization_id' => $org->id,
        'title' => 'Hackathon Alpha',
        'slug' => 'hackathon-alpha',
        'modality' => 'online',
        'status' => 'draft',
    ]);

    $hackathonB = Hackathon::create([
        'organization_id' => $org->id,
        'title' => 'Hackathon Beta',
        'slug' => 'hackathon-beta',
        'modality' => 'online',
        'status' => 'draft',
    ]);

    // Admin assigns Collab 1 to Hackathon A as manager
    $this->actingAs($admin)->withSession([
        'current_context' => 'organization',
        'active_organization_id' => $org->id,
    ]);

    $assignResp = $this->post(route('organization.hackathon.members.store', $hackathonA->id), [
        'user_id' => $collab1->id,
        'role' => 'manager',
    ]);
    $assignResp->assertRedirect();
    expect(HackathonMember::where('hackathon_id', $hackathonA->id)->where('user_id', $collab1->id)->exists())->toBeTrue();

    // Collab 1 can access Hackathon A management
    $this->actingAs($collab1)->withSession([
        'current_context' => 'organization',
        'active_organization_id' => $org->id,
    ]);
    $manageRespA = $this->get(route('hackathon.manage.index', $hackathonA->id));
    $manageRespA->assertOk();

    // Collab 1 CANNOT access Hackathon B management (Section 9: Gabriel não deve gerenciar Hackathon B)
    $manageRespB = $this->get(route('hackathon.manage.index', $hackathonB->id));
    $manageRespB->assertStatus(403);
});
