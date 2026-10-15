<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('forgot password screen can be rendered', function () {
    $response = $this->get(route('password.request'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Auth/ForgotPassword'));
});

test('reset password link can be requested', function () {
    $user = User::factory()->create(['email' => 'user@reset.test']);

    $response = $this->post(route('password.email'), [
        'email' => 'user@reset.test',
    ]);

    $response->assertSessionHas('status');
});

test('reset password screen can be rendered with token', function () {
    $user = User::factory()->create(['email' => 'user@reset.test']);
    $token = Password::createToken($user);

    $response = $this->get(route('password.reset', ['token' => $token, 'email' => 'user@reset.test']));
    $response->assertOk();
    $response->assertInertia(fn ($page) =>
        $page->component('Auth/ResetPassword')
            ->where('token', $token)
            ->where('email', 'user@reset.test')
    );
});

test('password can be reset with valid token', function () {
    $user = User::factory()->create(['email' => 'user@reset.test']);
    $token = Password::createToken($user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => 'user@reset.test',
        'password' => 'novasenha123',
        'password_confirmation' => 'novasenha123',
    ]);

    $response->assertRedirect(route('login'));
    expect(Hash::check('novasenha123', $user->fresh()->password))->toBeTrue();
});
