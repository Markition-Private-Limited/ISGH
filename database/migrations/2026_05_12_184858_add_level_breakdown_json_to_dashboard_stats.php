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
        Schema::table('dashboard_stats', function (Blueprint $table) {
            $table->json('level_breakdown')->nullable()->after('lifetime_members');
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_stats', function (Blueprint $table) {
            $table->dropColumn('level_breakdown');
        });
    }
};
