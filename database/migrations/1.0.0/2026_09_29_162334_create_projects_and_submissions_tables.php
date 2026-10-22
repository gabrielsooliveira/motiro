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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_id')->nullable();
            $table->uuid('hackathon_group_id')->nullable();
            $table->uuid('guild_id')->nullable();
            $table->uuid('team_id')->nullable(); // Compatibilidade legada
            $table->uuid('user_id')->nullable(); // Para submissões individuais
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('short_description')->nullable();
            $table->longText('problem_statement')->nullable();
            $table->longText('solution_description')->nullable();
            $table->string('target_audience')->nullable();
            $table->json('technologies')->nullable();
            $table->string('category')->nullable();

            // Entregáveis / Mídia
            $table->text('pitch')->nullable();
            $table->string('pitch_deck_pdf')->nullable();
            $table->string('video_url')->nullable();
            $table->json('images')->nullable();
            $table->string('github_repository_url')->nullable();
            $table->string('demo_url')->nullable();
            $table->string('website_url')->nullable();
            $table->text('documentation_url')->nullable();

            $table->enum('status', ['draft', 'submitted', 'locked'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
            $table->foreign('hackathon_group_id')->references('id')->on('hackathon_groups')->onDelete('cascade');
            $table->foreign('guild_id')->references('id')->on('guilds')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['hackathon_id', 'hackathon_group_id']);
            $table->index(['hackathon_id', 'guild_id']);
            $table->index(['hackathon_id', 'user_id']);
        });

        Schema::create('project_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('submitted_by');
            $table->string('version')->default('1.0');
            $table->json('snapshot_data'); // Snapshot imutável da submissão para auditoria
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_submissions');
        Schema::dropIfExists('projects');
    }
};
