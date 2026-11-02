<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domestic B2B shipments through Bigship.
 * Defensive (hasTable) because the live DB has hand-made tables. Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_orders')) {
            Schema::create('b2b_orders', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->index();
                $t->string('order_ref', 40)->nullable()->index();
                $t->string('status', 30)->default('draft')->index(); // draft, booked, in_transit, delivered, rto, cancelled
                $t->unsignedBigInteger('pickup_warehouse_id')->nullable();
                $t->unsignedBigInteger('return_warehouse_id')->nullable();
                $t->text('pickup')->nullable();
                $t->text('return_address')->nullable();
                $t->text('consignee')->nullable();
                $t->string('invoice_no', 60);
                $t->date('invoice_date')->nullable();
                $t->decimal('invoice_amount', 12, 2)->default(0);
                $t->string('payment_mode', 10)->default('prepaid'); // prepaid | cod
                $t->decimal('cod_amount', 12, 2)->default(0);
                $t->string('product_name', 150);
                $t->unsignedTinyInteger('risk_type_id')->default(2);   // 2 owner risk, 3 carrier risk
                $t->text('boxes');                                     // [{count,length,breadth,height,weight}]
                $t->unsignedInteger('total_boxes')->default(1);
                $t->decimal('total_weight', 10, 2)->default(0);       // kg, dead weight
                $t->string('invoice_file')->nullable();
                $t->string('ewaybill_no', 30)->nullable();
                $t->string('ewaybill_file')->nullable();
                $t->string('courier', 20)->default('bigship');
                $t->string('courier_id', 20)->nullable();
                $t->string('courier_name', 120)->nullable();
                $t->unsignedSmallInteger('tat')->nullable();
                $t->decimal('charged_weight', 10, 2)->nullable();
                $t->decimal('courier_cost', 12, 2)->nullable();       // what Bigship charges us
                $t->decimal('seller_charge', 12, 2)->nullable();      // debited from seller wallet (incl. GST)
                $t->text('quote')->nullable();
                $t->string('provider_order_id', 40)->nullable()->index();
                $t->string('awb', 60)->nullable()->index();
                $t->string('lr_number', 60)->nullable();
                $t->string('tracking_status', 80)->nullable();
                $t->longText('tracking')->nullable();
                $t->timestamp('last_tracked_at')->nullable();
                $t->text('error')->nullable();
                $t->timestamp('booked_at')->nullable();
                $t->timestamp('cancelled_at')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('bigship_warehouses')) {
            Schema::create('bigship_warehouses', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->index();
                $t->string('address_hash', 40);
                $t->string('pincode', 10)->nullable();
                $t->unsignedBigInteger('bigship_warehouse_id');
                $t->timestamps();
                $t->unique(['seller_id', 'address_hash']);
            });
        }

        // Each Bigship courier must be approved by admin before sellers see it (new = off)
        if (!Schema::hasTable('bigship_couriers')) {
            Schema::create('bigship_couriers', function (Blueprint $t) {
                $t->id();
                $t->string('courier_id', 20)->unique();
                $t->string('name', 120);
                $t->string('plan_name', 80)->nullable();
                $t->boolean('approved')->default(false);
                $t->timestamp('last_seen_at')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('courier_api_logs')) {
            Schema::create('courier_api_logs', function (Blueprint $t) {
                $t->id();
                $t->string('courier', 40)->index();
                $t->string('endpoint', 80);
                $t->string('method', 8)->default('POST');
                $t->longText('request')->nullable();
                $t->longText('response')->nullable();
                $t->unsignedSmallInteger('http_status')->default(0);
                $t->boolean('success')->default(false);
                $t->unsignedInteger('duration_ms')->default(0);
                $t->unsignedBigInteger('order_id')->nullable()->index();
                $t->string('awb', 60)->nullable()->index();
                $t->timestamps();
                $t->index(['courier', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_orders');
        Schema::dropIfExists('bigship_warehouses');
        Schema::dropIfExists('bigship_couriers');
    }
};
