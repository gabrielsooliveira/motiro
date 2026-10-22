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
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique(); // e.g. superadmin, org_admin, org_moderator, judge, mentor, competitor
            $table->string('display_name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique(); // e.g. hackathon.create, team.approve, judge.assign, evaluation.create
            $table->string('display_name');
            $table->string('group')->default('general'); // e.g. hackathon, team, judge, evaluation, result
            $table->timestamps();
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('permission_id');

            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });

        // Granular contextual permissions per Organization / Hackathon
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('user_id');
            $table->uuid('organization_id')->nullable();
            $table->uuid('hackathon_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'organization_id', 'hackathon_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
