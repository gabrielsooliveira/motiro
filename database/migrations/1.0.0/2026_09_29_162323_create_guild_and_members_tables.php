<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Guildas Permanentes de participantes
        Schema::create('guilds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->json('technologies')->nullable();
            $table->string('area_of_focus')->nullable();
            $table->json('links')->nullable();
            $table->boolean('is_looking_for_members')->default(true);
            $table->uuid('creator_id')->nullable();
            $table->uuid('leader_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('leader_id')->references('id')->on('users')->onDelete('set null');
        });

        // 2. Membros da Guilda
        Schema::create('guild_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('guild_id');
            $table->uuid('team_id')->nullable(); // Suporte a compatibilidade
            $table->uuid('user_id');
            $table->enum('role', ['leader', 'member'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['guild_id', 'user_id']);
            $table->foreign('guild_id')->references('id')->on('guilds')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 3. Convites e solicitações de ingresso na Guilda
        Schema::create('guild_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('guild_id');
            $table->uuid('team_id')->nullable(); // Suporte a compatibilidade
            $table->uuid('inviter_id');
            $table->uuid('invitee_id')->nullable();
            $table->string('email')->nullable();
            $table->enum('type', ['invite', 'request'])->default('invite');
            $table->enum('status', ['pending', 'accepted', 'declined', 'cancelled'])->default('pending');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('guild_id')->references('id')->on('guilds')->onDelete('cascade');
            $table->foreign('inviter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('invitee_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['guild_id', 'email', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guild_invitations');
        Schema::dropIfExists('guild_members');
        Schema::dropIfExists('guilds');
    }
};
