<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('level_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');          // WildApricot contact id
            $table->string('member_email')->nullable();
            $table->string('from_type');
            $table->string('to_type');
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 8)->default('usd');

            $table->json('family_members')->nullable();        // submitted spouse/family payload
            $table->json('created_family_ids')->nullable();    // WA ids of family contacts created so far

            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_payment_method_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();

            $table->string('status')->default('pending');      // pending|paid|failed|processed
            $table->unsignedBigInteger('wa_invoice_id')->nullable();
            $table->unsignedBigInteger('wa_bundle_id')->nullable();
            $table->unsignedBigInteger('wa_level_id')->nullable();
            $table->string('wa_step')->nullable();             // invoice|payment|level|family|done
            $table->boolean('processed')->default(false);
            $table->unsignedInteger('retry_count')->default(0);

            $table->string('error_type')->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_decline_code')->nullable();
            $table->text('error_message')->nullable();

            $table->string('payment_method')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_last4', 8)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('contact_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_changes');
    }
};
