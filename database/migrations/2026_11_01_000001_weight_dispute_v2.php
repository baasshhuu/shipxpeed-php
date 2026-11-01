<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weight disputes v2:
 *  - source: excel | auto (fetched from the courier API)
 *  - courier_proofs: courier's photos / links (auto-fetched or added by admin)
 *  - seller_links: photo / video links the seller shares (Drive, YouTube …) next to uploads
 *  - weight_fetch_checks: which orders were already checked against the courier API
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('weight_disputes')) {
            Schema::table('weight_disputes', function (Blueprint $t) {
                $has = fn ($c) => Schema::hasColumn('weight_disputes', $c);
                if (!$has('source'))         $t->string('source', 20)->nullable();
                if (!$has('courier_proofs')) $t->text('courier_proofs')->nullable();
                if (!$has('seller_links'))   $t->text('seller_links')->nullable();
            });
        }

        if (!Schema::hasTable('weight_fetch_checks')) {
            Schema::create('weight_fetch_checks', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('order_id')->unique();
                $t->string('awb', 64)->nullable()->index();
                $t->string('source', 20)->nullable();        // shipclues | delhivery
                $t->unsignedInteger('attempts')->default(0);
                $t->boolean('done')->default(false)->index();
                $t->string('result', 30)->nullable();        // no_data | charged | within_slab | error | unsupported
                $t->string('message', 255)->nullable();
                $t->decimal('courier_weight_g', 10, 2)->nullable();
                $t->timestamp('last_checked_at')->nullable()->index();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('weight_fetch_checks');
    }
};
