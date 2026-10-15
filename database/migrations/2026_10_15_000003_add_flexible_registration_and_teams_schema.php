<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Hackathons Table Updates
        Schema::table('hackathons', function (Blueprint $table) {
            $table->string('registration_type')->default('individual_or_team')->after('max_team_members');
            $table->boolean('allow_team_creation')->default(true)->after('requires_team');
            $table->boolean('allow_team_invites')->default(true)->after('allow_team_creation');
            $table->boolean('allow_team_requests')->default(true)->after('allow_team_invites');
            $table->boolean('allow_org_team_management')->default(true)->after('allow_team_requests');
            $table->dateTime('team_formation_deadline')->nullable()->after('registration_end_date');
            $table->dateTime('team_management_deadline')->nullable()->after('team_formation_deadline');
        });

        // Sync registration_type with requires_team for existing hackathons
        DB::table('hackathons')->where('requires_team', true)->update(['registration_type' => 'team_only']);
        DB::table('hackathons')->where('requires_team', false)->update(['registration_type' => 'individual']);

        // 2. Teams Table Updates
        Schema::table('teams', function (Blueprint $table) {
            $table->uuid('hackathon_id')->nullable()->after('id');
            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
        });

        // Backfill teams.hackathon_id from existing hackathon_registrations
        $registrations = DB::table('hackathon_registrations')->whereNotNull('team_id')->get();
        foreach ($registrations as $reg) {
            DB::table('teams')->where('id', $reg->team_id)->whereNull('hackathon_id')->update([
                'hackathon_id' => $reg->hackathon_id,
            ]);
        }

        // 3. Hackathon Registrations Updates
        Schema::table('hackathon_registrations', function (Blueprint $table) {
            $table->string('participation_type')->default('individual')->after('user_id');
            $table->boolean('is_eligible')->default(true)->after('status');
        });

        // Set participation_type for existing records
        DB::table('hackathon_registrations')->whereNotNull('team_id')->update(['participation_type' => 'team']);
        DB::table('hackathon_registrations')->whereNull('team_id')->update(['participation_type' => 'individual']);

        // Try dropping the old unique constraint on (hackathon_id, team_id) if it exists,
        // and add unique constraint on (hackathon_id, user_id)
        try {
            Schema::table('hackathon_registrations', function (Blueprint $table) {
                $table->dropUnique(['hackathon_id', 'team_id']);
            });
        } catch (\Throwable $e) {
            // Ignore if index name differs or not supported by driver
        }

        try {
            Schema::table('hackathon_registrations', function (Blueprint $table) {
                $table->index(['hackathon_id', 'team_id']);
                $table->unique(['hackathon_id', 'user_id']);
            });
        } catch (\Throwable $e) {
            // Ignore if index already exists
        }

        // 4. Projects Table Updates
        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('team_id')->nullable()->change();
            $table->uuid('user_id')->nullable()->after('team_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        try {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropUnique(['hackathon_id', 'team_id']);
            });
        } catch (\Throwable $e) {
            // Ignore if index drop not needed
        }

        try {
            Schema::table('projects', function (Blueprint $table) {
                $table->index(['hackathon_id', 'team_id']);
                $table->index(['hackathon_id', 'user_id']);
            });
        } catch (\Throwable $e) {
            // Ignore if index already exists
        }

        // 5. Team Invitations Updates (Support join requests)
        Schema::table('team_invitations', function (Blueprint $table) {
            $table->string('type')->default('invite')->after('status'); // 'invite' or 'request'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_invitations', function (Blueprint $table) {
            $table->dropColumn(['type']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id']);
        });

        Schema::table('hackathon_registrations', function (Blueprint $table) {
            $table->dropColumn(['participation_type', 'is_eligible']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['hackathon_id']);
            $table->dropColumn(['hackathon_id']);
        });

        Schema::table('hackathons', function (Blueprint $table) {
            $table->dropColumn([
                'registration_type',
                'allow_team_creation',
                'allow_team_invites',
                'allow_team_requests',
                'allow_org_team_management',
                'team_formation_deadline',
                'team_management_deadline',
            ]);
        });
    }
};
