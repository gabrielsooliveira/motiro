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
        Schema::create('hackathons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id'); // hackathon pertence à uma organização
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->enum('status', ['draft', 'registration', 'submission', 'voting', 'finished'])->default('draft');
            // landing page customizada, banner, logo
            $table->string('banner_url')->nullable();
            $table->string('logo_url')->nullable();
            // talvez link de streaming
            $table->string('livestream_url')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hackathons');
    }
};
