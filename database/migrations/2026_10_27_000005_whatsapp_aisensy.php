<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp via AiSensy: OTP codes, buyer alert log (also the billing record),
 * NDR responses from buyers, seller opt-in + per-seller rates.
 * Defensive (hasTable / hasColumn) because the live DB has hand-made tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_otps')) {
            Schema::create('whatsapp_otps', function (Blueprint $t) {
                $t->id();
                $t->string('purpose', 20);              // signup | login
                $t->string('phone', 16)->index();       // +91XXXXXXXXXX
                $t->string('code_hash');
                $t->unsignedTinyInteger('attempts')->default(0);
                $t->timestamp('expires_at');
                $t->timestamp('consumed_at')->nullable();
                $t->string('ip', 45)->nullable();
                $t->string('send_status', 20)->nullable(); // sent | failed
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->nullable()->index();
                $t->unsignedBigInteger('order_id')->nullable()->index();
                $t->string('awb_number', 60)->nullable();
                $t->string('event', 30);
                $t->string('dedupe_key', 120)->nullable()->unique(); // order_id:event (ndr: per day)
                $t->string('phone', 16)->nullable();
                $t->string('campaign', 80)->nullable();
                $t->string('status', 30);  // sent | failed | skipped_*
                $t->string('reason', 255)->nullable();
                $t->decimal('charge', 8, 2)->default(0);
                $t->unsignedBigInteger('recharge_id')->nullable();
                $t->unsignedSmallInteger('http_status')->nullable();
                $t->text('response')->nullable();
                $t->timestamps();
                $t->index(['seller_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('ndr_responses')) {
            Schema::create('ndr_responses', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('order_id')->index();
                $t->unsignedBigInteger('seller_id')->nullable()->index();
                $t->string('awb_number', 60)->nullable();
                $t->string('token', 64)->unique();
                $t->timestamp('expires_at')->nullable();
                $t->string('action', 30)->nullable();     // re-attempt | change-address | change-phone | rto
                $t->date('reattempt_date')->nullable();
                $t->text('address')->nullable();
                $t->string('landmark', 190)->nullable();
                $t->string('phone', 16)->nullable();
                $t->string('remark', 255)->nullable();
                $t->string('status', 30)->default('awaiting_buyer'); // awaiting_buyer | sent_to_courier | needs_manual | failed
                $t->text('courier_response')->nullable();
                $t->timestamp('responded_at')->nullable();
                $t->timestamps();
            });
        }

        foreach (['seller_lists', 'seller_list'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (!Schema::hasColumn($table, 'whatsapp_alerts_active')) {
                    $t->boolean('whatsapp_alerts_active')->default(false);
                }
                if (!Schema::hasColumn($table, 'whatsapp_brand_name')) {
                    $t->string('whatsapp_brand_name', 60)->nullable();
                }
                if (!Schema::hasColumn($table, 'whatsapp_rates')) {
                    $t->text('whatsapp_rates')->nullable(); // JSON {event: rate} set by admin; missing = default
                }
                if (!Schema::hasColumn($table, 'phone_verified_at')) {
                    $t->timestamp('phone_verified_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ndr_responses');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_otps');
        foreach (['seller_lists', 'seller_list'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach (['whatsapp_alerts_active', 'whatsapp_brand_name', 'whatsapp_rates', 'phone_verified_at'] as $col) {
                if (Schema::hasColumn($table, $col)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropColumn($col));
                }
            }
        }
    }
};
