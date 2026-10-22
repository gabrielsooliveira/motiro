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
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('slug')->unique();
            $table->string('type')->default('empresa');
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->uuid('owner_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('owner_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('organization_user', function (Blueprint $table) {
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->enum('role', ['admin', 'manager', 'collaborator'])->default('collaborator');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->primary(['organization_id', 'user_id']);

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('organization_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('email');
            $table->enum('role', ['admin', 'manager', 'collaborator'])->default('collaborator');
            $table->uuid('invited_by_id')->nullable();
            $table->string('token', 64)->unique();
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('invited_by_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['organization_id', 'email', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');
        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};
