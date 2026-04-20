<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();

            // ── Internal reference ────────────────────────────────────────────
            $table->string('ref')->unique()->index();                 // UUID from createCheckoutSession
            $table->string('membership_type', 50);

            // ── Stripe identifiers ────────────────────────────────────────────
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_payment_method_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_charge_id')->nullable()->index();

            // ── Payment amounts ───────────────────────────────────────────────
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 10)->default('usd');

            // ── Status ────────────────────────────────────────────────────────
            $table->enum('status', [
                'pending',
                'succeeded',
                'failed',
                'requires_action',
                'cancelled',
                'refunded',
            ])->default('pending');

            // ── Card / payment method details (extracted for quick lookup) ────
            $table->string('card_brand', 30)->nullable();     // visa, mastercard, …
            $table->string('card_last4', 4)->nullable();
            $table->string('card_exp_month', 2)->nullable();
            $table->string('card_exp_year', 4)->nullable();
            $table->string('payment_method_type', 30)->nullable(); // card, us_bank_account, …
            $table->string('receipt_email')->nullable();
            $table->string('description')->nullable();

            // ── Stripe error details (populated on failure) ───────────────────
            $table->string('error_code')->nullable();
            $table->string('error_type')->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_decline_code')->nullable();

            // ── Full raw Stripe responses (JSON) ──────────────────────────────
            $table->json('customer_response')->nullable();
            $table->json('payment_intent_response')->nullable();
            $table->json('payment_confirm_response')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'membership_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
