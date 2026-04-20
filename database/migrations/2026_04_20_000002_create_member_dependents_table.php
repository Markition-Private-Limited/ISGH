<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_dependents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();

            // spouse | flat_member
            $table->enum('type', ['spouse', 'flat_member']);

            // ── Personal info ─────────────────────────────────────────────────
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('dob')->nullable();
            $table->string('tx_dl', 50)->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();

            // relation is used for flat_member type (Child, Sibling, Parent, Spouse, etc.)
            $table->string('relation', 50)->nullable();

            // ── Address (mirrors primary — stored for data integrity) ─────────
            $table->string('street')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 10)->nullable();
            $table->string('zip', 10)->nullable();

            $table->timestamps();

            $table->index(['member_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_dependents');
    }
};
