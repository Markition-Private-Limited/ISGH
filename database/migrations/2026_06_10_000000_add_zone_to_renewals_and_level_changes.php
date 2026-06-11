<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('renewals', function (Blueprint $table) {
            $table->string('zone')->nullable()->after('membership_type');
        });

        Schema::table('level_changes', function (Blueprint $table) {
            $table->string('zone')->nullable()->after('to_type');
        });
    }

    public function down(): void
    {
        Schema::table('renewals', function (Blueprint $table) {
            $table->dropColumn('zone');
        });

        Schema::table('level_changes', function (Blueprint $table) {
            $table->dropColumn('zone');
        });
    }
};
