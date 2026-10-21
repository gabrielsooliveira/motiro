<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create hackathon_groups table
        Schema::create('hackathon_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_id');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->json('technologies')->nullable();
            $table->uuid('leader_id')->nullable();
            $table->uuid('origin_team_id')->nullable(); // Optional reference if spawned from a guild
            $table->string('status')->default('active'); // active, disqualified, etc.
            $table->timestamps();

            $table->foreign('hackathon_id')->references('id')->on('hackathons')->onDelete('cascade');
            $table->foreign('leader_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('origin_team_id')->references('id')->on('teams')->onDelete('set null');
            $table->index(['hackathon_id', 'slug']);
        });

        // 2. Create hackathon_group_members table
        Schema::create('hackathon_group_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hackathon_group_id');
            $table->uuid('user_id');
            $table->string('role')->default('member'); // 'leader' or 'member'
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->foreign('hackathon_group_id')->references('id')->on('hackathon_groups')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['hackathon_group_id', 'user_id']);
        });

        // 3. Add hackathon_group_id to hackathon_registrations
        Schema::table('hackathon_registrations', function (Blueprint $table) {
            $table->uuid('hackathon_group_id')->nullable()->after('team_id');
            $table->foreign('hackathon_group_id')->references('id')->on('hackathon_groups')->onDelete('set null');
        });

        // 4. Add hackathon_group_id to projects
        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('hackathon_group_id')->nullable()->after('team_id');
            $table->foreign('hackathon_group_id')->references('id')->on('hackathon_groups')->onDelete('set null');
        });

        // 5. Data Migration: migrate existing hackathon-linked teams into hackathon_groups
        if (Schema::hasColumn('teams', 'hackathon_id')) {
            $hackathonTeams = DB::table('teams')->whereNotNull('hackathon_id')->get();
            foreach ($hackathonTeams as $ht) {
                // Check if group already exists
                $exists = DB::table('hackathon_groups')->where('id', $ht->id)->exists();
                if (!$exists) {
                    DB::table('hackathon_groups')->insert([
                        'id' => $ht->id,
                        'hackathon_id' => $ht->hackathon_id,
                        'name' => $ht->name,
                        'slug' => $ht->slug,
                        'description' => $ht->description,
                        'technologies' => $ht->technologies,
                        'leader_id' => $ht->leader_id,
                        'origin_team_id' => null,
                        'status' => 'active',
                        'created_at' => $ht->created_at ?? now(),
                        'updated_at' => $ht->updated_at ?? now(),
                    ]);

                    // Migrate members
                    $members = DB::table('team_members')->where('team_id', $ht->id)->get();
                    foreach ($members as $m) {
                        DB::table('hackathon_group_members')->insert([
                            'id' => (string) Str::uuid(),
                            'hackathon_group_id' => $ht->id,
                            'user_id' => $m->user_id,
                            'role' => $m->role,
                            'joined_at' => $m->joined_at ?? now(),
                            'created_at' => $m->created_at ?? now(),
                            'updated_at' => $m->updated_at ?? now(),
                        ]);
                    }

                    // Update hackathon_registrations
                    DB::table('hackathon_registrations')
                        ->where('team_id', $ht->id)
                        ->update(['hackathon_group_id' => $ht->id]);

                    // Update projects
                    DB::table('projects')
                        ->where('team_id', $ht->id)
                        ->update(['hackathon_group_id' => $ht->id]);
                }
            }

            // Permanent teams decouple from hackathon_id
            DB::table('teams')->update(['hackathon_id' => null]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['hackathon_group_id']);
            $table->dropColumn(['hackathon_group_id']);
        });

        Schema::table('hackathon_registrations', function (Blueprint $table) {
            $table->dropForeign(['hackathon_group_id']);
            $table->dropColumn(['hackathon_group_id']);
        });

        Schema::dropIfExists('hackathon_group_members');
        Schema::dropIfExists('hackathon_groups');
    }
};
