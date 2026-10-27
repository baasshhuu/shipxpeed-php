<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seller › Notify: buyer Email & SMS preferences + a log of every buyer email/SMS.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('seller_notify_settings')) {
            Schema::create('seller_notify_settings', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->unique();
                $t->boolean('hide_product')->default(false);
                $t->boolean('hide_price_prepaid')->default(false);
                $t->text('email_events')->nullable(); // JSON list of stage keys
                $t->text('sms_events')->nullable();   // JSON list of stage keys
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('buyer_notifications')) {
            Schema::create('buyer_notifications', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->index();
                $t->unsignedBigInteger('order_id')->nullable()->index();
                $t->string('awb_number', 64)->nullable();
                $t->string('channel', 10);            // email | sms
                $t->string('event', 32);
                $t->string('recipient', 191)->nullable();
                $t->string('dedupe_key', 120)->unique();
                $t->string('status', 32);             // sending | sent | failed | skipped_*
                $t->string('reason', 255)->nullable();
                $t->decimal('charge', 8, 2)->default(0);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_notifications');
        Schema::dropIfExists('seller_notify_settings');
    }
};
