<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_opt_ins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id')->index();     // Wild Apricot contact id
            $table->string('member_email')->unique();              // email entered at upload, stored lowercase
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('membership_type')->nullable();
            $table->string('center')->nullable();
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('status')->default('waiting_for_approval');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_opt_ins');
    }
};
