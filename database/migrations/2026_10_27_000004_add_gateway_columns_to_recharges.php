<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cashfree PG wallet recharge: track gateway order/payment on each recharge row.
 * Defensive (hasTable / hasColumn) because the live DB has hand-made tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('recharges')) {
            return;
        }

        Schema::table('recharges', function (Blueprint $t) {
            if (!Schema::hasColumn('recharges', 'gateway')) {
                $t->string('gateway', 20)->nullable();
            }
            if (!Schema::hasColumn('recharges', 'gateway_order_id')) {
                $t->string('gateway_order_id', 60)->nullable()->index();
            }
            if (!Schema::hasColumn('recharges', 'gateway_payment_id')) {
                $t->string('gateway_payment_id', 60)->nullable();
            }
            if (!Schema::hasColumn('recharges', 'gateway_status')) {
                $t->string('gateway_status', 30)->nullable();
            }
            if (!Schema::hasColumn('recharges', 'paid_at')) {
                $t->timestamp('paid_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('recharges')) {
            return;
        }

        foreach (['gateway', 'gateway_order_id', 'gateway_payment_id', 'gateway_status', 'paid_at'] as $col) {
            if (Schema::hasColumn('recharges', $col)) {
                Schema::table('recharges', fn (Blueprint $t) => $t->dropColumn($col));
            }
        }
    }
};
