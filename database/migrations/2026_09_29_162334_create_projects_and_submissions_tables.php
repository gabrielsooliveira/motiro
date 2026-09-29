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
            $table->uuid('hackathon_id');
            $table->uuid('team_id');
            $table->string('title');
            $table->string('slug');
            $table->text('short_description')->nullable();
            $table->longText('problem_statement')->nullable();
            $table->longText('solution_description')->nullable();
            $table->string('target_audience')->nullable();
            $table->json('technologies')->nullable();
            $table->string('category')->nullable();
            
            // Deliverables / Media
            $table->text('pitch')->nullable();
            $table->string('video_url')->nullable();
            $table->json('images')->nullable();
            $table->string('pitch_deck_pdf')->nullable();
            $table->string('github_repository_url')->nullable();
            $table->string('demo_url')->nullable();
            $table->string('website_url')->nullable();
            $table->text('documentation_url')->nullable();

            $table->enum('status', ['draft', 'submitted', 'locked'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->unique(['hackathon_id', 'team_id']);
        });

        Schema::create('project_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('submitted_by');
            $table->string('version')->default('1.0');
            $table->json('snapshot_data'); // Snapshot of submitted payload for audit and immutability
            $table->timestamps();

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
