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
        Schema::table('teams', function (Blueprint $table) {
            $table->uuid('leader_id')->nullable()->change();
        });

        Schema::table('hackathons', function (Blueprint $table) {
            $table->string('team_formation_mode')->default('by_participants')->after('registration_type');
            $table->boolean('team_leader_required')->default(true)->after('team_formation_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hackathons', function (Blueprint $table) {
            $table->dropColumn(['team_formation_mode', 'team_leader_required']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->uuid('leader_id')->nullable(false)->change();
        });
    }
};
