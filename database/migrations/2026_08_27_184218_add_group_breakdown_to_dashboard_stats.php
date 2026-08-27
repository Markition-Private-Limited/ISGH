<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_stats', function (Blueprint $table) {
            $table->json('group_breakdown')->nullable()->after('level_breakdown');
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_stats', function (Blueprint $table) {
            $table->dropColumn('group_breakdown');
        });
    }
};
