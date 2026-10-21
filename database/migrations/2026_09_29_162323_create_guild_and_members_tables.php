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
        Schema::create('guilds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->uuid('leader_id');
            $table->timestamps();

            $table->foreign('leader_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('guild_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('guild_id');
            $table->uuid('user_id');
            $table->enum('role', ['leader', 'member'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['guild_id', 'user_id']);
            $table->foreign('guild_id')->references('id')->on('guilds')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('guild_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('guild_id');
            $table->uuid('inviter_id');
            $table->uuid('invitee_id')->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'cancelled'])->default('pending');
            $table->string('token', 64)->unique();
            $table->timestamps();

            $table->foreign('guild_id')->references('id')->on('guilds')->onDelete('cascade');
            $table->foreign('inviter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('invitee_id')->references('id')->on('users')->onDelete('cascade');
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
