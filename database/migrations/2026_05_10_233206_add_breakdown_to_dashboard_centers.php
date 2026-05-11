<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_centers', function (Blueprint $table) {
            $table->unsignedInteger('active_members')->default(0)->after('member_count');
            $table->unsignedInteger('lapsed_members')->default(0)->after('active_members');
            $table->unsignedInteger('individual_members')->default(0)->after('lapsed_members');
            $table->unsignedInteger('checkmatic_members')->default(0)->after('individual_members');
            $table->unsignedInteger('lifetime_members')->default(0)->after('checkmatic_members');
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_centers', function (Blueprint $table) {
            $table->dropColumn([
                'active_members', 'lapsed_members',
                'individual_members', 'checkmatic_members', 'lifetime_members',
            ]);
        });
    }
};
