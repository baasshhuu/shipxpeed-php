<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_slab_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('courier_slab_id');
            $table->string('zone');
            $table->decimal('cod_price', 10, 2)->default(0);
            $table->decimal('cod_fix_price', 10, 2)->default(0);
            $table->decimal('prepaid_price', 10, 2)->default(0);
            $table->decimal('prepaid_fix_price', 10, 2)->default(0);
            $table->decimal('cod_charge_percent', 5, 2)->default(0);
            $table->decimal('rto_credit', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('courier_slab_id')->references('id')->on('courier_slabs')->onDelete('cascade');
            $table->unique(['courier_slab_id', 'zone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_slab_rates');
    }
};
