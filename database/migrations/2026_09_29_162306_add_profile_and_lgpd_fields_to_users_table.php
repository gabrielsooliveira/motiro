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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('username');
            $table->string('city')->nullable()->after('phone');
            $table->string('state', 50)->nullable()->after('city');
            $table->string('education_institution')->nullable()->after('state');
            $table->string('education_course')->nullable()->after('education_institution');
            $table->text('professional_experience')->nullable()->after('education_course');
            $table->json('skills')->nullable()->after('professional_experience');
            $table->json('technologies')->nullable()->after('skills');
            
            // Links
            $table->string('github_url')->nullable()->after('technologies');
            $table->string('linkedin_url')->nullable()->after('github_url');
            $table->string('portfolio_url')->nullable()->after('linkedin_url');
            $table->string('website_url')->nullable()->after('portfolio_url');

            // LGPD / Privacy
            $table->boolean('is_profile_public')->default(true)->after('website_url');
            $table->timestamp('lgpd_consent_at')->nullable()->after('is_profile_public');
            $table->timestamp('terms_accepted_at')->nullable()->after('lgpd_consent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'city',
                'state',
                'education_institution',
                'education_course',
                'professional_experience',
                'skills',
                'technologies',
                'github_url',
                'linkedin_url',
                'portfolio_url',
                'website_url',
                'is_profile_public',
                'lgpd_consent_at',
                'terms_accepted_at',
            ]);
        });
    }
};
