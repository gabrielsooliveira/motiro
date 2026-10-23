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
        Schema::create('user_participant', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();

            // Informações educacionais e profissionais
            $table->string('education_institution')->nullable();
            $table->string('education_course')->nullable();
            $table->text('professional_experience')->nullable();
            $table->json('skills')->nullable();
            $table->json('technologies')->nullable();

            // Links externos / portfólio
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('website_url')->nullable();

            // Privacidade do perfil
            $table->boolean('is_profile_public')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_participant');
    }
};
