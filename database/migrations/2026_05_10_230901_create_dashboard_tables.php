<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row global stats snapshot
        Schema::create('dashboard_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('total_members')->default(0);
            $table->unsignedInteger('active_members')->default(0);
            $table->unsignedInteger('lapsed_members')->default(0);
            $table->unsignedInteger('individual_members')->default(0);
            $table->unsignedInteger('checkmatic_members')->default(0);
            $table->unsignedInteger('lifetime_members')->default(0);
            $table->unsignedInteger('total_zips')->default(0);
            $table->timestamp('last_synced_at')->nullable();
        });

        // One row per center
        Schema::create('dashboard_centers', function (Blueprint $table) {
            $table->id();
            $table->string('zone_name');       // e.g. "North Zone"
            $table->string('center_name');     // e.g. "Masjid Bilal - Adel Road"
            $table->unsignedInteger('member_count')->default(0);
            $table->index('zone_name');
        });

        // One row per center + zip combination
        Schema::create('dashboard_center_zips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_center_id')
                  ->constrained('dashboard_centers')
                  ->cascadeOnDelete();
            $table->string('zip', 20);
            $table->string('city', 100)->default('');
            $table->unsignedInteger('member_count')->default(0);
            $table->index(['dashboard_center_id', 'zip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_center_zips');
        Schema::dropIfExists('dashboard_centers');
        Schema::dropIfExists('dashboard_stats');
    }
};
