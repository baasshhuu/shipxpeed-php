<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('seller_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id'); // Foreign key
            $table->string('city_id')->nullable();
            $table->string('state_id')->nullable();
            $table->string('country')->nullable();
            $table->string('pincode')->nullable();
            $table->string('state')->nullable();
            $table->string('address_line')->nullable();
            $table->timestamps();

            $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_addresses');
    }
};
