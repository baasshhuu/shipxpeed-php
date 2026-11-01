<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clear courier booking failure reasons: last reason on the order + a full failure log.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'booking_error')) {
            Schema::table('orders', function (Blueprint $t) {
                $t->text('booking_error')->nullable();
                $t->timestamp('booking_error_at')->nullable();
            });
        }
        if (!Schema::hasTable('courier_booking_failures')) {
            Schema::create('courier_booking_failures', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->nullable()->index();
                $t->unsignedBigInteger('order_id')->nullable()->index();
                $t->string('courier_code', 60)->nullable()->index();
                $t->string('provider_name', 120)->nullable();
                $t->string('reason', 500);
                $t->text('raw')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_booking_failures');
        if (Schema::hasColumn('orders', 'booking_error')) {
            Schema::table('orders', function (Blueprint $t) {
                $t->dropColumn(['booking_error', 'booking_error_at']);
            });
        }
    }
};
