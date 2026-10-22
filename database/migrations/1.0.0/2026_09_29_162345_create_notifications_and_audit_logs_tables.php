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
        // Internal In-App Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('type'); // e.g. team_invite, registration_approved, stage_started, evaluation_assigned
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'read_at']);
        });

        // Audit Logs for administrative accountability
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable(); // Admin/Actor
            $table->uuid('organization_id')->nullable();
            $table->uuid('hackathon_id')->nullable();
            $table->string('action'); // e.g. team.approved, stage.updated, judge.added, result.published
            $table->string('auditable_type');
            $table->uuid('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
            $table->index(['hackathon_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
    }
};
