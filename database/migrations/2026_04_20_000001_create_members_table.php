<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            // ── Membership ────────────────────────────────────────────────────
            $table->enum('membership_type', [
                'family',
                'individual',
                'flat',
                'checkomatic_family',
                'checkomatic_individual',
                'lifetime_family',
                'lifetime_individual',
            ]);
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending');
            $table->date('membership_start_date')->nullable();
            $table->date('membership_end_date')->nullable(); // null = lifetime

            // ── Primary member personal info ──────────────────────────────────
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email');
            $table->string('phone', 20);
            $table->date('dob')->nullable();
            $table->string('tx_dl', 50)->nullable();  // TX Driver's Licence / ID
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();

            // ── Address ───────────────────────────────────────────────────────
            $table->string('street')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 10)->nullable();
            $table->string('zip', 10)->nullable();
            $table->string('zone')->nullable(); // ZIP centre zone

            // ── Payment ───────────────────────────────────────────────────────
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_payment_method_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->unsignedInteger('amount_cents')->nullable(); // final charged amount
            $table->unsignedInteger('checkomatic_monthly_cents')->nullable(); // for checkomatic types

            // ── Terms & preferences ───────────────────────────────────────────
            $table->boolean('terms_agreed')->default(false);
            $table->boolean('auto_renewal')->default(false);
            $table->timestamp('terms_agreed_at')->nullable();

            $table->timestamps();

            $table->index('email');
            $table->index(['membership_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
