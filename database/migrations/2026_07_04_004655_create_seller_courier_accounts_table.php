<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_courier_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('courier_account_id');
            $table->unsignedBigInteger('courier_slab_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('courier_account_id')->references('id')->on('courier_accounts')->onDelete('cascade');
            $table->foreign('courier_slab_id')->references('id')->on('courier_slabs')->onDelete('set null');
            $table->unique(['seller_id', 'courier_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_courier_accounts');
    }
};
