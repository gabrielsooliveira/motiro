<?php

namespace Tests\Feature\Guild;

use App\Actions\User\CreateUserAccountAction;
use App\Models\Guild;
use App\Models\GuildInvitation;
use App\Models\User;
use App\Services\GuildService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuildServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GuildService $guildService;
    protected User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->guildService = app(GuildService::class);
        $createUserAction = app(CreateUserAccountAction::class);

        $this->creator = $createUserAction->execute([
            'name' => 'Criador da Guild',
            'email' => 'criador@motiro.test',
            'password' => 'secret123',
        ], true);
    }

    public function test_can_create_guild_with_creator_as_leader(): void
    {
        $guild = $this->guildService->createGuild($this->creator, [
            'name' => 'Guild dos Devs',
            'description' => 'Comunidade voltada a desenvolvimento e hackathons.',
            'area_of_focus' => 'Fullstack & IA',
            'technologies' => ['PHP', 'Vue.js', 'Python'],
            'is_looking_for_members' => true,
        ]);

        $this->assertDatabaseHas('guilds', [
            'id' => $guild->id,
            'name' => 'Guild dos Devs',
            'slug' => 'guild-dos-devs',
            'creator_id' => $this->creator->id,
            'leader_id' => $this->creator->id,
        ]);

        $this->assertTrue($guild->isLeader($this->creator));
        $this->assertTrue($guild->isCreator($this->creator));
        $this->assertTrue($guild->isMember($this->creator));
        $this->assertEquals(1, $guild->members()->count());
    }

    public function test_can_update_guild_data(): void
    {
        $guild = $this->guildService->createGuild($this->creator, [
            'name' => 'Guild Inicial',
            'description' => 'Desc inicial',
        ]);

        $updated = $this->guildService->updateGuild($guild, [
            'name' => 'Guild Atualizada',
            'description' => 'Nova descrição refinada.',
            'area_of_focus' => 'Mobile & Cloud',
            'is_looking_for_members' => false,
        ], $this->creator);

        $this->assertEquals('Guild Atualizada', $updated->name);
        $this->assertEquals('guild-atualizada', $updated->slug);
        $this->assertEquals('Nova descrição refinada.', $updated->description);
        $this->assertFalse($updated->is_looking_for_members);
    }

    public function test_non_manager_cannot_update_guild(): void
    {
        $guild = $this->guildService->createGuild($this->creator, ['name' => 'Guild Segura']);
        $outsider = User::factory()->create();

        $this->expectException(Exception::class);
        $this->guildService->updateGuild($guild, ['name' => 'Nome Inválido'], $outsider);
    }

    public function test_can_invite_member_by_email_and_accept_via_token(): void
    {
        $guild = $this->guildService->createGuild($this->creator, ['name' => 'Guild Alfa']);
        $invitee = User::factory()->create(['email' => 'convidado@motiro.test']);

        $invitation = $this->guildService->inviteMember($guild, $this->creator, 'convidado@motiro.test');

        $this->assertDatabaseHas('guild_invitations', [
            'id' => $invitation->id,
            'guild_id' => $guild->id,
            'email' => 'convidado@motiro.test',
            'status' => 'pending',
            'type' => 'invite',
        ]);

        // Aceita o convite com a conta autenticada do usuário
        $acceptedGuild = $this->guildService->acceptInvitationByToken($invitation->token, $invitee);

        $this->assertTrue($acceptedGuild->isMember($invitee));
        $this->assertEquals(2, $acceptedGuild->members()->count());
        $this->assertEquals('accepted', $invitation->fresh()->status);
    }

    public function test_can_elect_new_leader(): void
    {
        $guild = $this->guildService->createGuild($this->creator, ['name' => 'Guild Democrática']);
        $member = User::factory()->create();

        // Adiciona membro
        $guild->members()->attach($member->id, [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->guildService->electLeader($guild, $member, $this->creator);

        $this->assertEquals($member->id, $guild->fresh()->leader_id);
        $this->assertTrue($guild->fresh()->isLeader($member));
    }

    public function test_leader_cannot_be_removed_without_transferring_leadership(): void
    {
        $guild = $this->guildService->createGuild($this->creator, ['name' => 'Guild Teste']);

        $this->expectException(Exception::class);
        $this->guildService->removeMember($guild, $this->creator, $this->creator);
    }

    public function test_leave_guild_transfers_leadership_to_next_member(): void
    {
        $guild = $this->guildService->createGuild($this->creator, ['name' => 'Guild Sucessão']);
        $successor = User::factory()->create();

        $guild->members()->attach($successor->id, [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->guildService->leaveGuild($guild, $this->creator);

        $this->assertFalse($guild->fresh()->isMember($this->creator));
        $this->assertTrue($guild->fresh()->isMember($successor));
        $this->assertEquals($successor->id, $guild->fresh()->leader_id);
    }

    public function test_participant_can_request_to_join_and_leader_accepts(): void
    {
        $guild = $this->guildService->createGuild($this->creator, [
            'name' => 'Guild Aberta',
            'is_looking_for_members' => true,
        ]);

        $applicant = User::factory()->create();

        $request = $this->guildService->requestToJoin($guild, $applicant);

        $this->assertEquals('pending', $request->status);
        $this->assertEquals('request', $request->type);
        $this->assertEquals($applicant->id, $request->inviter_id);

        $this->guildService->acceptRequest($request, $this->creator);

        $this->assertEquals('accepted', $request->fresh()->status);
        $this->assertTrue($guild->fresh()->isMember($applicant));
    }

    public function test_http_participant_can_create_and_manage_guild(): void
    {
        $response = $this->actingAs($this->creator)
            ->post(route('participant.guilds.store'), [
                'name' => 'Guild HTTP Teste',
                'description' => 'Descrição via HTTP',
                'area_of_focus' => 'Engenharia',
            ]);

        $guild = Guild::where('name', 'Guild HTTP Teste')->first();
        $this->assertNotNull($guild);
        $response->assertRedirect(route('participant.guilds.show', $guild->id));

        // Test view guild show
        $showResponse = $this->actingAs($this->creator)
            ->get(route('participant.guilds.show', $guild->id));
        $showResponse->assertOk();
    }

    public function test_participant_guild_index_routes_to_participant_controller(): void
    {
        $response = $this->actingAs($this->creator)
            ->get(route('participant.guilds.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Participant/Dashboard/Guild/Index'));
    }
}
