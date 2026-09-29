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
        // 1. Evaluation Criteria defined per Hackathon
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_id');
            $table->string('name'); // e.g. Inovação, Impacto, UX, etc.
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(1.00); // e.g. 0.30 or 30.00
            $table->decimal('min_score', 5, 2)->default(0.00);
            $table->decimal('max_score', 5, 2)->default(10.00);
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('order')->default(1);
            $table->timestamps();

            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
        });

        // 2. Judge Assignments (Judges assigned to hackathons or specific projects)
        Schema::create('judge_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_id');
            $table->uuid('user_id'); // Judge user
            $table->uuid('project_id')->nullable(); // Null if assigned to hackathon generally, or specific project
            $table->enum('status', ['assigned', 'in_progress', 'completed'])->default('assigned');
            $table->timestamps();

            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->unique(['hackathon_id', 'user_id', 'project_id']);
        });

        // 3. Conflicts of Interest
        Schema::create('conflicts_of_interest', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_id');
            $table->uuid('user_id'); // Judge declaring conflict
            $table->uuid('project_id')->nullable(); // Target project or team
            $table->uuid('team_id')->nullable();
            $table->text('reason');
            $table->enum('status', ['declared', 'reviewed_by_admin', 'dismissed'])->default('declared');
            $table->timestamps();

            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });

        // 4. Evaluations completed by Judges for Projects
        Schema::create('evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_id');
            $table->uuid('project_id');
            $table->uuid('user_id'); // Judge
            $table->decimal('final_score', 8, 2)->nullable();
            $table->text('feedback_positive')->nullable();
            $table->text('feedback_improvement')->nullable();
            $table->text('private_admin_notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'locked'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['project_id', 'user_id']);
        });

        // 5. Individual criterion score
        Schema::create('evaluation_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('evaluation_id');
            $table->uuid('criteria_id');
            $table->decimal('score', 5, 2);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('evaluation_id')->references('id')->on('evaluations')->onDelete('cascade');
            $table->foreign('criteria_id')->references('id')->on('evaluation_criteria')->onDelete('cascade');
            $table->unique(['evaluation_id', 'criteria_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_scores');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('conflicts_of_interest');
        Schema::dropIfExists('judge_assignments');
        Schema::dropIfExists('evaluation_criteria');
    }
};
