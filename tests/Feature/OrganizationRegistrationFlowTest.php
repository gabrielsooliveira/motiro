<?php

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('novo usuário consegue criar conta pessoal e cadastrar organização em etapas', function () {
    // Etapa 1 — Criação de conta pessoal
    $accountResp = $this->post(route('organization.register.account'), [
        'name' => 'Ana Clara Santos',
        'email' => 'anaclara@pessoal.test',
        'password' => 'senhaSegura123',
        'password_confirmation' => 'senhaSegura123',
        'terms' => true,
    ]);

    $accountResp->assertSessionHasNoErrors();
    $user = User::where('email', 'anaclara@pessoal.test')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Ana Clara Santos');
    expect($user->roles()->where('name', 'competitor')->exists())->toBeTrue();
    $this->assertAuthenticatedAs($user);

    // Etapa 2 & 3 — Dados da organização e confirmação
    $orgResp = $this->post(route('organization.register.store'), [
        'organization_name' => 'Instituto Inova Brasil',
        'organization_type' => 'Organização social / Sem fins lucrativos',
        'organization_email' => 'contato@inovabrasil.org',
        'website' => 'https://inovabrasil.org',
        'description' => 'Instituto dedicado ao fomento de hackathons sociais.',
    ]);

    $org = Organization::where('name', 'Instituto Inova Brasil')->first();
    expect($org)->not->toBeNull();
    expect($org->type)->toBe('Organização social / Sem fins lucrativos');
    expect($org->email)->toBe('contato@inovabrasil.org');
    expect($org->owner_id)->toBe($user->id);

    // Vínculo administrativo criado na tabela organization_user
    $membership = OrganizationUser::where('organization_id', $org->id)
        ->where('user_id', $user->id)
        ->first();
    expect($membership)->not->toBeNull();
    expect($membership->role)->toBe('admin');
    expect($membership->is_active)->toBeTrue();

    // Permissão de administrador da organização no RBAC
    expect($user->roles()->where('name', 'org_admin')->wherePivot('organization_id', $org->id)->exists())->toBeTrue();

    // Contexto ativo definido
    expect(session('current_context'))->toBe('organization');
    expect(session('active_organization_id'))->toBe($org->id);

    // Redireciona para a tela de conclusão (Etapa 4)
    $orgResp->assertRedirect(route('organization.register.success', ['organization' => $org->id]));
});

test('usuário autenticado consegue criar uma organização sem recriar sua conta', function () {
    $existingUser = User::factory()->create([
        'name' => 'Carlos Gestor',
        'email' => 'carlos@empresa.test',
        'password' => bcrypt('password123'),
    ]);

    $initialUserCount = User::count();

    // Acessa o formulário de cadastro de organização
    $getResp = $this->actingAs($existingUser)->get(route('organization.register'));
    $getResp->assertOk();
    $getResp->assertInertia(fn ($page) => 
        $page->component('Organization/Auth/Register')
            ->where('authUser.id', $existingUser->id)
            ->where('authUser.email', 'carlos@empresa.test')
    );

    // Cria a organização
    $postResp = $this->actingAs($existingUser)->post(route('organization.register.store'), [
        'organization_name' => 'Empresa Alfa Tecnologia',
        'organization_type' => 'Empresa',
        'organization_email' => 'contato@alfa.test',
        'website' => 'https://alfa.test',
    ]);

    // Nenhuma conta de usuário adicional deve ter sido criada
    expect(User::count())->toBe($initialUserCount);

    $org = Organization::where('name', 'Empresa Alfa Tecnologia')->first();
    expect($org)->not->toBeNull();
    expect($org->owner_id)->toBe($existingUser->id);

    $membership = OrganizationUser::where('organization_id', $org->id)->where('user_id', $existingUser->id)->first();
    expect($membership->role)->toBe('admin');
});

test('usuário existente consegue acessar o fluxo após autenticar-se na Etapa 1 (Opção A)', function () {
    $user = User::factory()->create([
        'name' => 'Mariana Silva',
        'email' => 'mariana@login.test',
        'password' => bcrypt('minhasenha123'),
    ]);

    $loginResp = $this->post(route('organization.register.login'), [
        'email' => 'mariana@login.test',
        'password' => 'minhasenha123',
    ]);

    $loginResp->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);

    // Agora cria a organização com o usuário logado
    $createResp = $this->post(route('organization.register.store'), [
        'organization_name' => 'Startup Futuro',
        'organization_type' => 'Startup',
        'organization_email' => 'ola@futuro.test',
    ]);

    $org = Organization::where('name', 'Startup Futuro')->first();
    expect($org)->not->toBeNull();
    expect($org->owner_id)->toBe($user->id);
});

test('o e-mail institucional é armazenado na organização e o e-mail de login permanece inalterado', function () {
    $user = User::factory()->create([
        'name' => 'Lucas Pessoal',
        'email' => 'lucas.pessoal@gmail.com',
        'password' => bcrypt('senha123'),
    ]);

    $this->actingAs($user)->post(route('organization.register.store'), [
        'organization_name' => 'Governo Digital Lab',
        'organization_type' => 'Instituição pública',
        'organization_email' => 'contato@governodigital.gov.br',
    ]);

    $user->refresh();
    // O e-mail de login continua o e-mail pessoal
    expect($user->email)->toBe('lucas.pessoal@gmail.com');

    $org = Organization::where('name', 'Governo Digital Lab')->first();
    // O e-mail institucional é armazenado na organização
    expect($org->email)->toBe('contato@governodigital.gov.br');
});

test('o usuário pode atuar como participante e administrador simultaneamente', function () {
    $user = User::factory()->create([
        'email' => 'hibrido@test.com',
        'password' => bcrypt('password123'),
    ]);

    $org = Organization::create([
        'name' => 'Comunidade Híbrida',
        'slug' => 'comunidade-hibrida',
        'owner_id' => $user->id,
        'is_active' => true,
    ]);

    OrganizationUser::create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'role' => 'admin',
        'is_active' => true,
    ]);

    // 1. Acessa dashboard de participante
    $respParticipant = $this->actingAs($user)
        ->withSession(['current_context' => 'competitor'])
        ->get(route('participant.dashboard'));
    $respParticipant->assertOk();

    // 2. Acessa dashboard de organização
    $respOrg = $this->actingAs($user)
        ->withSession([
            'current_context' => 'organization',
            'active_organization_id' => $org->id,
        ])
        ->get(route('organization.dashboard'));
    $respOrg->assertOk();
});

test('o usuário pode ter vínculo com múltiplas organizações', function () {
    $user = User::factory()->create(['name' => 'Líder Multi', 'email' => 'lider@multi.test']);

    $this->actingAs($user)->post(route('organization.register.store'), [
        'organization_name' => 'Universidade Prime',
        'organization_type' => 'Universidade ou instituição de ensino',
    ]);

    $this->actingAs($user)->post(route('organization.register.store'), [
        'organization_name' => 'Tech Hub Prime',
        'organization_type' => 'Comunidade ou coletivo',
    ]);

    $orgs = $user->organizations()->get();
    expect($orgs->count())->toBe(2);
    expect($orgs->pluck('name')->all())->toContain('Universidade Prime', 'Tech Hub Prime');
});

test('um usuário não autenticado não consegue criar uma organização por requisição direta', function () {
    $response = $this->post(route('organization.register.store'), [
        'organization_name' => 'Tentativa Anônima',
        'organization_type' => 'Empresa',
    ]);

    $response->assertSessionHasErrors('auth');
    expect(Organization::where('name', 'Tentativa Anônima')->exists())->toBeFalse();
});

test('tentativas de cadastro com mesmo nome geram slugs únicos automaticamente', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('organization.register.store'), [
        'organization_name' => 'Inovação Aberta',
        'organization_type' => 'Empresa',
    ]);

    $this->actingAs($user)->post(route('organization.register.store'), [
        'organization_name' => 'Inovação Aberta',
        'organization_type' => 'Empresa',
    ]);

    $orgs = Organization::where('name', 'Inovação Aberta')->get();
    expect($orgs->count())->toBe(2);
    expect($orgs[0]->slug)->not->toBe($orgs[1]->slug);
});

test('validações de campos obrigatórios e formatos funcionam', function () {
    $user = User::factory()->create();

    // Falha por nome ausente
    $resp1 = $this->actingAs($user)->post(route('organization.register.store'), [
        'organization_name' => '',
        'organization_type' => 'Empresa',
    ]);
    $resp1->assertSessionHasErrors('organization_name');

    // Falha por formato inválido de website
    $resp2 = $this->actingAs($user)->post(route('organization.register.store'), [
        'organization_name' => 'Org Web Invalida',
        'website' => 'not-a-valid-url',
    ]);
    $resp2->assertSessionHasErrors('website');

    // Falha por e-mail institucional inválido
    $resp3 = $this->actingAs($user)->post(route('organization.register.store'), [
        'organization_name' => 'Org Email Invalido',
        'organization_email' => 'email-sem-formato',
    ]);
    $resp3->assertSessionHasErrors('organization_email');
});

test('o fluxo de convite existente continua funcionando e vincula à instituição existente', function () {
    $admin = User::factory()->create(['name' => 'Admin Org']);
    $org = Organization::create([
        'name' => 'Instituição com Convite',
        'slug' => 'instituicao-convite',
        'owner_id' => $admin->id,
        'is_active' => true,
    ]);

    $invitation = OrganizationInvitation::create([
        'organization_id' => $org->id,
        'email' => 'convidado@equipe.test',
        'role' => 'manager',
        'invited_by_id' => $admin->id,
        'token' => Str::random(40),
        'status' => 'pending',
    ]);

    $initialOrgCount = Organization::count();

    // Aceita o convite através da rota de cadastro
    $resp = $this->post(route('organization.register.store'), [
        'invitation_token' => $invitation->token,
        'name' => 'Convidado Gestor',
        'password' => 'segredo123',
        'password_confirmation' => 'segredo123',
    ]);

    $resp->assertRedirect(route('organization.dashboard'));

    // Nenhuma organização nova foi criada
    expect(Organization::count())->toBe($initialOrgCount);

    $newUser = User::where('email', 'convidado@equipe.test')->first();
    expect($newUser)->not->toBeNull();

    // Vínculo associado com a role do convite
    $membership = OrganizationUser::where('organization_id', $org->id)->where('user_id', $newUser->id)->first();
    expect($membership)->not->toBeNull();
    expect($membership->role)->toBe('manager');

    $invitation->refresh();
    expect($invitation->status)->toBe('accepted');
});

test('tela de conclusão (Etapa 4) renderiza os dados da organização para o criador e bloqueia usuários não autorizados', function () {
    $owner = User::factory()->create(['name' => 'Proprietário']);
    $otherUser = User::factory()->create(['name' => 'Outro Usuário']);

    $org = Organization::create([
        'name' => 'Organização Sucesso',
        'slug' => 'org-sucesso',
        'type' => 'Empresa',
        'email' => 'sucesso@org.test',
        'owner_id' => $owner->id,
        'is_active' => true,
    ]);

    OrganizationUser::create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'role' => 'admin',
        'is_active' => true,
    ]);

    // O proprietário acessa com sucesso
    $respOwner = $this->actingAs($owner)->get(route('organization.register.success', ['organization' => $org->id]));
    $respOwner->assertOk();
    $respOwner->assertInertia(fn ($page) => 
        $page->component('Organization/Auth/Success')
            ->where('organization.name', 'Organização Sucesso')
            ->where('user.name', 'Proprietário')
    );

    // Usuário sem vínculo recebe 403
    $respOther = $this->actingAs($otherUser)->get(route('organization.register.success', ['organization' => $org->id]));
    $respOther->assertStatus(403);
});

test('não é permitido criar nova conta pessoal se o e-mail já estiver cadastrado', function () {
    User::factory()->create([
        'email' => 'duplicado@motiro.test',
    ]);

    $response = $this->post(route('organization.register.account'), [
        'name' => 'Tentativa Duplicada',
        'email' => 'duplicado@motiro.test',
        'password' => 'senha123456',
        'password_confirmation' => 'senha123456',
        'terms' => true,
    ]);

    $response->assertSessionHasErrors('email');
});
