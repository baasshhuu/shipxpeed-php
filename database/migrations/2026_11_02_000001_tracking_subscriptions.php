<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Buyers who asked (on the public tracking page) for 24-hourly tracking emails. */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tracking_subscriptions')) {
            return;
        }
        Schema::create('tracking_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_id')->index();
            $t->unsignedBigInteger('seller_id')->nullable()->index();
            $t->string('awb_number', 64)->index();
            $t->string('email', 191);
            $t->string('token', 64)->unique();
            $t->boolean('active')->default(true)->index();
            $t->timestamp('last_sent_at')->nullable();
            $t->timestamp('last_event_at')->nullable();
            $t->string('last_status', 64)->nullable();
            $t->unsignedInteger('emails_sent')->default(0);
            $t->string('last_error', 255)->nullable();
            $t->string('stopped_reason', 32)->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
            $t->unique(['order_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_subscriptions');
    }
};
