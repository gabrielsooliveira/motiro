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
        // 1. Grupos formados especificamente no contexto de um hackathon
        Schema::create('hackathon_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_id');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->json('technologies')->nullable();
            $table->uuid('leader_id')->nullable();
            $table->uuid('origin_guild_id')->nullable(); // Referência opcional à Guilda permanente de origem
            $table->uuid('origin_team_id')->nullable(); // Alias para compatibilidade legada
            $table->string('status')->default('active'); // active, disqualified, etc.
            $table->timestamps();

            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
            $table->foreign('leader_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('origin_guild_id')->references('id')->on('guilds')->onDelete('set null');
            $table->index(['hackathon_id', 'slug']);
        });

        // 2. Membros do grupo de hackathon
        Schema::create('hackathon_group_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_group_id');
            $table->uuid('user_id');
            $table->string('role')->default('member'); // 'leader' ou 'member'
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->foreign('hackathon_group_id')->references('id')->on('hackathon_groups')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['hackathon_group_id', 'user_id']);
        });

        // 3. Inscrições de participantes no hackathon
        Schema::create('hackathon_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_id');
            $table->uuid('user_id');
            $table->uuid('hackathon_group_id')->nullable();
            $table->uuid('guild_id')->nullable();
            $table->uuid('team_id')->nullable(); // Alias para compatibilidade legada
            $table->enum('participation_type', ['individual', 'team'])->default('individual');
            $table->boolean('is_eligible')->default(true);
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'cancelled', 'disqualified'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_answers')->nullable();
            $table->json('submitted_documents')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('hackathon_group_id')->references('id')->on('hackathon_groups')->onDelete('set null');
            $table->foreign('guild_id')->references('id')->on('guilds')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['hackathon_id', 'user_id']);
            $table->index(['hackathon_id', 'hackathon_group_id']);
            $table->index(['hackathon_id', 'guild_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hackathon_registrations');
        Schema::dropIfExists('hackathon_group_members');
        Schema::dropIfExists('hackathon_groups');
    }
};
