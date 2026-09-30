<?php

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registers a new competitor successfully', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $response = $this->post(route('register.store'), [
        'type' => 'competitor',
        'name' => 'Competidor Silva',
        'email' => 'competidor@teste.com',
        'password' => 'senha12345',
        'password_confirmation' => 'senha12345',
    ]);

    $response->assertRedirect(route('dashboard.index'));

    $user = User::where('email', 'competidor@teste.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Competidor Silva');
    expect($user->roles()->where('name', 'competitor')->exists())->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

test('registers a new organization successfully', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $response = $this->post(route('register.store'), [
        'type' => 'organization',
        'name' => 'Universidade Inovadora',
        'email' => 'contato@univ-inova.edu.br',
        'organization_type' => 'Universidade',
        'website' => 'https://univ-inova.edu.br',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ]);

    $response->assertRedirect(route('organization.dashboard'));

    $user = User::where('email', 'contato@univ-inova.edu.br')->first();
    expect($user)->not->toBeNull();

    $org = Organization::where('name', 'Universidade Inovadora')->first();
    expect($org)->not->toBeNull();
    expect($org->type)->toBe('Universidade');
    expect($org->owner_id)->toBe($user->id);
    expect($user->roles()->where('name', 'org_admin')->exists())->toBeTrue();

    $this->assertAuthenticatedAs($user);
});
