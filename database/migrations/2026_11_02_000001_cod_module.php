<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * COD remittance module.
 *  - cod_remittances: one row per delivered COD order (generated on its delivered date)
 *  - cod_payouts:     one row per payout to a seller's bank (admin "mark paid")
 * Defensive (hasTable) because the live DB has hand-made tables. Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cod_payouts')) {
            Schema::create('cod_payouts', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->index();
                $t->unsignedInteger('orders')->default(0);
                $t->decimal('cod_amount', 14, 2)->default(0);
                $t->decimal('fee_amount', 14, 2)->default(0);
                $t->decimal('wallet_adjusted', 14, 2)->default(0);
                $t->decimal('amount', 14, 2)->default(0);           // paid to bank
                $t->string('reference', 120)->nullable();           // UTR / batch ref
                $t->date('paid_on');
                $t->string('account_holder', 150)->nullable();      // bank snapshot at payout time
                $t->string('bank_name', 150)->nullable();
                $t->string('account_number', 40)->nullable();
                $t->string('ifsc', 20)->nullable();
                $t->unsignedBigInteger('paid_by')->nullable();
                $t->timestamp('mailed_at')->nullable();
                $t->string('mail_error', 255)->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('cod_remittances')) {
            Schema::create('cod_remittances', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->index();
                $t->unsignedBigInteger('order_id')->unique();
                $t->string('order_number', 80)->nullable();
                $t->string('awb', 80)->nullable()->index();
                $t->string('courier', 120)->nullable();
                $t->decimal('cod_amount', 12, 2)->default(0);
                $t->date('delivered_on')->index();
                $t->string('plan', 4)->default('d7');                 // d7 | d4 | d2
                $t->unsignedTinyInteger('plan_days')->default(7);
                $t->decimal('fee_pct', 5, 2)->default(0);
                $t->decimal('fee_amount', 12, 2)->default(0);         // early COD fee, cut from payout
                $t->decimal('wallet_adjusted', 12, 2)->default(0);    // used to bring wallet up to the minimum
                $t->decimal('net_payable', 12, 2)->default(0);        // cod - fee - wallet_adjusted
                $t->date('due_on')->index();                          // payable date as per plan
                $t->string('status', 12)->default('pending')->index(); // pending | adjusted | paid
                $t->unsignedBigInteger('payout_id')->nullable()->index();
                $t->timestamp('paid_at')->nullable();
                $t->string('note', 190)->nullable();
                $t->timestamps();
                $t->index(['seller_id', 'status', 'due_on']);
            });
        }

        // Early COD prices (seller self-activates; no admin approval)
        if (Schema::hasTable('xp_settings')) {
            foreach (['early_cod_enabled' => '1', 'early_cod_d4_pct' => '0.99', 'early_cod_d2_pct' => '1.29'] as $k => $v) {
                DB::table('xp_settings')->updateOrInsert(['key' => $k], ['value' => $v, 'updated_at' => now(), 'created_at' => now()]);
            }
            try { \Illuminate\Support\Facades\Cache::forget('xp_settings_all'); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cod_remittances');
        Schema::dropIfExists('cod_payouts');
    }
};
