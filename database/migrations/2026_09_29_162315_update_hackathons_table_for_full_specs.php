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
        Schema::table('hackathons', function (Blueprint $table) {
            $table->string('slug')->unique()->after('title');
            $table->string('category')->nullable()->after('slug');
            $table->json('tags')->nullable()->after('category');
            $table->string('location')->nullable()->after('tags');
            $table->enum('modality', ['presential', 'online', 'hybrid'])->default('online')->after('location');

            // Limits
            $table->unsignedInteger('max_participants')->nullable()->after('end_date');
            $table->unsignedInteger('max_teams')->nullable()->after('max_participants');
            $table->unsignedSmallInteger('min_team_members')->default(1)->after('max_teams');
            $table->unsignedSmallInteger('max_team_members')->default(5)->after('min_team_members');

            // Registration policy
            $table->boolean('requires_team')->default(true)->after('max_team_members');
            $table->boolean('auto_approve_registrations')->default(false)->after('requires_team');
            $table->dateTime('registration_start_date')->nullable()->after('auto_approve_registrations');
            $table->dateTime('registration_end_date')->nullable()->after('registration_start_date');

            // Submissions & Evaluation configuration
            $table->dateTime('submission_start_date')->nullable()->after('registration_end_date');
            $table->dateTime('submission_end_date')->nullable()->after('submission_start_date');
            $table->boolean('submissions_reopened')->default(false)->after('submission_end_date');
            $table->enum('evaluation_distribution', ['manual', 'all_to_all', 'n_evaluations_per_project'])->default('all_to_all')->after('submissions_reopened');
            $table->unsignedSmallInteger('evaluations_per_project_target')->default(3)->after('evaluation_distribution');
            $table->enum('scoring_algorithm', ['weighted_average', 'weighted_sum', 'trimmed_mean'])->default('weighted_average')->after('evaluations_per_project_target');
            $table->boolean('results_published')->default(false)->after('scoring_algorithm');

            // Content & Regulatory
            $table->longText('rules')->nullable()->after('results_published');
            $table->string('external_website')->nullable()->after('rules');
            $table->string('contact_email')->nullable()->after('external_website');
            $table->string('contact_phone')->nullable()->after('contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hackathons', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'category',
                'tags',
                'location',
                'modality',
                'max_participants',
                'max_teams',
                'min_team_members',
                'max_team_members',
                'requires_team',
                'auto_approve_registrations',
                'registration_start_date',
                'registration_end_date',
                'submission_start_date',
                'submission_end_date',
                'submissions_reopened',
                'evaluation_distribution',
                'evaluations_per_project_target',
                'scoring_algorithm',
                'results_published',
                'rules',
                'external_website',
                'contact_email',
                'contact_phone',
            ]);
        });
    }
};
