<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            // Which WA step failed: contact | invoice | payment | spouses | done
            $table->string('wa_step')->nullable()->after('processed_at');
            // Full error message from WA API
            $table->text('wa_error')->nullable()->after('wa_step');
            // When the error occurred
            $table->timestamp('wa_error_at')->nullable()->after('wa_error');
            // How many retry attempts were made
            $table->unsignedSmallInteger('retry_count')->default(0)->after('wa_error_at');
            // Stripe payment confirmed (paid) but WA not yet processed
            $table->boolean('stripe_paid')->default(false)->after('retry_count');
        });
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn(['wa_step', 'wa_error', 'wa_error_at', 'retry_count', 'stripe_paid']);
        });
    }
};
