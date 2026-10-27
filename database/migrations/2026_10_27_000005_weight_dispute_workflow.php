<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Weight dispute workflow:
 *   admin uploads courier-charged weights -> excess charge auto-debited from the seller wallet
 *   -> seller can raise a dispute with photo/video proof inside the dispute window
 *   -> admin accepts (auto refund to wallet) or rejects (closed).
 * Defensive (hasTable / hasColumn) because the live DB has hand-made tables.
 */
return new class extends Migration
{
    private array $cols = [
        'seller_id', 'order_id', 'order_number', 'applied_weight', 'applied_dead_weight', 'applied_vol_weight',
        'charged_weight_g', 'weight_type', 'courier_length', 'courier_breadth', 'courier_height',
        'extra_slabs', 'excess_charge', 'calc_method', 'admin_note', 'dispute_status', 'deducted_at',
        'dispute_deadline', 'debit_recharge_id', 'seller_remark', 'seller_proofs', 'raised_at',
        'admin_remark', 'reviewed_at', 'reviewed_by', 'refund_recharge_id', 'refunded_at', 'upload_batch',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('weight_disputes')) {
            Schema::create('weight_disputes', function (Blueprint $t) {
                $t->id();
                $t->string('awb')->nullable()->index();
                $t->string('courier')->nullable();
                $t->string('Mentionedweight')->nullable();
                $t->string('chargedweight')->nullable();
                $t->string('weightmissmatched')->nullable();
                $t->string('weightdisputecharges')->nullable();
                $t->string('status')->nullable()->default('0');
                $t->timestamps();
            });
        }

        Schema::table('weight_disputes', function (Blueprint $t) {
            $has = fn ($c) => Schema::hasColumn('weight_disputes', $c);
            if (!$has('seller_id'))           $t->unsignedBigInteger('seller_id')->nullable()->index();
            if (!$has('order_id'))            $t->unsignedBigInteger('order_id')->nullable();
            if (!$has('order_number'))        $t->string('order_number', 100)->nullable();
            if (!$has('applied_weight'))      $t->decimal('applied_weight', 10, 2)->nullable();      // grams the seller was billed on
            if (!$has('applied_dead_weight')) $t->decimal('applied_dead_weight', 10, 2)->nullable(); // grams entered by seller
            if (!$has('applied_vol_weight'))  $t->decimal('applied_vol_weight', 10, 2)->nullable();  // grams from seller's L x B x H
            if (!$has('charged_weight_g'))    $t->decimal('charged_weight_g', 10, 2)->nullable();    // grams the courier charged
            if (!$has('weight_type'))         $t->string('weight_type', 20)->nullable();             // dead | volumetric
            if (!$has('courier_length'))      $t->decimal('courier_length', 8, 2)->nullable();
            if (!$has('courier_breadth'))     $t->decimal('courier_breadth', 8, 2)->nullable();
            if (!$has('courier_height'))      $t->decimal('courier_height', 8, 2)->nullable();
            if (!$has('extra_slabs'))         $t->integer('extra_slabs')->nullable();
            if (!$has('excess_charge'))       $t->decimal('excess_charge', 10, 2)->nullable();
            if (!$has('calc_method'))         $t->string('calc_method', 30)->nullable();             // rate_card | proportional | manual
            if (!$has('admin_note'))          $t->string('admin_note', 500)->nullable();
            if (!$has('dispute_status'))      $t->string('dispute_status', 20)->nullable()->index(); // charged | disputed | accepted | rejected | closed | void
            if (!$has('deducted_at'))         $t->timestamp('deducted_at')->nullable();
            if (!$has('dispute_deadline'))    $t->timestamp('dispute_deadline')->nullable();
            if (!$has('debit_recharge_id'))   $t->unsignedBigInteger('debit_recharge_id')->nullable();
            if (!$has('seller_remark'))       $t->text('seller_remark')->nullable();
            if (!$has('seller_proofs'))       $t->text('seller_proofs')->nullable();                 // JSON list of stored files
            if (!$has('raised_at'))           $t->timestamp('raised_at')->nullable();
            if (!$has('admin_remark'))        $t->text('admin_remark')->nullable();
            if (!$has('reviewed_at'))         $t->timestamp('reviewed_at')->nullable();
            if (!$has('reviewed_by'))         $t->unsignedBigInteger('reviewed_by')->nullable();
            if (!$has('refund_recharge_id'))  $t->unsignedBigInteger('refund_recharge_id')->nullable();
            if (!$has('refunded_at'))         $t->timestamp('refunded_at')->nullable();
            if (!$has('upload_batch'))        $t->string('upload_batch', 40)->nullable();
        });

        // Old rows from the previous screen: charged ones (status 1) are closed, never-charged ones are void.
        DB::table('weight_disputes')->whereNull('dispute_status')->where('status', '1')->update(['dispute_status' => 'closed']);
        DB::table('weight_disputes')->whereNull('dispute_status')->update(['dispute_status' => 'void']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('weight_disputes')) {
            return;
        }
        foreach ($this->cols as $col) {
            if (Schema::hasColumn('weight_disputes', $col)) {
                Schema::table('weight_disputes', fn (Blueprint $t) => $t->dropColumn($col));
            }
        }
    }
};
