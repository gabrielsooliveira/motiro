<?php

namespace Tests\Feature;

use App\Mail\GuildInvitationMail;
use App\Models\Hackathon;
use App\Models\HackathonGroup;
use App\Models\HackathonRegistration;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TeamService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('1. Criação válida de guilda com criador como responsável e sem imagem', function () {
    $creator = User::factory()->create();

    $response = $this->actingAs($creator)->post(route('participant.teams.store'), [
        'name' => 'Guilda dos Desenvolvedores',
        'description' => 'Comunidade voltada a projetos open-source e hackathons',
        'set_as_leader' => true,
    ]);

    $response->assertSessionHasNoErrors();
    $guild = Team::where('name', 'Guilda dos Desenvolvedores')->first();

    expect($guild)->not->toBeNull();
    expect($guild->creator_id)->toBe($creator->id);
    expect($guild->leader_id)->toBe($creator->id);
    expect($guild->logo)->toBeNull();
    expect($guild->members)->toHaveCount(1);
    expect($guild->members->first()->id)->toBe($creator->id);
    expect($guild->members->first()->pivot->role)->toBe('leader');
});

test('2. Validação de nome e descrição da guilda', function () {
    $creator = User::factory()->create();

    // Nome vazio
    $response = $this->actingAs($creator)->post(route('participant.teams.store'), [
        'name' => '',
        'description' => 'Descrição qualquer',
    ]);
    $response->assertSessionHasErrors(['name']);

    // Nome excedendo limite
    $response = $this->actingAs($creator)->post(route('participant.teams.store'), [
        'name' => str_repeat('A', 256),
    ]);
    $response->assertSessionHasErrors(['name']);

    // Descrição excedendo limite
    $response = $this->actingAs($creator)->post(route('participant.teams.store'), [
        'name' => 'Guilda Válida',
        'description' => str_repeat('D', 2001),
    ]);
    $response->assertSessionHasErrors(['description']);
});

test('3. Upload, substituição e remoção de imagem da guilda', function () {
    Storage::fake('public');
    $creator = User::factory()->create();

    // 1. Criação com Upload de Imagem
    $file1 = UploadedFile::fake()->image('guild-logo.jpg', 400, 400);

    $response = $this->actingAs($creator)->post(route('participant.teams.store'), [
        'name' => 'Design Masters Guild',
        'description' => 'Guilda focada em UI/UX',
        'image' => $file1,
    ]);

    $response->assertSessionHasNoErrors();
    $guild = Team::where('name', 'Design Masters Guild')->first();
    expect($guild->logo)->not->toBeNull();
    $path1 = str_replace('/storage/', '', $guild->logo);
    Storage::disk('public')->assertExists($path1);

    // 2. Substituição da Imagem
    $file2 = UploadedFile::fake()->image('guild-logo-v2.png', 500, 500);

    $response = $this->actingAs($creator)->post(route('participant.teams.update', $guild->id), [
        'name' => 'Design Masters Guild Updated',
        'description' => $guild->description,
        'image' => $file2,
    ]);

    $response->assertSessionHasNoErrors();
    $freshGuild = $guild->fresh();
    expect($freshGuild->logo)->not->toBe($guild->logo);
    $path2 = str_replace('/storage/', '', $freshGuild->logo);
    Storage::disk('public')->assertExists($path2);
    // Imagem antiga deve ter sido excluída
    Storage::disk('public')->assertMissing($path1);

    // 3. Remoção da Imagem
    $response = $this->actingAs($creator)->post(route('participant.teams.update', $guild->id), [
        'name' => $freshGuild->name,
        'description' => $freshGuild->description,
        'remove_image' => true,
    ]);

    $response->assertSessionHasNoErrors();
    $noLogoGuild = $freshGuild->fresh();
    expect($noLogoGuild->logo)->toBeNull();
    Storage::disk('public')->assertMissing($path2);
});

test('4. Listagem e gerenciamento de membros da guilda', function () {
    $creator = User::factory()->create(['name' => 'Criador']);
    $member1 = User::factory()->create(['name' => 'Membro Um']);
    $member2 = User::factory()->create(['name' => 'Membro Dois']);

    $guild = app(TeamService::class)->createTeam($creator, [
        'name' => 'Guilda de Testes de Membros',
    ]);

    // Adiciona membros
    $guild->members()->attach($member1->id, ['id' => (string) Str::uuid(), 'role' => 'member', 'joined_at' => now()]);
    $guild->members()->attach($member2->id, ['id' => (string) Str::uuid(), 'role' => 'member', 'joined_at' => now()]);

    expect($guild->fresh()->members)->toHaveCount(3);

    // Visualização da guilda
    $response = $this->actingAs($member1)->get(route('participant.teams.show', $guild->id));
    $response->assertOk();

    // Membro comum tenta remover outro membro (bloqueado com 403)
    $response = $this->actingAs($member1)->delete(route('participant.teams.members.remove', [
        'id' => $guild->id,
        'userId' => $member2->id,
    ]));
    $response->assertForbidden();

    // Líder/Criador remove membro2 com sucesso
    $response = $this->actingAs($creator)->delete(route('participant.teams.members.remove', [
        'id' => $guild->id,
        'userId' => $member2->id,
    ]));
    $response->assertSessionHasNoErrors();
    expect($guild->fresh()->members->pluck('id'))->not->toContain($member2->id);

    // Bloqueio de remoção do próprio criador da guilda
    $response = $this->actingAs($creator)->delete(route('participant.teams.members.remove', [
        'id' => $guild->id,
        'userId' => $creator->id,
    ]));
    $response->assertSessionHasErrors(['member']);
});

test('5. Envio de convite por e-mail com Mailpit / Mailable e verificação de duplicidade', function () {
    Mail::fake();

    $creator = User::factory()->create(['name' => 'Carlos Líder']);
    $invitee = User::factory()->create(['email' => 'convidado@motiro.test', 'name' => 'Ana Dev']);

    $guild = app(TeamService::class)->createTeam($creator, [
        'name' => 'ByteCrafters Guild',
        'description' => 'Guilda de desenvolvimento',
    ]);

    // Envio inicial de convite
    $response = $this->actingAs($creator)->post(route('participant.teams.invite', $guild->id), [
        'email_or_username' => 'convidado@motiro.test',
    ]);

    $response->assertSessionHasNoErrors();

    // Verifica que o Mailable GuildInvitationMail foi enviado
    Mail::assertSent(GuildInvitationMail::class, function ($mail) use ($invitee, $guild, $creator) {
        return $mail->hasTo($invitee->email)
            && $mail->guild->id === $guild->id
            && $mail->inviter->id === $creator->id
            && !empty($mail->inviteUrl);
    });

    $invitation = TeamInvitation::where('team_id', $guild->id)->where('email', 'convidado@motiro.test')->first();
    expect($invitation)->not->toBeNull();
    expect($invitation->status)->toBe('pending');
    expect($invitation->expires_at)->not->toBeNull();
    expect($invitation->expires_at->isFuture())->toBeTrue();

    // Tentativa de convite duplicado para a mesma guilda
    $responseDup = $this->actingAs($creator)->post(route('participant.teams.invite', $guild->id), [
        'email_or_username' => 'convidado@motiro.test',
    ]);

    $responseDup->assertSessionHasErrors(['email_or_username']);
    expect(TeamInvitation::where('team_id', $guild->id)->where('email', 'convidado@motiro.test')->count())->toBe(1);
});

test('6. Reenvio com rate limit e cancelamento de convite', function () {
    Mail::fake();

    $creator = User::factory()->create();
    $guild = app(TeamService::class)->createTeam($creator, ['name' => 'Cloud Ops Guild']);

    $invite = app(TeamService::class)->inviteMember($guild, $creator, 'dev-ops@motiro.test');

    // Tentativa imediata de reenvio deve falhar pelo rate-limiting (< 2 min)
    $response = $this->actingAs($creator)->post(route('participant.teams.invite.resend', $invite->id));
    $response->assertSessionHasErrors(['invite']);

    // Simula passagem de tempo (3 minutos)
    \Illuminate\Support\Facades\DB::table('team_invitations')->where('id', $invite->id)->update([
        'created_at' => now()->subMinutes(3),
        'updated_at' => now()->subMinutes(3),
    ]);

    // Reenvio agora é permitido
    $response = $this->actingAs($creator)->post(route('participant.teams.invite.resend', $invite->id));
    $response->assertSessionHasNoErrors();
    expect($invite->fresh()->expires_at->isFuture())->toBeTrue();

    // Cancelamento do convite
    $responseCancel = $this->actingAs($creator)->delete(route('participant.teams.invite.cancel', $invite->id));
    $responseCancel->assertSessionHasNoErrors();
    expect($invite->fresh()->status)->toBe('cancelled');
});

test('7. Aceitação bem-sucedida de convite por token com transação e recusa', function () {
    $creator = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'membro.futuro@motiro.test']);

    $guild = app(TeamService::class)->createTeam($creator, ['name' => 'Data Scientists']);
    $invite = app(TeamService::class)->inviteMember($guild, $creator, $invitee->email);

    // Acesso à página pública do convite
    $responseShow = $this->actingAs($invitee)->get(route('guild.invitations.show', $invite->token));
    $responseShow->assertOk();

    // Aceitação do convite
    $responseAccept = $this->actingAs($invitee)->post(route('guild.invitations.accept', $invite->token));
    $responseAccept->assertRedirect(route('team.show', $guild->id));

    expect($guild->fresh()->members->pluck('id'))->toContain($invitee->id);
    expect($invite->fresh()->status)->toBe('accepted');

    // Teste de recusa com outro convite
    $invitee2 = User::factory()->create(['email' => 'outro.dev@motiro.test']);
    $invite2 = app(TeamService::class)->inviteMember($guild, $creator, $invitee2->email);

    $responseDecline = $this->actingAs($invitee2)->post(route('guild.invitations.decline', $invite2->token));
    $responseDecline->assertRedirect(route('participant.dashboard'));

    expect($guild->fresh()->members->pluck('id'))->not->toContain($invitee2->id);
    expect($invite2->fresh()->status)->toBe('declined');
});

test('8. Bloqueio de aceitação de convite expirado, cancelado ou com conta diferente', function () {
    $creator = User::factory()->create();
    $legitInvitee = User::factory()->create(['email' => 'legit@motiro.test']);
    $impostor = User::factory()->create(['email' => 'impostor@motiro.test']);

    $guild = app(TeamService::class)->createTeam($creator, ['name' => 'Security Guild']);
    $invite = app(TeamService::class)->inviteMember($guild, $creator, $legitInvitee->email);

    // 1. Impostor tentando aceitar o convite
    $responseImpostor = $this->actingAs($impostor)->post(route('guild.invitations.accept', $invite->token));
    $responseImpostor->assertSessionHasErrors(['invitation']);
    expect($guild->fresh()->members->pluck('id'))->not->toContain($impostor->id);

    // 2. Convite Cancelado
    $invite->update(['status' => 'cancelled']);
    $responseCancelled = $this->actingAs($legitInvitee)->post(route('guild.invitations.accept', $invite->token));
    $responseCancelled->assertSessionHasErrors(['invitation']);

    // 3. Convite Expirado
    $invite->update(['status' => 'pending', 'expires_at' => now()->subDay()]);
    $responseExpired = $this->actingAs($legitInvitee)->post(route('guild.invitations.accept', $invite->token));
    $responseExpired->assertSessionHasErrors(['invitation']);
    expect($guild->fresh()->members->pluck('id'))->not->toContain($legitInvitee->id);
});

test('9. Transferência de liderança e regras de saída voluntária', function () {
    $creator = User::factory()->create(['name' => 'Líder Atual']);
    $member = User::factory()->create(['name' => 'Sucessor']);

    $guild = app(TeamService::class)->createTeam($creator, ['name' => 'Leadership Guild']);
    $guild->members()->attach($member->id, ['id' => (string) Str::uuid(), 'role' => 'member', 'joined_at' => now()]);

    // Líder tenta sair sem antes transferir liderança (bloqueado)
    $responseLeaveBlocked = $this->actingAs($creator)->post(route('participant.teams.leave', $guild->id));
    $responseLeaveBlocked->assertSessionHasErrors(['leave']);
    expect($guild->fresh()->members->pluck('id'))->toContain($creator->id);

    // Transferência de liderança para o membro
    $responseTransfer = $this->actingAs($creator)->post(route('participant.teams.leader', $guild->id), [
        'leader_id' => $member->id,
    ]);
    $responseTransfer->assertSessionHasNoErrors();
    expect($guild->fresh()->leader_id)->toBe($member->id);

    // Agora o criador (não mais líder) consegue sair voluntariamente
    $responseLeaveSuccess = $this->actingAs($creator)->post(route('participant.teams.leave', $guild->id));
    $responseLeaveSuccess->assertSessionHasNoErrors();
    expect($guild->fresh()->members->pluck('id'))->not->toContain($creator->id);
});

test('10. Exclusão de guilda autorizada apenas ao criador', function () {
    $creator = User::factory()->create();
    $member = User::factory()->create();

    $guild = app(TeamService::class)->createTeam($creator, ['name' => 'Ephemeral Guild']);
    $guild->members()->attach($member->id, ['id' => (string) Str::uuid(), 'role' => 'member', 'joined_at' => now()]);

    // Membro comum tenta excluir (bloqueado)
    $responseMember = $this->actingAs($member)->delete(route('participant.teams.destroy', $guild->id));
    $responseMember->assertForbidden();
    expect(Team::find($guild->id))->not->toBeNull();

    // Criador exclui a guilda
    $responseCreator = $this->actingAs($creator)->delete(route('participant.teams.destroy', $guild->id));
    $responseCreator->assertRedirect(route('participant.teams.index'));
    expect(Team::find($guild->id))->toBeNull();
});

test('11. Separação estrita entre membros da guilda e grupos de hackathon', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $guild = app(TeamService::class)->createTeam($user1, ['name' => 'Guilda Isolada']);
    $guild->members()->attach($user2->id, ['id' => (string) Str::uuid(), 'role' => 'member', 'joined_at' => now()]);

    $org = \App\Models\Organization::create([
        'id' => (string) Str::uuid(),
        'name' => 'Org Teste',
        'slug' => 'org-teste-' . Str::random(5),
        'owner_id' => $user1->id,
    ]);

    $hackathon = Hackathon::create([
        'id' => (string) Str::uuid(),
        'organization_id' => $org->id,
        'title' => 'Hackathon Inovação',
        'slug' => 'hackathon-inovacao-' . Str::random(5),
        'status' => 'registration',
        'modality' => 'online',
        'registration_type' => 'both',
        'max_team_members' => 5,
        'min_team_members' => 2,
    ]);

    // A guilda existe com 2 membros, mas nenhum deles está inscrito no hackathon
    expect(HackathonRegistration::where('hackathon_id', $hackathon->id)->count())->toBe(0);
    expect(HackathonGroup::where('hackathon_id', $hackathon->id)->count())->toBe(0);

    // Inscreve apenas user1 no hackathon
    $reg1 = HackathonRegistration::create([
        'id' => (string) Str::uuid(),
        'hackathon_id' => $hackathon->id,
        'user_id' => $user1->id,
        'status' => 'approved',
        'is_eligible' => true,
    ]);

    // Guilda continua tendo 2 membros
    expect($guild->fresh()->members)->toHaveCount(2);
    // Mas no hackathon, apenas user1 está registrado
    expect(HackathonRegistration::where('hackathon_id', $hackathon->id)->pluck('user_id'))->toContain($user1->id);
    expect(HackathonRegistration::where('hackathon_id', $hackathon->id)->pluck('user_id'))->not->toContain($user2->id);
});
