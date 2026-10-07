<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number');
            $table->string('awb_number')->nullable(); // From XpressBees
            $table->string('courier_id');
            $table->string('payment_type');
            $table->float('order_amount');
            $table->float('shipping_charges');
            $table->float('cod_charges');
            $table->float('discount');
            $table->float('collectable_amount');
            $table->string('package_type')->nullable();
            $table->float('package_weight');
            $table->float('package_length');
            $table->float('package_breadth');
            $table->float('package_height');
            $table->json('consignee');
            $table->json('pickup');
            $table->json('rto')->nullable();
            $table->json('order_items');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
