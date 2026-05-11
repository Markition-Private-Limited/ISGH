<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['executive_board', 'zone_director', 'associate_director'])->after('password');
            $table->enum('access_level', ['city_wide', 'zone', 'center'])->after('role');
            $table->string('zone')->nullable()->after('access_level');
            $table->string('center')->nullable()->after('zone');
            $table->boolean('must_change_password')->default(true)->after('center');
            $table->boolean('is_active')->default(true)->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'access_level', 'zone', 'center', 'must_change_password', 'is_active']);
        });
    }
};
