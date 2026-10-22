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
        Schema::create('hackathons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->enum('status', ['draft', 'published', 'registration', 'in_progress', 'submission', 'voting', 'finished', 'cancelled'])->default('draft');
            $table->string('banner_url')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('livestream_url')->nullable();
            $table->string('rules_file')->nullable();
            $table->text('rules')->nullable();
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->string('location')->nullable();
            $table->enum('modality', ['presential', 'online', 'hybrid'])->default('online');
            $table->string('external_website')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();

            // Limites e parâmetros de participantes e equipes
            $table->unsignedInteger('max_participants')->nullable();
            $table->unsignedInteger('max_teams')->nullable();
            $table->unsignedSmallInteger('min_team_members')->default(1);
            $table->unsignedSmallInteger('max_team_members')->default(5);
            $table->boolean('requires_team')->default(false);
            $table->string('registration_type')->default('individual_or_team');
            $table->string('team_formation_mode')->default('by_participants');
            $table->boolean('team_leader_required')->default(true);
            $table->boolean('allow_team_creation')->default(true);
            $table->boolean('allow_team_invites')->default(true);
            $table->boolean('allow_team_requests')->default(true);
            $table->boolean('allow_org_team_management')->default(true);
            $table->dateTime('team_formation_deadline')->nullable();
            $table->dateTime('team_management_deadline')->nullable();
            $table->boolean('auto_approve_registrations')->default(false);

            // Prazos de etapas
            $table->dateTime('registration_start_date')->nullable();
            $table->dateTime('registration_end_date')->nullable();
            $table->dateTime('submission_start_date')->nullable();
            $table->dateTime('submission_end_date')->nullable();
            $table->boolean('submissions_reopened')->default(false);

            // Critérios de avaliação e resultados
            $table->string('evaluation_distribution')->default('all_judges');
            $table->unsignedSmallInteger('evaluations_per_project_target')->default(3);
            $table->string('scoring_algorithm')->default('weighted_average');
            $table->boolean('results_published')->default(false);

            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });

        Schema::create('hackathon_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->unsignedSmallInteger('order')->default(1);
            $table->enum('status', ['upcoming', 'active', 'completed'])->default('upcoming');
            $table->timestamps();

            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
            $table->index(['hackathon_id', 'order']);
        });

        Schema::create('hackathon_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(1);
            $table->text('requirements')->nullable();
            $table->string('prize')->nullable();
            $table->timestamps();

            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
        });

        Schema::create('hackathon_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_id');
            $table->uuid('user_id');
            $table->enum('role', ['admin', 'manager', 'collaborator'])->default('collaborator');
            $table->json('permissions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['hackathon_id', 'user_id']);
            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hackathon_members');
        Schema::dropIfExists('hackathon_challenges');
        Schema::dropIfExists('hackathon_stages');
        Schema::dropIfExists('hackathons');
    }
};
